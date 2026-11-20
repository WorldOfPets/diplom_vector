import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Chart = Chart;
window.Sortable = Sortable;

window.vektorInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.vektorInstallPrompt = event;
    window.dispatchEvent(new Event('vektor-install-available'));
});

window.addEventListener('appinstalled', () => {
    window.vektorInstallPrompt = null;
    window.dispatchEvent(new Event('vektor-installed'));
});

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

// Chrome на Android иногда оставляет fixed-слой в размерах вьюпорта, которые
// были до ухода PWA в фон (например, после клавиатуры в дневнике). Пересборка
// layout возвращает нижнее меню к реальному низу экрана.
const resyncMobileNav = () => {
    const nav = document.querySelector('[data-mobile-nav]');

    if (!nav) {
        return;
    }

    nav.style.position = 'static';
    void nav.offsetHeight;
    nav.style.position = '';
};

let navResyncQueued = false;

const scheduleNavResync = () => {
    if (navResyncQueued) {
        return;
    }

    navResyncQueued = true;

    requestAnimationFrame(() => {
        navResyncQueued = false;
        resyncMobileNav();
    });
};

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        scheduleNavResync();
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        return;
    }

    scheduleNavResync();
    setTimeout(scheduleNavResync, 300);
});

window.visualViewport?.addEventListener('resize', scheduleNavResync);

let navResizeTimer = null;

window.addEventListener('resize', () => {
    clearTimeout(navResizeTimer);
    navResizeTimer = setTimeout(scheduleNavResync, 250);
});

window.addEventListener('orientationchange', () => setTimeout(scheduleNavResync, 200));

