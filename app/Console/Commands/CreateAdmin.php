<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'panti:admin {email?} {--name=Pengurus}';

    protected $description = 'Membuat akun pengurus tanpa password bawaan publik';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email pengurus');
        $password = $this->secret('Password (minimal 12 karakter)');
        $data = ['email' => $email, 'password' => $password, 'name' => $this->option('name')];
        $validator = Validator::make($data, ['email' => 'required|email|unique:users', 'password' => 'required|min:12', 'name' => 'required|string|max:150']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

return self::FAILURE;
        }
        User::create($data);
        $this->info('Akun pengurus berhasil dibuat.');

        return self::SUCCESS;
    }
}
