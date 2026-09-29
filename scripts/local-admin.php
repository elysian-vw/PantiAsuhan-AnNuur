<?php
// Local bootstrap only. Never use default credentials for deployment.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('local')) { fwrite(STDERR, "Hanya untuk lingkungan local.\n"); exit(1); }
$email = 'pengurus@annuur2.test';
if (App\Models\User::where('email', $email)->exists()) { echo "Akun lokal sudah ada; password tidak diubah.\n"; exit(0); }
$password = bin2hex(random_bytes(10));
App\Models\User::create(['name' => 'Pengurus An-Nuur 2', 'email' => $email, 'password' => $password]);
if (!is_dir(__DIR__.'/../.runtime')) mkdir(__DIR__.'/../.runtime', 0700, true);
file_put_contents(__DIR__.'/../.runtime/admin-access.json', json_encode(['url' => 'http://127.0.0.1:8000/admin/login', 'email' => $email, 'password' => $password], JSON_PRETTY_PRINT));
echo "Akun lokal dibuat. Detail akses tersimpan di .runtime/admin-access.json (diabaikan Git).\n";
