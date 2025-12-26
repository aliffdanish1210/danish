<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminRoleSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('user_id', 'admin')->first();

        if ($admin) {
            $admin->assignRole('admin'); // uses 'web' guard
        }
    }
}
