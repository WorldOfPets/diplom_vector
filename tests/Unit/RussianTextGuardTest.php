<?php

namespace Tests\Unit;

use App\Support\RussianTextGuard;
use Tests\TestCase;

class RussianTextGuardTest extends TestCase
{
    public function test_normal_text_passes(): void
    {
        $guard = new RussianTextGuard;

        $this->assertSame([], $guard->suspiciousWords('Вчера я злился на друга, потому что он опоздал'));
        $this->assertSame([], $guard->suspiciousWords('Мне тревожно перед экзаменом, но я справлюсь'));
        $this->assertSame([], $guard->suspiciousWords(''));
    }

    public function test_keyboard_mash_is_detected(): void
    {
        $guard = new RussianTextGuard;

        $this->assertSame(['билеберда', 'фывап'], $guard->suspiciousWords('билеберда фывап'));
        $this->assertSame(['фывап'], $guard->suspiciousWords('фывап'));
        $this->assertSame(['аааааа'], $guard->suspiciousWords('аааааа'));
    }

    public function test_latin_keyboard_mash_is_detected(): void
    {
        $guard = new RussianTextGuard;

        $this->assertSame(['asdf', 'qwer'], $guard->suspiciousWords('asdf qwer'));
        $this->assertSame([], $guard->suspiciousWords('Хочу прокачать soft skills'));
    }

    public function test_single_unfamiliar_word_inside_a_phrase_is_tolerated(): void
    {
        $guard = new RussianTextGuard;

        $this->assertSame([], $guard->suspiciousWords('Я чувствую кринж, но говорю об этом прямо'));
    }

    public function test_suspicious_words_are_merged_across_fields(): void
    {
        $guard = new RussianTextGuard;

        $this->assertSame(
            ['фывап', 'билеберда'],
            $guard->suspiciousFrom(['фывап', 'билеберда', 'нормальный текст', null, '']),
        );
    }
}
