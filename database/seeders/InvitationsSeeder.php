<?php

namespace Database\Seeders;

use App\Models\Invitation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InvitationsSeeder extends Seeder
{
    public function run(): void
    {
        Invitation::create([
            'email' => 'valid@example.com',
            'role' => 'User',
            'token' => Str::random(32),
            'expires_at' => now()->addDay(),
        ]);

        Invitation::create([
            'email' => 'expired@example.com',
            'role' => 'Admin',
            'token' => Str::random(32),
            'expires_at' => now()->subDay(),
        ]);
    }
}
