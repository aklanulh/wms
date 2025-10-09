<?php
/**
 * Setup Script untuk Hostinger
 * Jalankan script ini setelah upload ke server Hostinger
 */

echo "=== WMS Setup untuk Hostinger ===\n";

// 1. Copy .env.hostinger ke .env
if (file_exists('.env.hostinger')) {
    copy('.env.hostinger', '.env');
    echo "✓ File .env berhasil dibuat dari .env.hostinger\n";
} else {
    echo "✗ File .env.hostinger tidak ditemukan\n";
}

// 2. Generate APP_KEY
if (file_exists('artisan')) {
    echo "Generating APP_KEY...\n";
    exec('php artisan key:generate --force', $output, $return);
    if ($return === 0) {
        echo "✓ APP_KEY berhasil di-generate\n";
    } else {
        echo "✗ Gagal generate APP_KEY\n";
        print_r($output);
    }
}

// 3. Set permissions untuk storage dan bootstrap/cache
$directories = [
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    chmod($dir, 0755);
    echo "✓ Permission set untuk: $dir\n";
}

// 4. Clear cache
echo "Clearing cache...\n";
$commands = [
    'php artisan config:clear',
    'php artisan route:clear',
    'php artisan view:clear',
    'php artisan cache:clear'
];

foreach ($commands as $cmd) {
    exec($cmd, $output, $return);
    if ($return === 0) {
        echo "✓ $cmd berhasil\n";
    }
}

// 5. Run migrations
echo "Running migrations...\n";
exec('php artisan migrate --force', $output, $return);
if ($return === 0) {
    echo "✓ Migrations berhasil dijalankan\n";
} else {
    echo "✗ Gagal menjalankan migrations\n";
    print_r($output);
}

// 6. Seed database
echo "Seeding database...\n";
exec('php artisan db:seed --force', $output, $return);
if ($return === 0) {
    echo "✓ Database seeding berhasil\n";
} else {
    echo "✗ Gagal seeding database\n";
    print_r($output);
}

echo "\n=== Setup Selesai ===\n";
echo "Aplikasi WMS siap digunakan!\n";
echo "Login: admin@msa.com / password\n";
?>
