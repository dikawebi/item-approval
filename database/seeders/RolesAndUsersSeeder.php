<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Roles required by code:
     * - 'accounting'  gated in ItemCreationRequestResource (classify / request-info /
     *                 reject actions), MyActionQueueWidget, observer + digest notifications
     * - 'commercial'  gated in ItemCreationRequestResource (create-in-D365 action),
     *                 MyActionQueueWidget, observer notifications
     *
     * Plain users (no role) are treated as requesters: they only see their own
     * requests (see ItemCreationRequestResource::getEloquentQuery()).
     */
    public function run(): void
    {
        foreach (['accounting', 'commercial'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $users = [
            // [name, email, roles]
            ['Requester', 'requester@example.com', []],
            ['Accounting', 'accounting@example.com', ['accounting']],
            ['Commercial', 'commercial@example.com', ['commercial']],
            ['Admin', 'admin@example.com', ['accounting', 'commercial']],
        ];

        foreach ($users as [$name, $email, $roles]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles($roles);
        }

        $this->command?->info('Seeded roles (accounting, commercial) and demo users.');
    }
}
