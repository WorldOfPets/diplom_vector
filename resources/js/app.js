import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Chart = Chart;
window.Sortable = Sortable;

window.applyVektorTheme = (theme) => {
    document.documentElement.dataset.theme = theme;

    const meta = document.querySelector('meta[name="theme-color"]');

    if (meta) {
        meta.setAttribute('content', theme === 'dark' ? '#0b0e14' : '#f7f5f0');
    }
};

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.error('Service worker registration failed', error);
        });
    });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('chartCanvas', (config) => ({
        chart: null,

        init() {
            if (!this.$el || !config) {
                return;
            }

            this.chart = new Chart(this.$el, config);
        },

        destroy() {
            this.chart?.destroy();
        },
    }));

    Alpine.data('shortCard', (options) => ({
        liked: Boolean(options.liked),
        likes: Number(options.likes ?? 0),
        viewed: Boolean(options.viewed),
        likeUrl: options.likeUrl,
        viewUrl: options.viewUrl,
        csrf: options.csrf,
        playing: false,
        busy: false,
        progress: 0,
        watched: 0,
        lastTime: null,
        observer: null,

        init() {
            if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            if (window.matchMedia?.('(min-width: 640px)').matches) {
                return;
            }

            if (!('IntersectionObserver' in window)) {
                return;
            }

            this.$nextTick(() => {
                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.play();
                        } else {
                            this.pause();
                        }
                    });
                }, { threshold: 0.65 });

                this.observer.observe(this.$el);

                const rect = this.$el.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    this.play();
                }
            });
        },

        play() {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            const attempt = video.play();
            attempt?.then(() => {
                this.playing = true;
            }).catch(() => {
                this.playing = false;
            });
        },

        pause() {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            video.pause();
            this.playing = false;
            this.lastTime = null;
        },

        toggle() {
            if (this.$refs.video?.paused) {
                this.play();
            } else {
                this.pause();
            }
        },

        async markViewed() {
            if (this.viewed || this.progress < 80 || !this.viewUrl) {
                return;
            }

            this.viewed = true;

            try {
                const response = await fetch(this.viewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ percent: Math.round(this.progress) }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
            } catch (error) {
                console.error(error);
                this.viewed = false;
            }
        },

        onTimeUpdate(event) {
            const video = event.target;

            if (!video?.duration || !Number.isFinite(video.duration)) {
                return;
            }

            const time = video.currentTime;

            if (this.lastTime !== null) {
                const delta = time - this.lastTime;

                if (delta > 0 && delta <= 1.5) {
                    this.watched += delta;
                }
            }

            this.lastTime = time;
            this.progress = Math.min(100, (this.watched / video.duration) * 100);

            if (this.progress >= 80) {
                this.markViewed();
            }
        },

        async like() {
            if (this.busy) {
                return;
            }

            this.busy = true;

            try {
                const response = await fetch(this.likeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: '{}',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                this.liked = Boolean(data.liked);
                this.likes = Number(data.likes ?? this.likes);
            } catch (error) {
                console.error(error);
            } finally {
                this.busy = false;
            }
        },

        destroy() {
            this.observer?.disconnect();
        },
    }));

    Alpine.data('theoryVideo', (options) => ({
        completed: Boolean(options.completed),
        percent: Number(options.initialPercent ?? (options.completed ? 100 : 0)),
        duration: 0,
        watched: 0,
        lastTime: null,
        saving: false,
        handler: null,
        url: options.url,
        csrf: options.csrf,

        init() {
            if (this.completed) {
                this.percent = 100;
                return;
            }

            this.handler = (event) => this.onMessage(event);
            window.addEventListener('message', this.handler);
        },

        destroy() {
            if (this.handler) {
                window.removeEventListener('message', this.handler);
            }
        },

        onMessage(event) {
            const frame = this.$refs.frame;

            if (!frame || event.source !== frame.contentWindow) {
                return;
            }

            let message = event.data;

            if (typeof message === 'string') {
                try {
                    message = JSON.parse(message);
                } catch {
                    return;
                }
            }

            if (!message || typeof message !== 'object') {
                return;
            }

            if (message.type === 'player:durationChange') {
                const duration = Number(message.data?.duration);

                if (Number.isFinite(duration) && duration > 0) {
                    this.duration = duration;
                }

                return;
            }

            if (message.type === 'player:currentTime') {
                this.accumulate(Number(message.data?.time));

                return;
            }

            if (message.type === 'player:playComplete') {
                this.complete();
            }
        },

        accumulate(time) {
            if (!Number.isFinite(time) || time < 0) {
                return;
            }

            if (this.lastTime !== null) {
                const delta = time - this.lastTime;

                if (delta > 0 && delta <= 3.5) {
                    this.watched += delta;
                }
            }

            this.lastTime = time;

            if (this.duration > 0) {
                this.percent = Math.min(100, Math.max(this.percent, Math.round((this.watched / this.duration) * 100)));
            }

            if (this.percent >= 80) {
                this.complete();
            }
        },

        async complete() {
            if (this.completed || this.saving) {
                return;
            }

            this.saving = true;

            try {
                const response = await fetch(this.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ percent: Math.max(80, Math.min(100, Math.round(this.percent))) }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                this.completed = true;
                this.percent = 100;
            } catch (error) {
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('trainerForm', (options) => ({
        response: options.initial ?? {},
        submitting: false,
        error: null,

        async submit() {
            if (this.submitting) {
                return;
            }

            this.submitting = true;
            this.error = null;

            try {
                const response = await fetch(options.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: JSON.stringify({
                        response: this.response,
                        attempt_id: options.attemptId,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'Не удалось сохранить результат.');
                }

                window.location.href = data.redirect;
            } catch (error) {
                this.error = error.message;
                this.submitting = false;
            }
        },
    }));

    Alpine.data('scenario', (config) => ({
        config,
        current: config.start,
        choices: [],
        feedback: null,
        finished: false,
        outcome: null,

        get node() {
            return this.config.nodes?.[this.current] ?? null;
        },

        choose(index) {
            const node = this.node;
            const option = node?.options?.[index];

            if (!option) {
                return;
            }

            this.choices.push(index);
            this.feedback = option.feedback ?? null;
            this.$dispatch('response-patch', { choices: [...this.choices] });

            const next = option.next ? this.config.nodes?.[option.next] : null;

            if (next) {
                this.current = option.next;
                if (!next.options || next.options.length === 0) {
                    this.finished = true;
                    this.outcome = next.outcome ?? null;
                }
            } else {
                this.finished = true;
                this.outcome = node.outcome ?? null;
            }
        },

        restart() {
            this.current = this.config.start;
            this.choices = [];
            this.feedback = null;
            this.finished = false;
            this.outcome = null;
            this.$dispatch('response-patch', { choices: [] });
        },
    }));

    Alpine.data('goNoGo', (config) => ({
        total: config.trials ?? 30,
        goRatio: config.go_ratio ?? 0.7,
        windowMs: config.response_window_ms ?? 1100,
        goLabel: config.go_label ?? 'Спокойно',
        nogoLabel: config.nogo_label ?? 'Триггер',
        goHint: config.go_hint ?? 'Нажмите',
        nogoHint: config.nogo_hint ?? 'Не нажимайте',
        running: false,
        finished: false,
        index: 0,
        current: null,
        showSignal: false,
        reacted: false,
        rt: null,
        startedAt: 0,
        trials: [],
        handler: null,

        init() {
            this.handler = (event) => {
                if (event.code === 'Space' || event.key === ' ') {
                    event.preventDefault();
                    this.react();
                }
            };
            window.addEventListener('keydown', this.handler);
        },

        destroy() {
            if (this.handler) {
                window.removeEventListener('keydown', this.handler);
            }
        },

        async start() {
            if (this.running) {
                return;
            }

            this.running = true;
            this.finished = false;
            this.trials = [];
            this.index = 0;

            for (let i = 0; i < this.total; i++) {
                this.index = i + 1;
                await this.runTrial();
                await this.sleep(260);
            }

            this.running = false;
            this.finished = true;
            this.showSignal = false;
            this.$dispatch('response-patch', { trials: this.trials });
        },

        runTrial() {
            return new Promise((resolve) => {
                const isGo = Math.random() < this.goRatio;
                this.current = isGo ? 'go' : 'nogo';
                this.reacted = false;
                this.rt = null;
                this.showSignal = true;
                this.startedAt = performance.now();

                const timer = setTimeout(() => {
                    this.trials.push({
                        type: this.current,
                        reacted: this.reacted,
                        rt: this.reacted ? this.rt : null,
                    });
                    this.showSignal = false;
                    resolve();
                }, this.windowMs);

                this._trialTimer = timer;
            });
        },

        react() {
            if (!this.running || !this.showSignal || this.reacted) {
                return;
            }

            this.reacted = true;
            this.rt = Math.round(performance.now() - this.startedAt);
        },

        sleep(ms) {
            return new Promise((resolve) => setTimeout(resolve, ms));
        },
    }));

    Alpine.data('breathing', (config) => ({
        pattern: config.pattern ?? [],
        cycles: config.cycles ?? 4,
        running: false,
        finished: false,
        cycle: 0,
        phaseIndex: 0,
        remaining: 0,
        before: 6,
        after: 6,
        timer: null,

        get phase() {
            return this.pattern[this.phaseIndex] ?? null;
        },

        get totalSeconds() {
            return this.pattern.reduce((sum, item) => sum + Number(item.seconds ?? 0), 0) * this.cycles;
        },

        start() {
            if (this.running) {
                return;
            }

            this.running = true;
            this.finished = false;
            this.cycle = 0;
            this.phaseIndex = 0;
            this.remaining = Number(this.phase?.seconds ?? 0);

            this.timer = setInterval(() => this.tick(), 1000);
        },

        tick() {
            if (!this.running) {
                return;
            }

            this.remaining -= 1;

            if (this.remaining > 0) {
                return;
            }

            this.phaseIndex += 1;

            if (this.phaseIndex >= this.pattern.length) {
                this.phaseIndex = 0;
                this.cycle += 1;
            }

            if (this.cycle >= this.cycles) {
                this.stop(true);
                return;
            }

            this.remaining = Number(this.pattern[this.phaseIndex]?.seconds ?? 0);
        },

        stop(completed = false) {
            this.running = false;
            clearInterval(this.timer);
            this.timer = null;

            if (completed) {
                this.finished = true;
                this.after = this.before;
                this.$dispatch('response-patch', {
                    cycles_completed: this.cycles,
                    before: Number(this.before),
                    after: Number(this.after),
                });
            }
        },

        updateAfter() {
            this.$dispatch('response-patch', {
                cycles_completed: this.finished ? this.cycles : this.cycle,
                before: Number(this.before),
                after: Number(this.after),
            });
        },
    }));

    Alpine.data('schemaEditor', (schema, initial) => ({
        schema: schema ?? [],
        data: (initial && !Array.isArray(initial)) ? initial : {},
        jsonText: {},
        jsonError: {},

        init() {
            this.schema.forEach((field) => {
                if (field.type === 'json') {
                    if (typeof this.data[field.key] !== 'object' || this.data[field.key] === null || Array.isArray(this.data[field.key])) {
                        this.data[field.key] = {};
                    }
                    this.jsonText[field.key] = JSON.stringify(this.data[field.key], null, 2);
                } else if (field.type === 'repeater') {
                    if (!Array.isArray(this.data[field.key])) {
                        this.data[field.key] = [];
                    }
                } else if (this.data[field.key] === undefined) {
                    this.data[field.key] = this.defaultFor(field);
                }
            });
        },

        defaultFor(field) {
            if (field.type === 'boolean') {
                return false;
            }
            if (field.type === 'number') {
                return field.default ?? 0;
            }
            return field.default ?? '';
        },

        blank(fieldKey) {
            const field = this.schema.find((entry) => entry.key === fieldKey);
            const item = {};
            (field?.fields ?? []).forEach((sub) => {
                item[sub.key] = this.defaultFor(sub);
            });
            return item;
        },

        add(fieldKey) {
            if (Array.isArray(this.data[fieldKey])) {
                this.data[fieldKey].push(this.blank(fieldKey));
            }
        },

        remove(fieldKey, index) {
            if (Array.isArray(this.data[fieldKey])) {
                this.data[fieldKey].splice(index, 1);
            }
        },

        updateJson(fieldKey, value) {
            try {
                const parsed = JSON.parse(value);
                if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    throw new Error('object expected');
                }
                this.data[fieldKey] = parsed;
                this.jsonError[fieldKey] = null;
            } catch (error) {
                this.jsonError[fieldKey] = 'Нужен корректный JSON-объект';
            }
        },

        updateItemJson(fieldKey, index, subKey, value) {
            try {
                const parsed = JSON.parse(value);
                if (parsed === null || typeof parsed !== 'object') {
                    throw new Error('object expected');
                }
                this.data[fieldKey][index][subKey] = parsed;
            } catch (error) {
                // keep the last valid value
            }
        },
    }));

    Alpine.data('testItemBuilder', (type, initialConfig, initialKey) => ({
        type,
        initialConfig: (initialConfig && !Array.isArray(initialConfig)) ? initialConfig : {},
        initialKey: (initialKey && !Array.isArray(initialKey)) ? initialKey : {},
        options: ((initialConfig?.options) ?? []).map((option) => ({
            id: option.id ?? '',
            label: option.label ?? '',
            weight: (initialKey?.weights?.[option.id] ?? 0),
            scores: option.scores ?? {},
        })),
        emotions: ((initialConfig?.emotions) ?? []).map((emotion) => ({
            id: emotion.id ?? '',
            label: emotion.label ?? '',
            weight: (initialKey?.emotions?.[emotion.id] ?? 0),
        })),
        items: ((initialConfig?.items) ?? []).map((item) => ({ id: item.id ?? '', label: item.label ?? '' })),
        left: ((initialConfig?.left) ?? []).map((item) => ({ id: item.id ?? '', label: item.label ?? '' })),
        right: ((initialConfig?.right) ?? []).map((item) => ({ id: item.id ?? '', label: item.label ?? '' })),
        pairs: (initialKey?.pairs ?? {}),
        categories: ((initialConfig?.categories) ?? []).map((category) => ({
            key: category.key ?? '',
            label: category.label ?? '',
            color: category.color ?? '#2D5BFF',
        })),
        mode: initialConfig?.mode ?? 'best_worst',
        imageAlt: initialConfig?.image_alt ?? '',
        maxChoices: initialConfig?.max ?? 3,
        intensityTarget: initialKey?.intensity_target ?? 3,
        intensityTolerance: initialKey?.intensity_tolerance ?? 1,
        best: initialKey?.best ?? '',
        worst: initialKey?.worst ?? '',
        subscale: initialConfig?.subscale ?? 'internal',
        reverse: initialConfig?.reverse ?? initialKey?.reverse ?? false,
        min: initialConfig?.min ?? 1,
        max: initialConfig?.max ?? 5,
        scaleTarget: initialKey?.target ?? null,

        addOption() {
            this.options.push({ id: 'o' + (this.options.length + 1), label: '', weight: 0, scores: {} });
        },
        removeOption(index) {
            this.options.splice(index, 1);
        },
        addEmotion() {
            this.emotions.push({ id: 'e' + (this.emotions.length + 1), label: '', weight: 0 });
        },
        removeEmotion(index) {
            this.emotions.splice(index, 1);
        },
        addItem() {
            this.items.push({ id: 'i' + (this.items.length + 1), label: '' });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        moveItem(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.items.length) {
                return;
            }
            [this.items[index], this.items[target]] = [this.items[target], this.items[index]];
        },
        addLeft() {
            this.left.push({ id: 'l' + (this.left.length + 1), label: '' });
        },
        removeLeft(index) {
            this.left.splice(index, 1);
        },
        addRight() {
            this.right.push({ id: 'r' + (this.right.length + 1), label: '' });
        },
        removeRight(index) {
            this.right.splice(index, 1);
        },
        addCategory() {
            this.categories.push({ key: 'c' + (this.categories.length + 1), label: '', color: '#2D5BFF' });
        },
        removeCategory(index) {
            this.categories.splice(index, 1);
        },
        updateOptionScores(index, value) {
            try {
                const parsed = JSON.parse(value);
                if (parsed === null || typeof parsed !== 'object') {
                    throw new Error('object expected');
                }
                this.options[index].scores = parsed;
            } catch (error) {
                // keep the last valid value
            }
        },
        get configPayload() {
            const config = { ...this.initialConfig };
            delete config.options;
            delete config.emotions;
            delete config.items;
            delete config.left;
            delete config.right;
            delete config.categories;

            if (this.type === 'single' || this.type === 'multi') {
                config.options = this.options.map((option) => ({ id: option.id, label: option.label }));
            }

            if (this.type === 'scene' || this.type === 'image_choice') {
                config.emotions = this.emotions.map((emotion) => ({ id: emotion.id, label: emotion.label }));
                config.max = Number(this.maxChoices);
                config.image_alt = this.imageAlt;
            }

            if (this.type === 'best_worst') {
                config.mode = this.mode;
                config.options = this.options.map((option) => ({
                    id: option.id,
                    label: option.label,
                    scores: option.scores ?? {},
                }));
                config.categories = this.categories;
            }

            if (this.type === 'ordering') {
                config.items = this.items.map((item) => ({ id: item.id, label: item.label }));
            }

            if (this.type === 'motivation') {
                config.subscale = this.subscale;
                config.reverse = Boolean(this.reverse);
                config.min = Number(this.min);
                config.max = Number(this.max);
            }

            if (this.type === 'matching') {
                config.left = this.left.map((item) => ({ id: item.id, label: item.label }));
                config.right = this.right.map((item) => ({ id: item.id, label: item.label }));
            }

            if (this.type === 'scale') {
                config.min = Number(this.min);
                config.max = Number(this.max);
            }

            return JSON.stringify(config);
        },
        get keyPayload() {
            const key = { ...this.initialKey };

            if (this.type === 'single' || this.type === 'multi') {
                key.weights = Object.fromEntries(this.options.map((option) => [option.id, Number(option.weight)]));
            }

            if (this.type === 'scene' || this.type === 'image_choice') {
                key.emotions = Object.fromEntries(this.emotions.map((emotion) => [emotion.id, Number(emotion.weight)]));
                key.intensity_target = Number(this.intensityTarget);
                key.intensity_tolerance = Number(this.intensityTolerance);
                key.max = this.initialKey.max ?? 1.5;
                key.penalty = this.initialKey.penalty ?? 0.2;
            }

            if (this.type === 'best_worst') {
                key.best = this.best;
                key.worst = this.worst;
            }

            if (this.type === 'ordering') {
                key.order = this.items.map((item) => item.id);
            }

            if (this.type === 'motivation') {
                key.reverse = Boolean(this.reverse);
            }

            if (this.type === 'matching') {
                key.pairs = this.pairs;
            }

            if (this.type === 'scale') {
                key.target = this.scaleTarget;
            }

            return JSON.stringify(key);
        },
    }));

    Alpine.data('testRunner', (options) => ({
        items: options.items ?? [],
        answers: {},
        index: 0,
        timeLimit: options.timeLimit ?? null,
        saving: false,
        submitting: false,
        error: null,
        confirmSubmit: false,
        startedAt: Date.now(),
        elapsed: 0,
        itemStartedAt: Date.now(),
        timer: null,
        autosaveTimer: null,

        init() {
            const initial = options.initial ?? {};
            const answers = {};

            this.items.forEach((item) => {
                answers[item.id] = {
                    ...this.defaultFor(item),
                    ...(initial[item.id] ?? {}),
                };
            });

            this.answers = answers;
            this.timer = setInterval(() => {
                this.elapsed = Math.floor((Date.now() - this.startedAt) / 1000);
            }, 1000);
            this.autosaveTimer = setInterval(() => this.saveDraft(), 12000);
        },

        destroy() {
            clearInterval(this.timer);
            clearInterval(this.autosaveTimer);
        },

        defaultFor(item) {
            switch (item.type) {
                case 'single':
                    return { value: null };
                case 'multi':
                    return { values: [] };
                case 'scene':
                case 'image_choice':
                    return { emotions: [], intensity: 3 };
                case 'best_worst':
                    return { best: null, worst: null };
                case 'ordering':
                    return { order: (item.config?.items ?? []).map((entry) => entry.id) };
                case 'matching':
                    return { pairs: {} };
                case 'scale':
                    return { value: item.config?.min ?? 1 };
                default:
                    return { value: null };
            }
        },

        get current() {
            return this.items[this.index] ?? null;
        },

        get progress() {
            return this.items.length === 0 ? 0 : Math.round(((this.index + 1) / this.items.length) * 100);
        },

        get answeredCount() {
            return this.items.filter((item) => this.isAnswered(item)).length;
        },

        get unansweredCount() {
            return this.items.length - this.answeredCount;
        },

        get isLast() {
            return this.index >= this.items.length - 1;
        },

        get elapsedLabel() {
            const minutes = Math.floor(this.elapsed / 60);
            const seconds = this.elapsed % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        get remainingLabel() {
            if (!options.timeLimit) {
                return null;
            }

            const remaining = Math.max(0, options.timeLimit * 60 - this.elapsed);
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },

        orderedItems(item) {
            const order = this.answers[item.id]?.order ?? [];
            const byId = Object.fromEntries((item.config?.items ?? []).map((entry) => [entry.id, entry]));

            return order.map((id) => byId[id]).filter(Boolean);
        },

        branchLabel(branch) {
            return options.branchLabels?.[branch] ?? branch;
        },

        typeLabel(type) {
            return {
                single: 'один ответ',
                multi: 'несколько ответов',
                scene: 'сцена',
                image_choice: 'сцена',
                best_worst: 'лучшая и худшая',
                ordering: 'порядок',
                matching: 'сопоставление',
                scale: 'шкала',
                motivation: 'оценка',
            }[type] ?? type;
        },

        mediaUrl(path) {
            if (!path) {
                return null;
            }

            return path.startsWith('http') ? path : `/${path.replace(/^\/+/, '')}`;
        },
        isAnswered(item) {
            const answer = this.answers[item.id] ?? {};

            switch (item.type) {
                case 'single':
                    return answer.value !== null && answer.value !== '';
                case 'multi':
                    return (answer.values ?? []).length > 0;
                case 'scene':
                case 'image_choice':
                    return (answer.emotions ?? []).length > 0;
                case 'best_worst':
                    return Boolean(answer.best && answer.worst && answer.best !== answer.worst);
                case 'ordering':
                    return (answer.order ?? []).length === (item.config?.items ?? []).length;
                case 'matching':
                    return Object.keys(answer.pairs ?? {}).length === (item.config?.left ?? []).length;
                case 'scale':
                    return answer.value !== null && answer.value !== '';
                default:
                    return answer.value !== null && answer.value !== '';
            }
        },

        select(value) {
            this.answers[this.current.id].value = value;
        },

        toggleArray(field, id, max = null) {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            const values = [...(answer[field] ?? [])];
            const index = values.indexOf(id);

            if (index >= 0) {
                values.splice(index, 1);
            } else if (!max || values.length < max) {
                values.push(id);
            }

            answer[field] = values;
        },

        setBest(id) {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            answer.best = id;
            if (answer.worst === id) {
                answer.worst = null;
            }
        },

        setWorst(id) {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            answer.worst = id;
            if (answer.best === id) {
                answer.best = null;
            }
        },

        move(from, delta) {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            const order = [...(answer.order ?? [])];
            const to = from + delta;

            if (to < 0 || to >= order.length) {
                return;
            }

            [order[from], order[to]] = [order[to], order[from]];
            answer.order = order;
        },

        next() {
            this.markTime();

            if (!this.isLast) {
                this.index += 1;
                this.itemStartedAt = Date.now();
                return;
            }

            this.requestSubmit();
        },

        prev() {
            this.markTime();

            if (this.index > 0) {
                this.index -= 1;
                this.itemStartedAt = Date.now();
            }
        },

        markTime() {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            if (answer) {
                answer.ms = Math.max(0, Date.now() - this.itemStartedAt);
            }
        },

        requestSubmit() {
            if (this.unansweredCount > 0) {
                this.confirmSubmit = true;
                return;
            }

            this.submit();
        },

        async saveDraft() {
            if (this.submitting) {
                return;
            }

            this.saving = true;

            try {
                await fetch(options.autosaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: JSON.stringify({ answers: this.answers }),
                });
            } catch (error) {
                console.error(error);
            } finally {
                this.saving = false;
            }
        },

        async submit() {
            if (this.submitting) {
                return;
            }

            this.markTime();
            this.confirmSubmit = false;
            this.submitting = true;
            this.error = null;

            try {
                const response = await fetch(options.submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: JSON.stringify({ answers: this.answers }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'Не удалось завершить тест.');
                }

                window.location.href = data.redirect;
            } catch (error) {
                this.error = error.message;
                this.submitting = false;
            }
        },
    }));
});

Alpine.start();
