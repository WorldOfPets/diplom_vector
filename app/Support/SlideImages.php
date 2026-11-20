<?php

namespace App\Support;

final class SlideImages
{
    /**
     * Папки со слайдами по слагу статьи теории.
     *
     * @var array<string, array{deck: string, slides: int}>
     */
    public const DECKS = [
        'what-is-ei' => ['deck' => 'deck-04', 'slides' => 12],
        'why-emotions' => ['deck' => 'deck-09', 'slides' => 13],
        'motivation-expectancy' => ['deck' => 'deck-01', 'slides' => 9],
        'empathy-boundaries' => ['deck' => 'deck-18', 'slides' => 11],
        'conflict-strategies' => ['deck' => 'deck-16', 'slides' => 10],
        'emotions-are-not-faces' => ['deck' => 'deck-11', 'slides' => 14],
        'emotion-diary' => ['deck' => 'deck-08', 'slides' => 10],
        'emotion-granularity' => ['deck' => 'deck-12', 'slides' => 15],
        'emotion-perception' => ['deck' => 'deck-15', 'slides' => 15],
        'body-emotion-map' => ['deck' => 'deck-02', 'slides' => 12],
        'emotion-dynamics' => ['deck' => 'deck-10', 'slides' => 10],
        'emotion-regulation' => ['deck' => 'deck-14', 'slides' => 7],
        'active-listening' => ['deck' => 'deck-06', 'slides' => 12],
        'stress-burnout' => ['deck' => 'deck-13', 'slides' => 13],
        'emotions-decisions' => ['deck' => 'deck-05', 'slides' => 10],
        'ei-measurement' => ['deck' => 'deck-17', 'slides' => 12],
        'dark-side-ei' => ['deck' => 'deck-03', 'slides' => 15],
        'ei-practice-plan' => ['deck' => 'deck-07', 'slides' => 11],
    ];

    /**
     * @var array<string, string>
     */
    private const MODULES = [
        'understand-self' => 'what-is-ei',
        'understand-others' => 'empathy-boundaries',
        'manage-state' => 'emotion-regulation',
        'motivation' => 'motivation-expectancy',
        'communicate' => 'active-listening',
    ];

    /**
     * @var array<string, string>
     */
    private const TESTS = [
        'ei_full' => 'what-is-ei',
        'ei_express' => 'emotion-perception',
        'ei_situational' => 'conflict-strategies',
        'motivation' => 'motivation-expectancy',
    ];

    public static function title(string $theorySlug): string
    {
        return self::slide($theorySlug, 1);
    }

    public static function module(string $moduleSlug, int $offset = 0): string
    {
        $theory = self::MODULES[$moduleSlug] ?? 'what-is-ei';

        return self::contentSlide($theory, $offset);
    }

    public static function test(string $kind): string
    {
        $theory = self::TESTS[$kind] ?? 'what-is-ei';

        return self::contentSlide($theory, 0);
    }

    public static function contentSlide(string $theorySlug, int $offset = 0): string
    {
        $deck = self::DECKS[$theorySlug] ?? self::DECKS['what-is-ei'];
        $available = max(1, $deck['slides'] - 1);
        $number = 2 + ($offset % $available);

        return self::slide($theorySlug, $number);
    }

    public static function slide(string $theorySlug, int $number): string
    {
        $deck = self::DECKS[$theorySlug] ?? self::DECKS['what-is-ei'];
        $number = max(1, min($deck['slides'], $number));

        return 'media/slides/'.$deck['deck'].'/slide-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.jpg';
    }
}
