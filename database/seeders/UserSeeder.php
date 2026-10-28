<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Демоакаунти дозволено створювати лише в local/testing.'
            );
        }

        $password = 'Password-12345';

        $users = [
            ['Owner One', 'owner1@example.test', true],
            ['Owner Two', 'owner2@example.test', true],
            ['Owner Three', 'owner3@example.test', true],
            ['Viewer', 'viewer@example.test', true],
            ['Editor', 'editor@example.test', true],
            ['Reviewer', 'reviewer@example.test', true],
            ['Unrelated User', 'unrelated@example.test', true],
            ['Inactive User', 'inactive@example.test', false],
        ];

        foreach ($users as [$name, $email, $active]) {
            $user = User::query()->firstOrNew([
                'email' => $email,
            ]);

            $user->name = $name;
            $user->password = $password;
            $user->is_active = $active;
            $user->email_verified_at = now();

            $user->saveOrFail();
        }
    }
}
