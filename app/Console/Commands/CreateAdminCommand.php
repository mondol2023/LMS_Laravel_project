<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'ielts:create-admin
                            {--name= : The admin name}
                            {--email= : The admin email}
                            {--password= : The admin password}
                            {--country= : The admin country}';

    protected $description = 'Create a new admin user for the IELTS application';

    public function handle(): int
    {
        $this->info('Creating new admin user for IELTS Mock Test Application');
        $this->newLine();

        $name = $this->option('name') ?: $this->ask('Admin name');
        $email = $this->option('email') ?: $this->ask('Admin email');
        $country = $this->option('country') ?: $this->ask('Country', 'United States');

        $password = $this->option('password') ?: $this->secret('Admin password');

        if (! $password) {
            $this->error('Password is required');

            return self::FAILURE;
        }

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'country' => $country,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'country' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  • {$error}");
            }

            return self::FAILURE;
        }

        try {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'country' => $country,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $this->info('✅ Admin user created successfully!');
            $this->newLine();
            $this->table(['Field', 'Value'], [
                ['ID', $admin->id],
                ['Name', $admin->name],
                ['Email', $admin->email],
                ['Country', $admin->country],
                ['Role', $admin->role],
                ['Created', $admin->created_at->format('Y-m-d H:i:s')],
            ]);

            $this->newLine();
            $this->info('The admin can now log in and access:');
            $this->line('  • User management');
            $this->line('  • Test creation and management');
            $this->line('  • Score management for Writing & Speaking');
            $this->line('  • System analytics and reports');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to create admin user: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
