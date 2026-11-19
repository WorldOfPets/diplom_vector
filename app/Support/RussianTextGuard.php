<?php

namespace App\Support;

final class RussianTextGuard
{
    /**
     * Слова короче этого порога не проверяем: предлоги и союзы слишком шумные.
     */
    private const MIN_WORD_LENGTH = 2;

    /**
     * Проверяем только первые буквы: так отсекаются опечатки и набор букв,
     * но проходят падежи и спряжения.
     */
    private const PREFIX_LENGTH = 5;

    /**
     * Текст считается набором букв, если незнакомых слов больше половины.
     */
    private const UNKNOWN_RATIO = 0.5;

    private static ?string $dictionary = null;

    /**
     * Возвращает незнакомые слова, если их в тексте больше половины.
     *
     * @return list<string>
     */
    public function suspiciousWords(string $text): array
    {
        $words = $this->words($text);

        if ($words === []) {
            return [];
        }

        $dictionary = self::dictionary();

        $unknown = array_values(array_filter(
            $words,
            fn (string $word): bool => self::hasRepeats($word) || ! self::isKnown($word, $dictionary),
        ));

        if ($unknown === [] || count($unknown) / count($words) <= self::UNKNOWN_RATIO) {
            return [];
        }

        return $unknown;
    }

    /**
     * Возвращает незнакомые слова из набора текстов, если их больше половины.
     *
     * @param  iterable<string|null>  $texts
     * @return list<string>
     */
    public function suspiciousFrom(iterable $texts): array
    {
        $suspicious = [];

        foreach ($texts as $text) {
            if (! is_string($text) || trim($text) === '') {
                continue;
            }

            foreach ($this->suspiciousWords($text) as $word) {
                $suspicious[$word] = true;
            }
        }

        return array_keys($suspicious);
    }

    /**
     * @return list<string>
     */
    private function words(string $text): array
    {
        $text = mb_strtolower(str_replace('ё', 'е', $text));
        preg_match_all('/[а-я]+|[a-z]+/u', $text, $matches);

        return array_values(array_filter(
            $matches[0],
            fn (string $word): bool => mb_strlen($word) >= self::MIN_WORD_LENGTH,
        ));
    }

    private static function isKnown(string $word, string $dictionary): bool
    {
        // Латиница в русском тексте — обычно аббревиатура или сленг.
        // Одно такое слово в фразе допустимо, но латинский набор букв — нет.
        if (preg_match('/^[a-z]+$/', $word) === 1) {
            return false;
        }

        $prefix = mb_substr($word, 0, self::PREFIX_LENGTH);

        return str_contains($dictionary, "\n".$prefix."\n");
    }

    /**
     * «ааааа», «урааааа» и подобное — не слова, а растянутые буквы.
     */
    private static function hasRepeats(string $word): bool
    {
        return preg_match('/(.)\1{3,}/u', $word) === 1;
    }

    private static function dictionary(): string
    {
        return self::$dictionary ??= (string) file_get_contents(resource_path('data/ru-word-prefixes.txt'));
    }
}
