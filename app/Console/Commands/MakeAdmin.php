<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'app:admin
        {email : Email пользователя, который станет администратором}
        {--name= : Имя, если пользователя ещё нет в базе}';

    protected $description = 'Назначает администратора по email (создаёт пользователя, если его ещё нет).';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $this->option('name') ?: 'Администратор',
                'email' => $email,
                'role' => UserRole::Admin,
                'consent_at' => now(),
                'consent_version' => '1.0',
            ]);

            $this->info("Создан администратор {$user->email}.");
        } else {
            $user->forceFill([
                'role' => UserRole::Admin,
                'consent_at' => $user->consent_at ?? now(),
                'consent_version' => $user->consent_version ?? '1.0',
            ])->save();

            $this->info("Пользователь {$user->email} назначен администратором.");
        }

        $this->line('При следующем входе через Яндекс роль сохранится.');

        return self::SUCCESS;
    }
}
