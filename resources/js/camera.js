import * as faceapi from '@vladmandic/face-api';

const cfg = window.VECTOR_CAMERA ?? {};
const $ = (id) => document.getElementById(id);

const els = {
    video: $('camera-video'),
    placeholder: $('camera-placeholder'),
    status: $('camera-status'),
    hint: $('camera-hint'),
    bars: $('camera-bars'),
    start: $('camera-start'),
    stop: $('camera-stop'),
    retry: $('camera-retry'),
    result: $('camera-result'),
    resultValue: $('camera-result-value'),
    resultText: $('camera-result-text'),
    targetButtons: document.querySelectorAll('[data-camera-target]'),
    threshold: $('camera-threshold'),
    progress: $('camera-progress'),
    attempts: $('camera-attempts'),
    completedCount: $('camera-completed'),
    totalCount: $('camera-total'),
    percent: $('camera-percent'),
    completionBar: $('camera-completion-bar'),
    completionTrack: $('camera-completion-track'),
    finish: $('camera-finish'),
};

const labels = cfg.labels ?? {};
const thresholds = cfg.thresholds ?? {};
const holdMs = cfg.holdMs ?? 2000;
const smoothFactor = 0.55;

let target = cfg.targets?.[0] ?? 'happy';
let stream = null;
let running = false;
let modelReady = false;
let hold = 0;
let best = 0;
let attempts = 0;
let lastFrame = performance.now();
let smoothed = {};
let submitting = false;
let loopTimer = null;
let completed = new Set(Array.isArray(cfg.completed) ? cfg.completed : []);
const totalTargets = Number(cfg.total ?? cfg.targets?.length ?? 0);

function setStatus(text, tone = 'muted') {
    if (!els.status) {
        return;
    }

    els.status.textContent = text;
    els.status.dataset.tone = tone;
}

function setHint(text) {
    if (els.hint) {
        els.hint.textContent = text;
    }
}

function thresholdFor(emotion) {
    return thresholds[emotion] ?? 0.8;
}

function renderBars(values) {
    if (!els.bars) {
        return;
    }

    Object.entries(values).forEach(([emotion, value]) => {
        const row = els.bars.querySelector(`[data-bar="${emotion}"]`);
        if (!row) {
            return;
        }

        const fill = row.querySelector('[data-fill]');
        const score = row.querySelector('[data-score]');
        const percent = Math.round(value * 100);

        if (fill) {
            fill.style.width = `${Math.min(percent, 100)}%`;
            fill.dataset.active = emotion === target ? 'true' : 'false';
        }
        if (score) {
            score.textContent = `${percent}%`;
        }
    });
}

function resetHold() {
    hold = 0;
    if (els.progress) {
        els.progress.style.width = '0%';
    }
}

function updateProgress() {
    if (els.progress) {
        els.progress.style.width = `${Math.min(100, (hold / holdMs) * 100)}%`;
    }
}

function completionPercent() {
    return totalTargets > 0 ? Math.round((completed.size / totalTargets) * 100) : 0;
}

function renderCompletion() {
    const count = completed.size;
    const percent = completionPercent();

    if (els.completedCount) {
        els.completedCount.textContent = String(count);
    }
    if (els.totalCount) {
        els.totalCount.textContent = String(totalTargets);
    }
    if (els.percent) {
        els.percent.textContent = `${percent}%`;
    }
    if (els.completionBar) {
        els.completionBar.style.width = `${percent}%`;
    }
    if (els.completionTrack) {
        els.completionTrack.setAttribute('aria-valuenow', String(percent));
    }
    if (els.finish) {
        els.finish.classList.toggle('hidden', count < totalTargets || totalTargets === 0);
    }

    els.targetButtons.forEach((button) => {
        button.dataset.completed = completed.has(button.dataset.cameraTarget) ? 'true' : 'false';
    });
}

function nextTarget() {
    const next = (cfg.targets ?? []).find((emotion) => !completed.has(emotion));

    return next ?? target;
}

