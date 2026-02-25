<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the base roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        // 2. Create the main SaaS Admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'fredericopaulista@gmail.com'],
            [
                'name' => 'Frederico Moura',
                'password' => Hash::make('12345678'),
            ]
        );

        $adminUser->assignRole($superAdminRole);

        // 3. Create a default Tenant (if we need to mock a workspace for the admin)
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'frederico.localhost'],
            ['name' => 'Frederico Workspace']
        );
        
        // 4. Attach admin to this workspace using the pivot
        if (!$adminUser->tenants->contains($tenant->id)) {
            $adminUser->tenants()->attach($tenant->id);
        }

        $this->call([
            PlanSeeder::class,
        ]);
    }
}
