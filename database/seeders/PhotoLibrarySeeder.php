<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Module;
use App\Models\TheoryArticle;
use App\Models\Trainer;
use App\Support\PhotoLibrary;
use Illuminate\Database\Seeder;

class PhotoLibrarySeeder extends Seeder
{
    public function run(): void
    {
        foreach (PhotoLibrary::all() as $path => $photo) {
            Media::query()->updateOrCreate(
                ['path' => $path],
                [
                    'disk' => 'public',
                    'mime' => 'image/jpeg',
                    'alt' => $photo['alt'] ?? null,
                    'source' => $photo['source'] ?? 'Unsplash',
                    'license' => $photo['license'] ?? 'Unsplash License',
                    'author' => $photo['author'] ?? null,
                ],
            );
        }

        $moduleCovers = [
            'understand-self' => 'media/photos/thoughtful.jpg',
            'manage-state' => 'media/photos/calm-forest.jpg',
            'motivation' => 'media/photos/team-highfive.jpg',
            'understand-others' => 'media/photos/friends-sunset.jpg',
            'communicate' => 'media/photos/meeting.jpg',
        ];

        foreach ($moduleCovers as $slug => $path) {
            Module::query()->where('slug', $slug)->update(['cover_path' => $path]);
        }

        $trainerCovers = [
            'card-of-day' => 'media/photos/calm-lake.jpg',
            'emotion-wheel' => 'media/photos/portrait-neutral.jpg',
            'body-map' => 'media/photos/meditate.jpg',
            'inner-weather' => 'media/photos/calm-lake.jpg',
            'volcano' => 'media/photos/stress-face.jpg',
            'breathing-478' => 'media/photos/calm-forest.jpg',
            'stop-crank' => 'media/photos/focus.jpg',
            'traffic-light' => 'media/photos/stress-laptop.jpg',
            'motivation-tags' => 'media/photos/planning.jpg',
            'six-trees' => 'media/photos/campus-path.jpg',
            'magic-shop' => 'media/photos/planner.jpg',
            'duty-interest' => 'media/photos/notes.jpg',
            'describe-situation' => 'media/photos/presentation.jpg',
            'three-views' => 'media/photos/colleagues.jpg',
            'active-listening' => 'media/photos/discussion.jpg',
            'behind-behavior' => 'media/photos/friends-support.jpg',
            'conflict-fork' => 'media/photos/conflict-laptop.jpg',
            'holiday-pie' => 'media/photos/team-hands.jpg',
            'i-statement' => 'media/photos/interview.jpg',
            'toxic-phrases' => 'media/photos/colleagues.jpg',
            'mimika' => 'media/photos/portrait-neutral.jpg',
            'self-acceptance' => 'media/photos/shy.jpg',
            'wise-man' => 'media/photos/calm-lake.jpg',
            'professional-coat' => 'media/photos/workshop.jpg',
            'trash-can' => 'media/photos/writing.jpg',
            'three-whales' => 'media/photos/meditate.jpg',
            'marionette' => 'media/photos/cover-face.jpg',
            'focus-switch' => 'media/photos/focus.jpg',
            'iceberg' => 'media/photos/calm-lake.jpg',
            'career-route' => 'media/photos/campus-arch.jpg',
            'smart-goal' => 'media/photos/planner.jpg',
            'wheel-balance' => 'media/photos/friends-sunset.jpg',
            'five-whys' => 'media/photos/writing.jpg',
            'wins-diary' => 'media/photos/graduation.jpg',
            'wall-obstacles' => 'media/photos/doubt.jpg',
            'nonverbal-signals' => 'media/photos/friends-support.jpg',
            'understand-me' => 'media/photos/students-smiling.jpg',
            'compliment' => 'media/photos/friends-hug.jpg',
            'request-without-demand' => 'media/photos/meeting.jpg',
            'external-agreement' => 'media/photos/discussion.jpg',
            'broken-record' => 'media/photos/colleagues.jpg',
            'mediator' => 'media/photos/hands-together.jpg',
            'confident-response' => 'media/photos/presentation.jpg',
        ];

        foreach ($trainerCovers as $slug => $path) {
            Trainer::query()->where('slug', $slug)->update(['cover_path' => $path]);
        }

        $theoryCovers = [
            'what-is-ei' => 'media/photos/lecture.jpg',
            'why-emotions' => 'media/photos/portrait-neutral.jpg',
            'motivation-expectancy' => 'media/photos/campus-path.jpg',
            'empathy-boundaries' => 'media/photos/friends-support.jpg',
            'conflict-strategies' => 'media/photos/conflict-laptop.jpg',
            'emotions-are-not-faces' => 'media/photos/cover-face.jpg',
        ];

        foreach ($theoryCovers as $slug => $path) {
            TheoryArticle::query()->where('slug', $slug)->update(['cover_path' => $path]);
        }
    }
}