async function loadModels() {
    if (modelReady) {
        return;
    }

    setStatus('Загружаем модель…');
    await faceapi.nets.tinyFaceDetector.loadFromUri('/models');
    await faceapi.nets.faceExpressionNet.loadFromUri('/models');
    modelReady = true;
    setStatus('Модель готова.');
}

async function startCamera() {
    if (running) {
        return;
    }

    if (submitting) {
        return;
    }

    if (completed.size >= totalTargets) {
        setStatus('Все эмоции пройдены. Завершаем…', 'success');
        await finish();
        return;
    }

    try {
        await loadModels();
    } catch (error) {
        console.error(error);
        setStatus('Не удалось загрузить модель. Проверьте соединение и обновите страницу.', 'error');
        return;
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false,
        });

        els.video.srcObject = stream;
        await els.video.play();

        running = true;
        attempts += 1;
        hold = 0;
        best = 0;
        smoothed = {};
        lastFrame = performance.now();
        els.placeholder?.classList.add('hidden');
        els.result?.classList.add('hidden');
        els.start?.classList.add('hidden');
        els.stop?.classList.remove('hidden');
        if (els.attempts) {
            els.attempts.textContent = String(attempts);
        }
        setStatus('Смотрите в камеру. Держите выражение.');
        setHint('Если лицо не находится — подойдите ближе и добавьте света.');

        loop();
    } catch (error) {
        console.error(error);
        setStatus('Камера недоступна. Разрешите доступ в браузере или пройдите альтернативное задание.', 'error');
    }
}

function stopCamera() {
    running = false;
    if (loopTimer) {
        clearTimeout(loopTimer);
        loopTimer = null;
    }
    stream?.getTracks().forEach((track) => track.stop());
    stream = null;
    els.video.srcObject = null;
    els.stop?.classList.add('hidden');
    els.start?.classList.remove('hidden');
}

function loop() {
    if (!running) {
        return;
    }

    loopTimer = setTimeout(async () => {
        await detect();
        loop();
    }, 150);
}

async function detect() {
    if (!els.video || els.video.readyState < 2) {
        return;
    }

    const now = performance.now();
    const delta = Math.min(300, now - lastFrame);
    lastFrame = now;

    let result = null;
    try {
        result = await faceapi
            .detectSingleFace(els.video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.45 }))
            .withFaceExpressions();
    } catch (error) {
        console.error(error);
        return;
    }

    if (!result) {
        resetHold();
        setHint('Лицо не найдено. Подойдите ближе и повернитесь к свету.');
        return;
    }

    const expressions = result.expressions;
    const values = {};

    Object.keys(labels).forEach((emotion) => {
        const current = expressions[emotion] ?? 0;
        smoothed[emotion] = (smoothed[emotion] ?? current) * smoothFactor + current * (1 - smoothFactor);
        values[emotion] = smoothed[emotion];
    });

    renderBars(values);

    const value = values[target] ?? 0;
    const top = Object.entries(values).sort((a, b) => b[1] - a[1])[0]?.[0];
    const isTargetTop = top === target;
    best = Math.max(best, value);

    if (value >= thresholdFor(target) && isTargetTop) {
        hold += delta;
        updateProgress();
        setHint(`Держите: ${Math.round((hold / holdMs) * 100)}%`);
    } else {
        hold = 0;
        updateProgress();
        setHint(
            isTargetTop
                ? `Уверенность ${Math.round(value * 100)}%. Нужно ${Math.round(thresholdFor(target) * 100)}%.`
                : `Сейчас сильнее читается «${labels[top] ?? top}». Попробуйте нужное выражение.`
        );
    }

    if (hold >= holdMs) {
        await succeed(value);
    }
}

