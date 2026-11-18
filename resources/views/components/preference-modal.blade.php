@php
    $preferenceUser = auth()->user();
    $preferenceTheme = \App\Support\Preferences::theme($preferenceUser);
    $preferenceTone = \App\Support\Preferences::tone($preferenceUser);
@endphp

@if(! \App\Support\Preferences::isSet($preferenceUser))
    <div
        x-data="{
            open: ! sessionStorage.getItem('vektor-prefs-skipped'),
            theme: '{{ $preferenceTheme }}',
            tone: '{{ $preferenceTone }}',
            pickTheme(value) {
                this.theme = value;
                document.documentElement.dataset.theme = value;
            },
            pickTone(value) {
                this.tone = value;
            }
        }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] overflow-y-auto bg-inverse/70 p-3 backdrop-blur-sm sm:p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="preferences-title"
    >
        <div class="mx-auto flex min-h-full max-w-3xl items-center justify-center">
            <form method="POST" action="{{ route('preferences.store') }}" class="w-full rounded-[2rem] border border-line/70 bg-surface p-5 shadow-2xl sm:p-8">
                @csrf

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="section-eyebrow">Настройка интерфейса</p>
                        <h2 id="preferences-title" class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ tone('preferences.title') }}</h2>
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-muted">{{ tone('preferences.lead') }}</p>
                    </div>
                    <button type="button" @click="open = false" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-line text-muted transition hover:bg-paper hover:text-ink" aria-label="Закрыть настройки">
                        <x-icon name="close" class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <fieldset>
                        <legend class="label">{{ tone('preferences.theme') }}</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="theme" value="light" x-model="theme" @change="window.applyVektorTheme($event.target.value)" class="sr-only">
                                <div class="rounded-2xl border p-2 transition" :class="theme === 'light' ? 'border-brand ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                    <div data-theme="light" class="overflow-hidden rounded-xl border border-line/60 bg-paper p-2.5">
                                        <div class="h-1.5 w-8 rounded-full bg-brand"></div>
                                        <div class="mt-2 h-1 w-full rounded-full bg-line"></div>
                                        <div class="mt-1 h-1 w-2/3 rounded-full bg-line"></div>
                                        <div class="mt-3 grid grid-cols-3 gap-1">
                                            <div class="h-5 rounded-md bg-surface shadow-sm"></div>
                                            <div class="h-5 rounded-md bg-brand-soft"></div>
                                            <div class="h-5 rounded-md bg-surface shadow-sm"></div>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-center text-xs font-bold">{{ tone('preferences.theme.light') }}</p>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="theme" value="dark" x-model="theme" @change="window.applyVektorTheme($event.target.value)" class="sr-only">
                                <div class="rounded-2xl border p-2 transition" :class="theme === 'dark' ? 'border-brand ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                    <div data-theme="dark" class="overflow-hidden rounded-xl border border-line/60 bg-paper p-2.5">
                                        <div class="h-1.5 w-8 rounded-full bg-brand"></div>
                                        <div class="mt-2 h-1 w-full rounded-full bg-line"></div>
                                        <div class="mt-1 h-1 w-2/3 rounded-full bg-line"></div>
                                        <div class="mt-3 grid grid-cols-3 gap-1">
                                            <div class="h-5 rounded-md bg-surface shadow-sm"></div>
                                            <div class="h-5 rounded-md bg-brand-soft"></div>
                                            <div class="h-5 rounded-md bg-surface shadow-sm"></div>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-center text-xs font-bold">{{ tone('preferences.theme.dark') }}</p>
                                </div>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="label">{{ tone('preferences.tone') }}</legend>
                        <div class="space-y-3">
                            <label class="block cursor-pointer">
                                <input type="radio" name="tone" value="vibe" x-model="tone" class="sr-only">
                                <div class="rounded-2xl border p-3 transition" :class="tone === 'vibe' ? 'border-brand bg-brand-soft/40 ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-extrabold">{{ tone('preferences.tone.vibe') }}</p>
                                        <span class="chip px-2 py-0.5 text-[10px]">🔥</span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted">{{ tone('preferences.tone.vibe_hint') }}</p>
                                    <p class="mt-2 rounded-xl bg-surface/70 px-3 py-2 text-xs leading-relaxed text-ink-soft">{{ tone('preferences.preview.vibe') }}</p>
                                </div>
                            </label>

                            <label class="block cursor-pointer">
                                <input type="radio" name="tone" value="professional" x-model="tone" class="sr-only">
                                <div class="rounded-2xl border p-3 transition" :class="tone === 'professional' ? 'border-brand bg-brand-soft/40 ring-4 ring-brand/10' : 'border-line hover:border-brand/30'">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-extrabold">{{ tone('preferences.tone.professional') }}</p>
                                        <span class="chip px-2 py-0.5 text-[10px]">🎓</span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-muted">{{ tone('preferences.tone.professional_hint') }}</p>
                                    <p class="mt-2 rounded-xl bg-surface/70 px-3 py-2 text-xs leading-relaxed text-ink-soft">{{ tone('preferences.preview.professional') }}</p>
                                </div>
                            </label>
                        </div>
                    </fieldset>
                </div>

                @if($errors->has('theme') || $errors->has('tone'))
                    <p class="mt-4 rounded-2xl border border-rose/20 bg-rose/5 px-4 py-2.5 text-xs font-semibold text-rose">{{ $errors->first('theme') ?: $errors->first('tone') }}</p>
                @endif

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" @click="sessionStorage.setItem('vektor-prefs-skipped', '1'); open = false" class="btn-ghost px-3 py-2 text-xs">Позже</button>
                    <button type="submit" class="btn-primary w-full py-3 sm:w-auto">
                        {{ tone('preferences.save') }}
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>

                <p class="mt-4 text-center text-[10px] leading-relaxed text-muted">{{ tone('preferences.note') }} Настройки можно изменить в профиле.</p>
            </form>
        </div>
    </div>
@endif
