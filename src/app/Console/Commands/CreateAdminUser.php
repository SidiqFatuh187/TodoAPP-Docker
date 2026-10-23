<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin 
                            {--name=Admin Claro : Nama admin} 
                            {--email=admin@claro.com : Email admin} 
                            {--password=admin12345 : Password admin} 
                            {--phone= : Nomor WhatsApp admin (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an admin user for CLARO application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name     = $this->option('name') ?: 'Admin Claro';
        $email    = $this->option('email') ?: 'admin@claro.com';
        $password = $this->option('password') ?: 'admin12345';
        $phone    = $this->option('phone');

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'name'              => $name,
                'password'          => Hash::make($password),
                'role'              => 'admin',
                'is_banned'         => false,
                'email_verified_at' => now(),
                'phone'             => $phone ?: $user->phone,
                'timezone'          => $user->timezone ?: 'Asia/Jakarta',
            ]);
            $this->info("User [{$email}] berhasil diupdate menjadi role ADMIN.");
        } else {
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'password'          => Hash::make($password),
                'role'              => 'admin',
                'is_banned'         => false,
                'email_verified_at' => now(),
                'phone'             => $phone,
                'timezone'          => 'Asia/Jakarta',
            ]);
            $this->info("Akun ADMIN baru [{$email}] berhasil dibuat.");
        }

        $this->newLine();
        $this->table(
            ['Field', 'Detail'],
            [
                ['Nama', $user->name],
                ['Email', $user->email],
                ['Password', $password],
                ['Role', $user->role],
                ['Timezone', $user->timezone],
                ['Phone', $user->phone ?? '-'],
            ]
        );

        $this->newLine();
        $this->info("Silakan login di: http://localhost:8000/login");

        return Command::SUCCESS;
    }
}