async function succeed(value) {
    running = false;
    if (loopTimer) {
        clearTimeout(loopTimer);
        loopTimer = null;
    }

    const confidence = Math.round(value * 1000) / 10;
    els.result?.classList.remove('hidden');
    if (els.resultValue) {
        els.resultValue.textContent = `${confidence}%`;
    }
    if (els.resultText) {
        els.resultText.textContent = `Выражение «${labels[target] ?? target}» удержано. Сохраняем прогресс…`;
    }
    setStatus('Получилось!', 'success');

    try {
        const data = await saveProgress(target, confidence);
        completed = new Set(Array.isArray(data.completed) ? data.completed : [...completed, target]);
        renderCompletion();
    } catch (error) {
        console.error(error);
        stopCamera();
        els.retry?.classList.remove('hidden');
        setStatus('Не удалось сохранить прогресс. Попробуйте ещё раз.', 'error');
        return;
    }

    if (completed.size >= totalTargets) {
        if (els.resultText) {
            els.resultText.textContent = 'Все эмоции пройдены. Собираем итоговый результат…';
        }
        setStatus('Практикум пройден!', 'success');
        setHint('Все эмоции собраны. Открываем разбор.');
        stopCamera();
        await finish();
        return;
    }

    const remaining = totalTargets - completed.size;
    setHint(`Осталось ${remaining} ${remaining === 1 ? 'эмоция' : remaining < 5 ? 'эмоции' : 'эмоций'}. Следующая цель — «${labels[nextTarget()] ?? nextTarget()}».`);

    setTimeout(() => {
        els.result?.classList.add('hidden');
        selectTarget(nextTarget());
        setHint(`Следующая цель — «${labels[target] ?? target}». Держите выражение.`);
        hold = 0;
        best = 0;
        smoothed = {};
        lastFrame = performance.now();
        running = true;
        setStatus('Следующая эмоция', 'muted');
        loop();
    }, 1400);
}

async function saveProgress(emotion, confidence) {
    const response = await fetch(cfg.progressUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg.csrf,
        },
        body: JSON.stringify({
            target: emotion,
            emotion,
            confidence,
            duration_ms: holdMs,
            attempts: Math.max(attempts, 1),
        }),
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json();
}

async function finish() {
    if (submitting) {
        return;
    }

    submitting = true;

    try {
        const response = await fetch(cfg.submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
            },
            body: '{}',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        window.location.href = data.redirect;
    } catch (error) {
        console.error(error);
        submitting = false;
        els.retry?.classList.remove('hidden');
        setStatus('Не удалось сохранить результат. Попробуйте ещё раз.', 'error');
    }
}

function selectTarget(emotion) {
    target = emotion;
    hold = 0;
    best = 0;
    updateProgress();
    els.targetButtons.forEach((button) => {
        button.dataset.active = button.dataset.cameraTarget === emotion ? 'true' : 'false';
    });
    if (els.threshold) {
        els.threshold.textContent = `${Math.round(thresholdFor(emotion) * 100)}%`;
    }
    if (completed.has(emotion)) {
        setHint('Эта эмоция уже пройдена. Можно повторить или выбрать другую.');
    } else if (attempts > 0) {
        setHint('Новая цель выбрана. Начните заново.');
    }
    els.result?.classList.add('hidden');
    els.retry?.classList.add('hidden');
}

els.targetButtons.forEach((button) => {
    button.addEventListener('click', () => selectTarget(button.dataset.cameraTarget));
});

els.start?.addEventListener('click', startCamera);
els.finish?.addEventListener('click', finish);
els.stop?.addEventListener('click', () => {
    stopCamera();
    setStatus('Остановлено.');
});
els.retry?.addEventListener('click', () => {
    els.retry?.classList.add('hidden');
    submitting = false;
    startCamera();
});

selectTarget(nextTarget());

renderCompletion();

if (!navigator.mediaDevices?.getUserMedia) {
    setStatus('Браузер не поддерживает камеру. Откройте страницу в современном браузере.', 'error');
    els.start?.setAttribute('disabled', 'disabled');
}
