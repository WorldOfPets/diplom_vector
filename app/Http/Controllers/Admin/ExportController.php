<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestAttempt;
use App\Models\TrainerAttempt;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function users(): StreamedResponse
    {
        $users = User::query()->with('group')->orderBy('id')->get();

        return $this->csv('users.csv', [
            'id', 'name', 'email', 'role', 'group', 'course', 'specialty', 'consent_at', 'last_seen_at', 'created_at',
        ], $users->map(fn (User $user): array => [
            $user->id,
            $user->name,
            $user->email,
            $user->role?->value,
            $user->group?->name,
            $user->course,
            $user->specialty,
            $user->consent_at?->toDateTimeString(),
            $user->last_seen_at?->toDateTimeString(),
            $user->created_at?->toDateTimeString(),
        ]));
    }

    public function trainerAttempts(): StreamedResponse
    {
        $attempts = TrainerAttempt::query()
            ->where('status', 'completed')
            ->with(['user.group', 'trainer.module'])
            ->orderBy('id')
            ->get();

        return $this->csv('trainer-attempts.csv', [
            'user', 'group', 'module', 'trainer', 'percent', 'passed', 'duration_sec', 'completed_at',
        ], $attempts->map(fn (TrainerAttempt $attempt): array => [
            $attempt->user?->name,
            $attempt->user?->group?->name,
            $attempt->trainer?->module?->title,
            $attempt->trainer?->title,
            $attempt->percent,
            $attempt->passed ? '1' : '0',
            $attempt->duration_sec,
            $attempt->completed_at?->toDateTimeString(),
        ]));
    }

    public function testAttempts(): StreamedResponse
    {
        $attempts = TestAttempt::query()
            ->where('status', 'completed')
            ->with(['user.group', 'test'])
            ->orderBy('id')
            ->get();

        return $this->csv('test-attempts.csv', [
            'user', 'group', 'test', 'overall', 'level', 'scores', 'duration_sec', 'finished_at',
        ], $attempts->map(fn (TestAttempt $attempt): array => [
            $attempt->user?->name,
            $attempt->user?->group?->name,
            $attempt->test?->title,
            $attempt->overall,
            $attempt->level,
            json_encode($attempt->scores, JSON_UNESCAPED_UNICODE),
            $attempt->duration_sec,
            $attempt->finished_at?->toDateTimeString(),
        ]));
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            fputcsv($output, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($output, array_map(fn ($value): string => $this->safe($value), $row), ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safe(mixed $value): string
    {
        $string = (string) ($value ?? '');

        if (preg_match('/^[=+\-@]/', $string) === 1) {
            return "'".$string;
        }

        return $string;
    }
}