document.addEventListener('alpine:init', () => {
    Alpine.data('pwaInstall', () => ({
        deferredPrompt: null,
        visible: false,
        helpOpen: false,
        isIos: false,
        onAvailable: null,
        onInstalled: null,

        init() {
            const standalone = window.matchMedia?.('(display-mode: standalone)').matches
                || window.navigator.standalone === true;

            this.isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

            if (standalone) {
                return;
            }

            this.visible = true;
            this.deferredPrompt = window.vektorInstallPrompt;

            this.onAvailable = () => {
                this.deferredPrompt = window.vektorInstallPrompt;
                this.visible = true;
            };

            this.onInstalled = () => {
                this.visible = false;
                this.helpOpen = false;
                this.deferredPrompt = null;
            };

            window.addEventListener('vektor-install-available', this.onAvailable);
            window.addEventListener('vektor-installed', this.onInstalled);
        },

        destroy() {
            if (this.onAvailable) {
                window.removeEventListener('vektor-install-available', this.onAvailable);
            }

            if (this.onInstalled) {
                window.removeEventListener('vektor-installed', this.onInstalled);
            }
        },

        async install() {
            if (!this.deferredPrompt) {
                this.helpOpen = true;

                return;
            }

            const prompt = this.deferredPrompt;
            this.deferredPrompt = null;
            window.vektorInstallPrompt = null;

            try {
                await prompt.prompt();
                const choice = await prompt.userChoice;

                if (choice?.outcome === 'accepted') {
                    this.visible = false;
                }
            } catch (error) {
                console.error(error);
                this.helpOpen = true;
            }
        },

        closeHelp() {
            this.helpOpen = false;
        },
    }));

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

    Alpine.data('shortsFeed', (options) => ({
        count: Number(options.count ?? 0),
        active: 0,

        onScroll() {
            const feed = this.$refs.feed;

            if (!feed?.clientHeight) {
                return;
            }

            this.active = Math.max(0, Math.min(this.count - 1, Math.round(feed.scrollTop / feed.clientHeight)));
        },

        go(index) {
            const feed = this.$refs.feed;

            if (!feed) {
                return;
            }

            const target = Math.max(0, Math.min(this.count - 1, index));

            feed.scrollTo({
                top: target * feed.clientHeight,
                behavior: 'smooth',
            });
        },
    }));

    Alpine.data('shortCard', (options) => ({
        id: options.id,
        index: Number(options.index ?? 0),
        liked: Boolean(options.liked),
        likes: Number(options.likes ?? 0),
        viewed: Boolean(options.viewed),
        hasAudio: Boolean(options.hasAudio),
        muted: true,
        likeUrl: options.likeUrl,
        viewUrl: options.viewUrl,
        csrf: options.csrf,
        playing: false,
        busy: false,
        progress: 0,
        watched: 0,
        lastTime: null,
        observer: null,
        onOtherPlay: null,
        onMuteChange: null,

        init() {
            try {
                const storedMuted = window.localStorage.getItem('vektor.shorts.muted');
                this.muted = storedMuted === null ? true : storedMuted === '1';
            } catch {
                this.muted = true;
            }

            if (this.viewed) {
                this.progress = 100;
            }

            this.onOtherPlay = (event) => {
                if (event.detail?.id !== this.id) {
                    this.pause();
                }
            };

            this.onMuteChange = (event) => {
                this.muted = Boolean(event.detail?.muted);

                if (this.$refs.video) {
                    this.$refs.video.muted = this.muted;
                }
            };

            window.addEventListener('short-playing', this.onOtherPlay);
            window.addEventListener('short-muted', this.onMuteChange);

            if (!('IntersectionObserver' in window)) {
                return;
            }

            this.$nextTick(() => {
                const feed = this.$el.closest('[data-shorts-feed]');

                this.observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            window.dispatchEvent(new CustomEvent('short-active', {
                                detail: { id: this.id, index: this.index },
                            }));

                            if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
                                return;
                            }

                            this.play();
                        } else {
                            this.pause();
                        }
                    });
                }, {
                    root: feed instanceof HTMLElement ? feed : null,
                    threshold: 0.65,
                });

                this.observer.observe(this.$el);
            });
        },

        play() {
            const video = this.$refs.video;
            if (!video) {
                return;
            }

            window.dispatchEvent(new CustomEvent('short-playing', {
                detail: { id: this.id },
            }));

            video.muted = this.muted;

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

        toggleMute() {
            this.muted = !this.muted;

            try {
                window.localStorage.setItem('vektor.shorts.muted', this.muted ? '1' : '0');
            } catch {
                // localStorage can be unavailable in private mode.
            }

            window.dispatchEvent(new CustomEvent('short-muted', {
                detail: { muted: this.muted },
            }));

            if (this.$refs.video) {
                this.$refs.video.muted = this.muted;
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

            if (this.onOtherPlay) {
                window.removeEventListener('short-playing', this.onOtherPlay);
            }

            if (this.onMuteChange) {
                window.removeEventListener('short-muted', this.onMuteChange);
            }
        },
    }));

    Alpine.data('theoryVideo', (options) => ({
        completed: Boolean(options.completed),
        percent: Number(options.initialPercent ?? (options.completed ? 100 : 0)),
        duration: 0,
        watched: 0,
        lastTime: null,
        saving: false,
        soundOn: true,
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

            if (message.type === 'player:mute') {
                this.soundOn = false;
                this.lastTime = null;

                return;
            }

            if (message.type === 'player:unmute') {
                this.soundOn = true;

                return;
            }

            if (message.type === 'player:currentTime') {
                const muted = message.data?.muted;
                const volume = Number(message.data?.volume);

                if (muted === true || (Number.isFinite(volume) && volume === 0)) {
                    this.soundOn = false;
                    this.lastTime = null;

                    return;
                }

                if (muted === false || (Number.isFinite(volume) && volume > 0)) {
                    this.soundOn = true;
                }

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
                window.dispatchEvent(new CustomEvent('theory-video-completed'));
            } catch (error) {
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('theoryLocalVideo', (options) => ({
        completed: Boolean(options.completed),
        percent: Number(options.initialPercent ?? (options.completed ? 100 : 0)),
        watched: 0,
        lastTime: null,
        saving: false,
        soundOn: true,
        url: options.url,
        csrf: options.csrf,

        onVolumeChange(event) {
            this.soundOn = !event.target.muted && event.target.volume > 0;

            if (!this.soundOn) {
                this.lastTime = null;
            }
        },

        onTimeUpdate(event) {
            if (this.completed) {
                return;
            }

            const video = event.target;

            if (!video?.duration || !Number.isFinite(video.duration)) {
                return;
            }

            if (video.muted || video.volume === 0) {
                this.soundOn = false;
                this.lastTime = null;

                return;
            }

            this.soundOn = true;
            const time = video.currentTime;

            if (this.lastTime !== null) {
                const delta = time - this.lastTime;

                if (delta > 0 && delta <= 1.5) {
                    this.watched += delta;
                }
            }

            this.lastTime = time;

            const current = Math.min(100, (this.watched / video.duration) * 100);
            this.percent = Math.max(this.percent, Math.round(current));

            if (current >= 80) {
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
                    body: JSON.stringify({ percent: 80 }),
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                this.completed = true;
                this.percent = 100;
                window.dispatchEvent(new CustomEvent('theory-video-completed'));
            } catch (error) {
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('theorySlides', (options) => ({
        slides: options.slides ?? [],
        index: 0,
        viewed: options.viewed ?? {},
        minSeconds: Number(options.minSeconds ?? 15),
        seconds: 0,
        completed: options.completed === true,
        timer: null,
        saving: false,

        init() {
            this.startTimer();
            this.preloadNext();
        },

        destroy() {
            this.stopTimer();
        },

        get total() {
            return this.slides.length;
        },

        get current() {
            return this.slides[this.index] ?? '';
        },

        get isViewed() {
            return Number(this.viewed[this.index] ?? 0) >= this.minSeconds;
        },

        get viewedCount() {
            return Object.values(this.viewed).filter((value) => Number(value) >= this.minSeconds).length;
        },

        get ready() {
            return this.seconds >= this.minSeconds;
        },

        get remaining() {
            return Math.max(0, this.minSeconds - this.seconds);
        },

        startTimer() {
            this.stopTimer();
            this.seconds = this.isViewed ? this.minSeconds : 0;

            if (this.isViewed) {
                return;
            }

            this.timer = setInterval(() => {
                if (document.hidden) {
                    return;
                }

                this.seconds = Math.min(this.minSeconds, this.seconds + 1);

                if (this.seconds >= this.minSeconds) {
                    this.markViewed();
                }
            }, 1000);
        },

        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        go(delta) {
            const next = this.index + delta;

            if (next < 0 || next >= this.total) {
                return;
            }

            if (delta > 0 && !this.ready) {
                return;
            }

            this.index = next;
            this.startTimer();
            this.preloadNext();
        },

        preloadNext() {
            const next = this.slides[this.index + 1];

            if (next) {
                const image = new Image();
                image.src = next;
            }
        },

        async markViewed() {
            this.stopTimer();

            const index = this.index;
            this.viewed = { ...this.viewed, [index]: this.minSeconds };

            if (this.saving) {
                return;
            }

            this.saving = true;

            try {
                const response = await fetch(options.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': options.csrf,
                    },
                    body: JSON.stringify({ index, seconds: this.minSeconds }),
                });

                const data = await response.json();

                if (data.completed) {
                    this.completed = true;
                    this.$dispatch('theory-slides-completed');
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('theoryTask', (options) => ({
        url: options.url,
        csrf: options.csrf,
        options: (options.options ?? []).map((word) => ({ word, used: false })),
        blankIds: options.blankIds ?? [],
        answers: options.completed ? { ...(options.filled ?? {}) } : {},
        selected: null,
        completed: Boolean(options.completed),
        busy: false,
        error: '',

        init() {
            if (!this.completed) {
                return;
            }

            const filled = Object.values(this.answers);

            this.options.forEach((option) => {
                option.used = filled.includes(option.word);
            });
        },

        select(option) {
            if (this.completed || option.used) {
                return;
            }

            this.selected = this.selected === option.word ? null : option.word;
            this.error = '';
        },

        dragStart(option, event) {
            if (this.completed || option.used) {
                event.preventDefault();
                return;
            }

            this.selected = option.word;
            event.dataTransfer.setData('text/plain', option.word);
            event.dataTransfer.effectAllowed = 'move';
        },

        dropBlank(id, event) {
            this.place(id, event.dataTransfer.getData('text/plain'));
        },

        tapBlank(id) {
            if (this.completed) {
                return;
            }

            if (this.selected) {
                this.place(id, this.selected);

                return;
            }

            if (this.answers[id]) {
                this.remove(id);
            }
        },

        place(id, word) {
            if (!word || this.completed || this.answers[id] === word) {
                return;
            }

            const option = this.options.find((item) => item.word === word && !item.used);

            if (!option) {
                return;
            }

            if (this.answers[id]) {
                this.release(this.answers[id]);
            }

            this.answers[id] = word;
            option.used = true;
            this.selected = null;
            this.error = '';
        },

        remove(id) {
            if (this.completed || !this.answers[id]) {
                return;
            }

            this.release(this.answers[id]);

            const next = { ...this.answers };
            delete next[id];
            this.answers = next;
            this.error = '';
        },

        release(word) {
            const option = this.options.find((item) => item.word === word);

            if (option) {
                option.used = false;
            }
        },

        async check() {
            if (this.busy || this.completed) {
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const response = await fetch(this.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ answers: this.answers }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    this.error = data.message || 'Проверьте слова и попробуйте ещё раз.';

                    return;
                }

                this.completed = true;
                this.options.forEach((option) => {
                    option.used = true;
                });
                window.dispatchEvent(new CustomEvent('theory-task-completed'));
            } catch (error) {
                console.error(error);
                this.error = 'Не удалось проверить задание. Попробуйте ещё раз.';
            } finally {
                this.busy = false;
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

    Alpine.data('pairMatcher', (options) => ({
        left: options.left ?? [],
        right: options.right ?? [],
        pairs: Object.fromEntries(
            Object.entries(options.pairs ?? {}).filter(([, value]) => typeof value === 'string' && value !== ''),
        ),
        active: null,

        init() {
            this.$dispatch('response-patch', { pairs: { ...this.pairs } });
        },

        get assignedCount() {
            return Object.keys(this.pairs).length;
        },

        get total() {
            return this.left.length;
        },

        isUsed(rightId) {
            return Object.values(this.pairs).includes(rightId);
        },

        chooseLeft(leftId) {
            this.active = this.active === leftId ? null : leftId;
        },

        assign(rightId) {
            if (!this.active || this.isUsed(rightId)) {
                return;
            }

            this.pairs = { ...this.pairs, [this.active]: rightId };
            this.active = null;
            this.$dispatch('response-patch', { pairs: { ...this.pairs } });
        },

        clear(leftId) {
            const next = { ...this.pairs };
            delete next[leftId];
            this.pairs = next;
            this.$dispatch('response-patch', { pairs: { ...next } });
        },

        labelFor(rightId) {
            return this.right.find((item) => item.id === rightId)?.label ?? '';
        },
    }));

    Alpine.data('categorizeBoard', (options) => ({
        buckets: options.buckets ?? [],
        activeItem: null,
        counts: options.counts ?? {},
        poolCount: options.poolCount ?? 0,

        init() {
            this.sync();
            this.$nextTick(() => this.mount());
        },

        mount() {
            if (!window.Sortable) {
                return;
            }

            const shared = {
                group: 'categorize',
                animation: 150,
                ghostClass: 'opacity-40',
                draggable: '[data-cat-item]',
                forceFallback: true,
                fallbackClass: 'categorize-drag',
                fallbackTolerance: 4,
                onEnd: () => this.sync(),
            };

            if (this.$refs.pool) {
                Sortable.create(this.$refs.pool, { ...shared, sort: false });
            }

            this.buckets.forEach((bucket) => {
                const element = this.bucketElement(bucket.id);
                if (element) {
                    Sortable.create(element, shared);
                }
            });
        },

        bucketElement(id) {
            return Array.from(this.$root.querySelectorAll('[data-bucket]'))
                .find((element) => element.dataset.bucket === id) ?? null;
        },

        itemNode(itemId) {
            return this.nodes().find((node) => node.dataset.catItem === itemId) ?? null;
        },

        nodes() {
            return Array.from(this.$root.querySelectorAll('[data-cat-item]'));
        },

        sync() {
            const assigned = {};

            this.nodes().forEach((node) => {
                const bucket = node.closest('[data-bucket]');
                if (bucket?.dataset.bucket) {
                    assigned[node.dataset.catItem] = bucket.dataset.bucket;
                }
            });

            this.counts = this.buckets.reduce((accumulator, bucket) => {
                accumulator[bucket.id] = Object.values(assigned).filter((value) => value === bucket.id).length;

                return accumulator;
            }, {});

            this.poolCount = this.nodes().filter((node) => !node.closest('[data-bucket]')?.dataset.bucket).length;

            this.$dispatch('response-patch', { buckets: assigned });
        },

        select(itemId) {
            this.activeItem = this.activeItem === itemId ? null : itemId;
        },

        place(bucketId) {
            if (!this.activeItem) {
                return;
            }

            const node = this.itemNode(this.activeItem);
            const target = this.bucketElement(bucketId);

            if (!node || !target) {
                return;
            }

            target.appendChild(node);
            this.activeItem = null;
            this.sync();
        },

        returnToPool(itemId) {
            if (!itemId || !this.$refs.pool) {
                return;
            }

            const node = this.itemNode(itemId);

            if (!node) {
                return;
            }

            this.$refs.pool.appendChild(node);
            this.activeItem = null;
            this.sync();
        },
    }));

    Alpine.data('coinJar', (options) => ({
        budget: options.budget ?? 100,
        coinValue: options.coinValue ?? 5,
        items: options.items ?? [],
        amounts: options.amounts ?? {},
        spentAmount: 0,
        remainingAmount: options.budget ?? 100,
        sortables: [],

        init() {
            this.refreshTotals();
            this.$nextTick(() => this.mount());
            this.$dispatch('response-patch', { amounts: { ...this.amounts } });
        },

        mount() {
            if (!window.Sortable) {
                return;
            }

            const shared = {
                group: 'coins',
                animation: 150,
                draggable: '[data-coin]',
                onEnd: () => this.sync(),
            };

            if (this.$refs.purse) {
                this.sortables.push(Sortable.create(this.$refs.purse, { ...shared, sort: false }));
            }

            this.targets().forEach((target) => {
                this.sortables.push(Sortable.create(target, shared));
            });
        },

        destroy() {
            this.sortables.forEach((sortable) => sortable.destroy());
        },

        targets() {
            return Array.from(this.$root.querySelectorAll('[data-coin-target]'));
        },

        target(id) {
            return this.targets().find((target) => target.dataset.coinTarget === id) ?? null;
        },

        count(id) {
            const target = this.target(id);

            return target ? target.querySelectorAll('[data-coin]').length : 0;
        },

        refreshTotals() {
            const spent = this.items.reduce(
                (sum, item) => sum + Number(this.amounts[item.id] ?? 0),
                0,
            );

            this.spentAmount = spent;
            this.remainingAmount = Math.max(0, this.budget - spent);
        },

        sync() {
            const amounts = {};

            this.items.forEach((item) => {
                amounts[item.id] = this.count(item.id) * this.coinValue;
            });

            this.amounts = amounts;
            this.refreshTotals();
            this.$dispatch('response-patch', { amounts: { ...amounts } });
        },

        add(id) {
            if (this.remainingAmount < this.coinValue || !this.$refs.purse) {
                return;
            }

            const coin = this.$refs.purse.querySelector('[data-coin]');
            const target = this.target(id);

            if (!coin || !target) {
                return;
            }

            target.appendChild(coin);
            this.sync();
        },

        remove(id) {
            const target = this.target(id);

            if (!target || !this.$refs.purse) {
                return;
            }

            const coin = target.querySelector('[data-coin]');

            if (!coin) {
                return;
            }

            this.$refs.purse.appendChild(coin);
            this.sync();
        },

        reset() {
            if (!this.$refs.purse) {
                return;
            }

            this.targets().forEach((target) => {
                target.querySelectorAll('[data-coin]').forEach((coin) => {
                    this.$refs.purse.appendChild(coin);
                });
            });

            this.sync();
        },
    }));

    Alpine.data('constructorCoach', (options) => ({
        values: options.values ?? {},
        fields: options.fields ?? [],
        blacklist: options.blacklist ?? [],
        chips: options.chips ?? [],
        draggedChip: null,

        init() {
            this.$dispatch('response-patch', { fields: { ...this.values } });
        },

        update(key, value) {
            if (!key) {
                return;
            }

            this.values = { ...this.values, [key]: value };
            this.$dispatch('response-patch', { fields: { ...this.values } });
        },

        dragChip(id) {
            this.draggedChip = id;
        },

        dropChip(key) {
            const chip = this.chips.find((item) => item.id === this.draggedChip);

            if (chip) {
                this.update(key, chip.label);
            }

            this.draggedChip = null;
        },

        get requiredFields() {
            return this.fields.filter((field) => field.required);
        },

        get completeCount() {
            return this.requiredFields.filter((field) => String(this.values[field.key] ?? '').trim().length >= field.min).length;
        },

        get progress() {
            return this.requiredFields.length === 0 ? 100 : Math.round((this.completeCount / this.requiredFields.length) * 100);
        },

        length(key) {
            return (this.values[key] ?? '').length;
        },

        stopWords(key) {
            const value = (this.values[key] ?? '').toLowerCase();

            return this.blacklist.filter((word) => word && value.includes(word.toLowerCase()));
        },
    }));

    Alpine.data('drawingBoard', (options) => ({
        palette: options.palette ?? [],
        color: options.palette?.[0] ?? '#2D5BFF',
        brush: 6,
        brushes: [3, 6, 12],
        strokes: Array.isArray(options.initial) ? options.initial : [],
        readonly: options.readonly === true,
        maxPoints: options.maxPoints ?? 4000,
        pointCount: 0,
        canvas: null,
        ctx: null,
        observer: null,
        draft: null,
        pointerId: null,

        init() {
            this.canvas = this.$refs.canvas;
            this.ctx = this.canvas.getContext('2d');
            this.pointCount = this.strokes.reduce((sum, stroke) => sum + (Array.isArray(stroke.points) ? stroke.points.length : 0), 0);
            this.resize();
            this.redraw();

            this.observer = new ResizeObserver(() => {
                this.resize();
                this.redraw();
            });
            this.observer.observe(this.canvas.parentElement);

            if (!this.readonly) {
                this.$dispatch('response-patch', { strokes: this.strokes });
            }
        },

        destroy() {
            this.observer?.disconnect();
        },

        get count() {
            return this.strokes.length;
        },

        resize() {
            const rect = this.canvas.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            this.canvas.width = Math.max(1, Math.round(rect.width * dpr));
            this.canvas.height = Math.max(1, Math.round(rect.height * dpr));
            this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        },

        point(event) {
            const rect = this.canvas.getBoundingClientRect();

            return {
                x: Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)),
                y: Math.min(1, Math.max(0, (event.clientY - rect.top) / rect.height)),
            };
        },

        start(event) {
            if (this.readonly || this.pointCount >= this.maxPoints || (event.button !== undefined && event.button !== 0)) {
                return;
            }

            event.preventDefault();

            try {
                this.canvas.setPointerCapture(event.pointerId);
            } catch (error) {
                // Pointer capture is best-effort; drawing still works without it.
            }

            this.pointerId = event.pointerId;
            const point = this.point(event);
            this.draft = { color: this.color, width: this.brush, points: [point] };
            this.segment(point, point, this.draft);
        },

        move(event) {
            if (!this.draft || event.pointerId !== this.pointerId || this.pointCount + this.draft.points.length >= this.maxPoints) {
                return;
            }

            const point = this.point(event);
            const last = this.draft.points[this.draft.points.length - 1];

            if (Math.hypot(point.x - last.x, point.y - last.y) < 0.0015) {
                return;
            }

            this.draft.points.push(point);
            this.segment(last, point, this.draft);
        },

        end() {
            if (!this.draft) {
                return;
            }

            if (this.draft.points.length > 0) {
                this.strokes = [...this.strokes, {
                    color: this.draft.color,
                    width: this.draft.width,
                    points: this.draft.points.map((point) => ({
                        x: Number(point.x.toFixed(4)),
                        y: Number(point.y.toFixed(4)),
                    })),
                }];
                this.pointCount += this.draft.points.length;
                this.$dispatch('response-patch', { strokes: this.strokes });
            }

            this.draft = null;
            this.pointerId = null;
        },

        segment(from, to, stroke) {
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.strokeStyle = stroke.color;
            this.ctx.fillStyle = stroke.color;
            this.ctx.lineWidth = stroke.width;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.beginPath();
            this.ctx.moveTo(from.x * rect.width, from.y * rect.height);
            this.ctx.lineTo(to.x * rect.width, to.y * rect.height);
            this.ctx.stroke();
        },

        redraw() {
            const rect = this.canvas.getBoundingClientRect();
            this.ctx.clearRect(0, 0, rect.width, rect.height);

            this.strokes.forEach((stroke) => {
                const points = Array.isArray(stroke.points) ? stroke.points : [];

                if (points.length === 0) {
                    return;
                }

                const width = Number(stroke.width) || 6;
                this.ctx.strokeStyle = stroke.color;
                this.ctx.fillStyle = stroke.color;
                this.ctx.lineWidth = width;
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                if (points.length === 1) {
                    this.ctx.beginPath();
                    this.ctx.arc(points[0].x * rect.width, points[0].y * rect.height, width / 2, 0, Math.PI * 2);
                    this.ctx.fill();

                    return;
                }

                this.ctx.beginPath();
                points.forEach((point, index) => {
                    const x = Number(point.x) * rect.width;
                    const y = Number(point.y) * rect.height;

                    if (index === 0) {
                        this.ctx.moveTo(x, y);
                    } else {
                        this.ctx.lineTo(x, y);
                    }
                });
                this.ctx.stroke();
            });
        },

        undo() {
            if (this.readonly || this.strokes.length === 0) {
                return;
            }

            this.strokes = this.strokes.slice(0, -1);
            this.pointCount = this.strokes.reduce((sum, stroke) => sum + (Array.isArray(stroke.points) ? stroke.points.length : 0), 0);
            this.redraw();
            this.$dispatch('response-patch', { strokes: this.strokes });
        },

        clear() {
            if (this.readonly || this.strokes.length === 0) {
                return;
            }

            this.strokes = [];
            this.pointCount = 0;
            this.redraw();
            this.$dispatch('response-patch', { strokes: this.strokes });
        },
    }));

    Alpine.data('puppetFigure', (options) => ({
        state: {
            head: 'level',
            shoulders: 'neutral',
            arms: 'open',
            posture: 'straight',
            ...(options.initial ?? {}),
        },
        target: options.target ?? 'confident',
        targetLabels: options.targets ?? {},
        touched: false,
        targetPostures: {
            confident: { head: 'up', shoulders: 'neutral', arms: 'open', posture: 'straight' },
            anxious: { head: 'down', shoulders: 'raised', arms: 'crossed', posture: 'slouched' },
            closed: { head: 'down', shoulders: 'low', arms: 'crossed', posture: 'slouched' },
            aggressive: { head: 'up', shoulders: 'raised', arms: 'hips', posture: 'straight' },
        },

        part(key, value) {
            this.state = { ...this.state, [key]: value };
            this.touched = true;
            this.push();
        },

        push() {
            if (!this.touched) {
                return;
            }

            this.$dispatch('response-patch', {
                puppet: { ...this.state, state: this.reading },
            });
        },

        get reading() {
            const { head, shoulders, arms } = this.state;

            if (arms === 'crossed' && shoulders === 'raised') {
                return 'anxious';
            }

            if (arms === 'crossed') {
                return 'closed';
            }

            if (arms === 'hips' && shoulders === 'raised') {
                return 'aggressive';
            }

            if (head === 'up' && shoulders === 'neutral' && arms === 'open' && this.state.posture === 'straight') {
                return 'confident';
            }

            return head === 'down' ? 'closed' : 'neutral';
        },

        get readingLabel() {
            return this.targetLabels[this.reading] ?? 'нейтрально';
        },

        get targetLabel() {
            return this.targetLabels[this.target] ?? this.target;
        },

        get match() {
            const target = this.targetPostures[this.target] ?? null;

            if (!target) {
                return 0;
            }

            const parts = ['head', 'shoulders', 'arms', 'posture'];
            const hits = parts.filter((part) => this.state[part] === target[part]).length;

            return Math.round((hits / parts.length) * 100);
        },

        get headTransform() {
            if (this.state.head === 'up') {
                return 'translate(0 -4) rotate(-8 150 60)';
            }

            if (this.state.head === 'down') {
                return 'translate(0 4) rotate(10 150 60)';
            }

            return '';
        },

        get shoulderTransform() {
            if (this.state.shoulders === 'raised') {
                return 'translate(0 -6)';
            }

            if (this.state.shoulders === 'low') {
                return 'translate(0 6)';
            }

            return '';
        },

        get postureTransform() {
            return this.state.posture === 'slouched' ? 'rotate(5 150 220)' : '';
        },
    }));

    Alpine.data('scenario', (config) => ({
        config,
        current: config.start,
        choices: [],
        feedback: null,
        finished: false,
        outcome: null,
        messages: [],
        tension: Number(config.tension?.start ?? 50),
        tensionEnabled: Array.isArray(config.tension) || (typeof config.tension === 'object' && config.tension !== null),

        init() {
            this.pushNode(this.config.start);
        },

        get node() {
            return this.config.nodes?.[this.current] ?? null;
        },

        get character() {
            return this.config.character ?? 'Собеседник';
        },

        get avatar() {
            const faces = {
                calm: '🙂',
                open: '😊',
                tense: '😬',
                angry: '😠',
                closed: '😒',
                sad: '😔',
                neutral: '🙂',
            };

            return faces[this.node?.mood ?? 'neutral'] ?? '🙂';
        },

        get tensionColor() {
            if (this.tension >= 67) {
                return '#E11D48';
            }

            return this.tension >= 34 ? '#F59E0B' : '#2BB673';
        },

        get tensionLabel() {
            if (this.tension >= 67) {
                return 'на пределе';
            }

            return this.tension >= 34 ? 'напряжённо' : 'спокойно';
        },

        pushNode(key) {
            const node = this.config.nodes?.[key];

            if (node?.text) {
                this.messages.push({ side: 'other', text: node.text });
            }
        },

        choose(index) {
            const node = this.node;
            const option = node?.options?.[index];

            if (!option) {
                return;
            }

            this.choices.push(index);
            this.messages.push({ side: 'me', text: option.label });
            this.feedback = option.feedback ?? null;

            if (this.tensionEnabled && typeof option.tension === 'number') {
                this.tension = Math.max(0, Math.min(100, this.tension + option.tension));
            }

            this.$dispatch('response-patch', {
                choices: [...this.choices],
                tension: this.tension,
            });

            const next = option.next ? this.config.nodes?.[option.next] : null;

            if (next) {
                this.current = option.next;
                this.pushNode(option.next);

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
            this.messages = [];
            this.tension = Number(this.config.tension?.start ?? 50);
            this.pushNode(this.config.start);
            this.$dispatch('response-patch', { choices: [], tension: this.tension });
        },
    }));

    Alpine.data('stroopGame', (options) => ({
        items: options.items ?? [],
        choices: options.options ?? [],
        timeLimit: options.timeLimit ?? 2500,
        index: 0,
        running: false,
        finished: false,
        rounds: [],
        chosen: null,
        remaining: 0,
        startedAt: 0,
        timer: null,
        ticker: null,

        get current() {
            return this.items[this.index] ?? null;
        },

        get progress() {
            return this.items.length > 0 ? Math.round((this.index / this.items.length) * 100) : 0;
        },

        get remainingPercent() {
            return this.timeLimit > 0 ? Math.max(0, Math.min(100, (this.remaining / this.timeLimit) * 100)) : 0;
        },

        start() {
            if (this.running) {
                return;
            }

            this.running = true;
            this.finished = false;
            this.rounds = [];
            this.index = 0;
            this.chosen = null;
            this.next();
        },

        next() {
            if (!this.running) {
                return;
            }

            if (this.index >= this.items.length) {
                this.finish();

                return;
            }

            this.chosen = null;
            this.remaining = this.timeLimit;
            this.startedAt = performance.now();
            this.timer = setTimeout(() => this.answer(null), this.timeLimit);
            this.ticker = setInterval(() => {
                this.remaining = Math.max(0, this.timeLimit - (performance.now() - this.startedAt));
            }, 50);
        },

        answer(choice) {
            if (!this.running || this.chosen !== null) {
                return;
            }

            this.chosen = choice ?? 'timeout';
            clearTimeout(this.timer);
            clearInterval(this.ticker);

            const ms = Math.min(this.timeLimit, Math.max(0, Math.round(performance.now() - this.startedAt)));
            this.rounds.push({ index: this.index, choice: choice ?? null, ms });
            this.index++;

            setTimeout(() => {
                if (this.running) {
                    this.next();
                }
            }, 180);
        },

        finish() {
            this.running = false;
            this.finished = true;
            this.chosen = null;
            this.remaining = 0;
            clearTimeout(this.timer);
            clearInterval(this.ticker);
            this.$dispatch('response-patch', { rounds: this.rounds });
        },

        destroy() {
            clearTimeout(this.timer);
            clearInterval(this.ticker);
        },
    }));

    Alpine.data('goNoGo', (config) => ({
        total: config.trials ?? 30,
        goRatio: config.go_ratio ?? 0.7,
        baseWindow: config.response_window_ms ?? 1100,
        windowMs: config.response_window_ms ?? 1100,
        minWindow: config.min_window_ms ?? 650,
        windowStep: config.window_step_ms ?? 60,
        adaptive: config.adaptive === true,
        flipAfter: Number(config.rule_flip_after ?? 0),
        distractors: Array.isArray(config.distractors) ? config.distractors : [],
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
        combo: 0,
        maxCombo: 0,
        flipped: false,
        announcement: null,
        distractor: null,
        lastResult: null,
        defaults: null,

        init() {
            this.defaults = {
                goLabel: this.goLabel,
                nogoLabel: this.nogoLabel,
                goHint: this.goHint,
                nogoHint: this.nogoHint,
            };
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

        get accuracy() {
            return this.trials.length > 0
                ? Math.round((this.trials.filter((trial) => trial.correct).length / this.trials.length) * 100)
                : 0;
        },

        get accuracyBefore() {
            const trials = this.trials.filter((trial) => !trial.flipped);

            return trials.length > 0
                ? Math.round((trials.filter((trial) => trial.correct).length / trials.length) * 100)
                : null;
        },

        get accuracyAfter() {
            const trials = this.trials.filter((trial) => trial.flipped);

            return trials.length > 0
                ? Math.round((trials.filter((trial) => trial.correct).length / trials.length) * 100)
                : null;
        },

        get ruleText() {
            return `Нажимать: «${this.goLabel}» · Пропускать: «${this.nogoLabel}»`;
        },

        get meanRt() {
            const times = this.trials.map((trial) => trial.rt).filter((value) => typeof value === 'number');

            return times.length > 0 ? Math.round(times.reduce((sum, value) => sum + value, 0) / times.length) : null;
        },

        get falseAlarms() {
            return this.trials.filter((trial) => trial.type === 'nogo' && trial.reacted).length;
        },

        get misses() {
            return this.trials.filter((trial) => trial.type === 'go' && !trial.reacted).length;
        },

        async start() {
            if (this.running) {
                return;
            }

            this.running = true;
            this.finished = false;
            this.trials = [];
            this.index = 0;
            this.combo = 0;
            this.maxCombo = 0;
            this.flipped = false;
            this.windowMs = this.baseWindow;
            this.announcement = null;
            this.distractor = null;
            this.lastResult = null;

            if (this.defaults) {
                this.goLabel = this.defaults.goLabel;
                this.nogoLabel = this.defaults.nogoLabel;
                this.goHint = this.defaults.goHint;
                this.nogoHint = this.defaults.nogoHint;
            }

            for (let i = 0; i < this.total; i++) {
                if (this.flipAfter > 0 && !this.flipped && i === this.flipAfter) {
                    await this.flipRules();
                }

                this.index = i + 1;
                await this.runTrial();
                await this.sleep(260);
            }

            this.running = false;
            this.finished = true;
            this.showSignal = false;
            this.distractor = null;
            this.$dispatch('response-patch', { trials: this.trials });
        },

        runTrial() {
            return new Promise((resolve) => {
                const windowForTrial = this.windowMs;
                const isGo = Math.random() < this.goRatio;

                this.current = isGo ? 'go' : 'nogo';
                this.reacted = false;
                this.rt = null;
                this.showSignal = true;
                this.lastResult = null;
                this.distractor = this.distractors.length > 0
                    ? this.distractors[Math.floor(Math.random() * this.distractors.length)]
                    : null;
                this.startedAt = performance.now();

                const timer = setTimeout(() => {
                    const correct = this.current === 'go' ? this.reacted : !this.reacted;

                    this.combo = correct ? this.combo + 1 : 0;
                    this.maxCombo = Math.max(this.maxCombo, this.combo);

                    if (this.adaptive) {
                        if (correct && this.combo > 0 && this.combo % 3 === 0) {
                            this.windowMs = Math.max(this.minWindow, this.windowMs - this.windowStep);
                        }

                        if (!correct) {
                            this.windowMs = Math.min(this.baseWindow, this.windowMs + this.windowStep);
                        }
                    }

                    this.lastResult = correct ? 'ok' : 'miss';
                    this.trials.push({
                        type: this.current,
                        reacted: this.reacted,
                        rt: this.reacted ? this.rt : null,
                        correct,
                        flipped: this.flipped,
                        window_ms: windowForTrial,
                    });
                    this.showSignal = false;
                    resolve();
                }, windowForTrial);

                this._trialTimer = timer;
            });
        },

        async flipRules() {
            [this.goLabel, this.nogoLabel] = [this.nogoLabel, this.goLabel];
            [this.goHint, this.nogoHint] = [this.nogoHint, this.goHint];
            this.flipped = true;
            this.showSignal = false;
            this.distractor = null;
            this.announcement = `Правила поменялись. Теперь нажимайте на «${this.goLabel}», а «${this.nogoLabel}» пропускайте.`;

            await this.sleep(2200);

            this.announcement = null;
            this.lastResult = null;
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
        navigatorOpen: false,
        startedAt: Date.now() - (Number(options.elapsedSeconds ?? 0) * 1000),
        elapsed: 0,
        itemStartedAt: Date.now(),
        timer: null,
        autosaveTimer: null,
        keyHandler: null,
        autoSubmitted: false,

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

                if (this.timeLimit && !this.autoSubmitted && this.elapsed >= this.timeLimit * 60) {
                    this.autoSubmitted = true;
                    this.submit();
                }
            }, 1000);
            this.autosaveTimer = setInterval(() => this.saveDraft(), 12000);
            this.keyHandler = (event) => this.handleKey(event);
            window.addEventListener('keydown', this.keyHandler);
        },

        destroy() {
            clearInterval(this.timer);
            clearInterval(this.autosaveTimer);
            if (this.keyHandler) {
                window.removeEventListener('keydown', this.keyHandler);
            }
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
                    return { order: this.shuffle((item.config?.items ?? []).map((entry) => entry.id)), orderTouched: false };
                case 'matching':
                    return { pairs: {} };
                case 'scale':
                    return { value: item.config?.min ?? 1 };
                default:
                    return { value: null };
            }
        },

        shuffle(ids) {
            const result = [...ids];

            for (let index = result.length - 1; index > 0; index -= 1) {
                const swap = Math.floor(Math.random() * (index + 1));
                [result[index], result[swap]] = [result[swap], result[index]];
            }

            if (result.length > 1 && result.every((id, index) => id === ids[index])) {
                result.push(result.shift());
            }

            return result;
        },

        get current() {
            return this.items[this.index] ?? null;
        },

        get progress() {
            return this.items.length === 0 ? 0 : Math.round((this.answeredCount / this.items.length) * 100);
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
                    return answer.orderTouched === true && (answer.order ?? []).length === (item.config?.items ?? []).length;
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
            answer.orderTouched = true;
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

        goTo(index) {
            if (index < 0 || index >= this.items.length) {
                return;
            }

            if (index === this.index) {
                this.navigatorOpen = false;

                return;
            }

            this.markTime();
            this.index = index;
            this.itemStartedAt = Date.now();
            this.navigatorOpen = false;
        },

        handleKey(event) {
            if (this.confirmSubmit || event.metaKey || event.ctrlKey || event.altKey) {
                return;
            }

            const tag = event.target?.tagName;
            const isTyping = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || event.target?.isContentEditable;

            if (isTyping) {
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.prev();
                return;
            }

            if (event.key === 'Enter') {
                if (tag !== 'BODY') {
                    return;
                }

                event.preventDefault();
                this.next();
                return;
            }

            if (!['single', 'motivation'].includes(this.current?.type)) {
                return;
            }

            const position = Number(event.key) - 1;

            if (!Number.isInteger(position) || position < 0) {
                return;
            }

            if (this.current.type === 'motivation' && position < 5) {
                event.preventDefault();
                this.select(position + 1);
                return;
            }

            const choice = (this.current.config?.options ?? [])[position];

            if (choice) {
                event.preventDefault();
                this.select(choice.id);
            }
        },

        markTime() {
            if (!this.current) {
                return;
            }

            const answer = this.answers[this.current.id];
            if (answer) {
                const spent = Math.max(0, Date.now() - this.itemStartedAt);
                answer.ms = Math.max(0, Number(answer.ms || 0) + spent);
            }

            this.itemStartedAt = Date.now();
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
