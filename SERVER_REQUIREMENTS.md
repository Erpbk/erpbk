# 🖥️ Server Requirements for This Laravel Project

## Minimum Requirements

Your server **MUST** meet these requirements:

### ✅ **Required:**

| Requirement | Minimum Version | Recommended |
|------------|-----------------|-------------|
| **PHP** | 8.2.0 | 8.3.x |
| **MySQL** | 5.7 | 8.0+ |
| **Composer** | 2.0 | Latest |
| **Apache/Nginx** | Any recent | Latest |

### ✅ **Required PHP Extensions:**

```
✓ BCMath
✓ Ctype
✓ cURL
✓ DOM
✓ Fileinfo
✓ Filter
✓ Hash
✓ Mbstring
✓ OpenSSL
✓ PCRE
✓ PDO
✓ Session
✓ Tokenizer
✓ XML
✓ GD or Imagick (for image processing)
✓ Zip
```

---

## 🔍 How to Check Your Server

### **Check PHP Version:**
```bash
php -v
```

Should output something like:
```
PHP 8.2.15 (cli) (built: Jan 10 2024 12:00:00)
```

### **Check PHP Extensions:**
```bash
php -m
```

This lists all installed extensions.

### **Check via PHP Info (Web):**

Create `public/phpinfo.php`:
```php
<?php phpinfo(); ?>
```

Visit: `https://yourdomain.com/phpinfo.php`

**⚠️ DELETE this file after checking!**

---

## 🚨 Common Issues

### ❌ "Your Composer dependencies require PHP >= 8.2.0"

**Problem:** Your server has PHP 7.x or 8.0/8.1

**Solution:** 
1. See `PHP_VERSION_FIX.md` for upgrade instructions
2. Upgrade PHP in cPanel → "Select PHP Version" → Choose 8.2 or 8.3

### ❌ "Extension XXX is missing"

**Problem:** Required PHP extension not installed

**Solution:**
- **cPanel:** Go to "Select PHP Version" → Check missing extensions
- **VPS:** `sudo apt install php8.2-xxx` (replace xxx with extension name)

### ❌ "Unable to connect to database"

**Problem:** MySQL version too old or wrong credentials

**Solution:**
1. Check MySQL version: `mysql --version`
2. Verify .env database credentials
3. Ensure database user has proper permissions

---

## 📦 Recommended Hosting Providers

These hosting providers support PHP 8.2+ out of the box:

### **Shared Hosting:**
- ✅ Cloudways (Recommended)
- ✅ SiteGround
- ✅ A2 Hosting
- ✅ Hostinger
- ✅ InMotion Hosting

### **VPS/Cloud:**
- ✅ DigitalOcean
- ✅ Vultr
- ✅ Linode
- ✅ AWS Lightsail
- ✅ Google Cloud

### **Managed Laravel Hosting:**
- ✅ Laravel Forge (with any VPS)
- ✅ Ploi
- ✅ Cloudways
- ✅ Laravel Vapor (AWS)

---

## 🔧 Server Configuration

### **Apache Requirements:**

- `mod_rewrite` enabled
- `.htaccess` files allowed
- `AllowOverride All` in VirtualHost

### **Nginx Requirements:**

See `DEPLOYMENT_GUIDE.md` for Nginx configuration example.

### **File Permissions:**

```bash
# Directories: 755
chmod -R 755 storage bootstrap/cache public

# Files: 644
find . -type f -exec chmod 644 {} \;

# Storage and cache must be writable
chmod -R 775 storage bootstrap/cache
```

---

## 📊 Checking Your Current Setup

Run this diagnostic script on your server:

```bash
php -r "
echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;
echo 'Required: 8.2.0+' . PHP_EOL . PHP_EOL;

\$required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip', 'gd'];
\$missing = [];

foreach (\$required as \$ext) {
    if (!extension_loaded(\$ext)) {
        \$missing[] = \$ext;
    }
}

if (empty(\$missing)) {
    echo '✓ All required extensions are installed!' . PHP_EOL;
} else {
    echo '✗ Missing extensions: ' . implode(', ', \$missing) . PHP_EOL;
}
"
```

---

## ⚙️ Optimal PHP Configuration (php.ini)

Recommended settings:

```ini
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
max_input_time = 300
```

Change these in:
- **cPanel:** "Select PHP Version" → "Options"
- **VPS:** Edit `/etc/php/8.2/fpm/php.ini`

---

## 🎯 Pre-Deployment Checklist

Before deploying, ensure:

- [ ] Server has PHP 8.2 or higher
- [ ] All required PHP extensions installed
- [ ] MySQL 5.7+ or MariaDB 10.3+
- [ ] Composer 2.x installed
- [ ] SSH access (for running commands)
- [ ] Document root can be changed to `/public` (or use root index.php workaround)
- [ ] HTTPS/SSL certificate installed (Let's Encrypt is free)
- [ ] Sufficient storage space (minimum 500MB)
- [ ] Email sending configured (optional, for notifications)

---

## 📞 What If My Server Doesn't Meet Requirements?

### **Option 1: Upgrade (Recommended)**
Ask your hosting provider to upgrade PHP to 8.2

### **Option 2: Switch Hosting**
Move to a provider that supports PHP 8.2+ (Cloudways recommended)

### **Option 3: Downgrade Project (Not Recommended)**
See `PHP_VERSION_FIX.md` for instructions to downgrade Laravel

---

## ✅ Verification After Deployment

```bash
# Check Laravel version
php artisan --version

# Check if routes are registered
php artisan route:list

# Check database connection
php artisan migrate:status

# Clear all caches
php artisan optimize:clear
```

---

## 🚀 Performance Optimization (Optional)

For production, enable:

```bash
# OPcache (in php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000

# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

---

**Bottom Line:** Your server needs PHP 8.2+ to run this Laravel 10 project. Most modern hosting providers support this. If yours doesn't, it's time to upgrade! 🚀

