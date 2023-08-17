<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $id = (string) Str::orderedUuid();
        //
        $superadmin = \App\User::create([
            'object_id' => $id,
            'first_name' => 'Kit',
            'last_name' => 'Seno',
            'user_type' => 'admin',
            'email' => 'kit.seno@camayacoast.com',
            'password' => bcrypt('secret'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        $role = Role::create(['name' => 'super-admin']);
        $superadmin->assignRole('super-admin');

    }
}
