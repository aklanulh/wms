# Panduan Deployment WMS ke Hostinger

## Persiapan Database

1. **Login ke cPanel Hostinger**
2. **Buka phpMyAdmin**
3. **Gunakan database yang sudah ada:**
   - Database: `u919556019_wms`
   - Username: `u919556019_supermsa`
   - Password: `Aa153456!`

## Langkah-langkah Deployment

### 1. Upload Files ke Hostinger

1. **Download/Clone repository dari GitHub:**
   ```bash
   git clone https://github.com/aklanulh/wms.git
   ```

2. **Upload semua file ke public_html di cPanel File Manager**
   - Upload semua file dari folder wms ke public_html
   - Pastikan file `index.php` ada di root public_html (bukan di folder public)

### 2. Setup Environment

1. **Rename file .env.hostinger menjadi .env**
2. **Edit file .env jika diperlukan:**
   - Sesuaikan APP_URL dengan domain Anda
   - Pastikan database credentials sudah benar

### 3. Install Dependencies

1. **Buka Terminal di cPanel atau gunakan SSH**
2. **Jalankan composer install:**
   ```bash
   cd public_html
   composer install --no-dev --optimize-autoloader
   ```

### 4. Setup Laravel

1. **Jalankan setup script:**
   ```bash
   php setup_hostinger.php
   ```

   Atau jalankan manual:
   ```bash
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### 5. Set Permissions

Pastikan folder berikut memiliki permission 755:
- storage/
- bootstrap/cache/

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 6. Konfigurasi Domain

1. **Jika menggunakan subdomain atau domain utama:**
   - Update APP_URL di file .env
   - Pastikan domain mengarah ke public_html

2. **Jika menggunakan subfolder:**
   - Buat folder di public_html (misal: wms)
   - Upload file ke folder tersebut
   - Update APP_URL: https://yourdomain.com/wms

## Testing

1. **Buka website di browser**
2. **Login dengan:**
   - Email: admin@msa.com
   - Password: password

## Troubleshooting

### Error 500
- Cek file .env sudah benar
- Cek permission folder storage dan bootstrap/cache
- Cek error log di cPanel

### Database Connection Error
- Pastikan credentials database benar di .env
- Pastikan database sudah dibuat di cPanel

### Composer Error
- Pastikan PHP version minimal 8.1
- Jalankan: `composer install --no-dev`

### Cache Issues
- Jalankan: `php artisan config:clear`
- Jalankan: `php artisan cache:clear`

## File Penting

- `index.php` - Entry point (harus di root public_html)
- `.env` - Environment configuration
- `.htaccess` - URL rewriting rules
- `setup_hostinger.php` - Setup script

## Backup

Selalu backup database dan files sebelum update:
```bash
# Backup database
mysqldump -u username -p database_name > backup.sql

# Backup files
tar -czf backup_files.tar.gz public_html/
```

## Support

Jika ada masalah, cek:
1. Error logs di cPanel
2. Laravel logs di storage/logs/
3. PHP error logs

---
**Catatan:** Pastikan PHP version di Hostinger minimal 8.1 untuk Laravel 11
