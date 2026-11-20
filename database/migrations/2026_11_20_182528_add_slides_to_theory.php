<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Соответствие статьи теории и папки со слайдами.
     *
     * @var array<string, array{deck: string, slides: int}>
     */
    private array $decks = [
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

    public function up(): void
    {
        Schema::table('theory_articles', function (Blueprint $table): void {
            $table->json('slides')->nullable()->after('cover_path');
        });

        Schema::table('theory_progress', function (Blueprint $table): void {
            $table->json('slides_progress')->nullable()->after('video_progress');
            $table->timestamp('slides_completed_at')->nullable()->after('slides_progress');
        });

        foreach ($this->decks as $slug => $deck) {
            $slides = [];

            for ($i = 1; $i <= $deck['slides']; $i++) {
                $slides[] = 'media/slides/'.$deck['deck'].'/slide-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'.jpg';
            }

            DB::table('theory_articles')
                ->where('slug', $slug)
                ->update(['slides' => json_encode($slides, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('theory_progress', function (Blueprint $table): void {
            $table->dropColumn(['slides_progress', 'slides_completed_at']);
        });

        Schema::table('theory_articles', function (Blueprint $table): void {
            $table->dropColumn('slides');
        });
    }
};
