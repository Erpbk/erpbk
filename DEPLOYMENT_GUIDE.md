# Laravel Deployment Guide - Fix CSS, JS, and Images Not Loading

## Problem
When deploying your Laravel application to a server, CSS, JavaScript, and images are not loading even though they work perfectly on localhost.

## Root Causes
1. **Document Root not set to `/public`** - Server points to project root instead of the public folder
2. **Incorrect APP_URL** - The application URL doesn't match your domain
3. **Missing .env configuration** - Environment variables not properly set for production
4. **File permissions** - Incorrect permissions on storage and cache folders
5. **Storage link not created** - Symbolic link for storage folder missing

---

## Solution Steps

### Step 1: Configure Your .env File

1. Copy the `.env.example` file to `.env` if you haven't already:
   ```bash
   cp .env.example .env
   ```

2. Edit your `.env` file and set these values:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   
   # Database settings
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

3. **IMPORTANT**: Replace `https://yourdomain.com` with your actual domain URL

### Step 2: Set Proper Document Root (BEST SOLUTION)

**Option A: Change Document Root in cPanel/Server (RECOMMENDED)**

1. Log into your cPanel or hosting control panel
2. Find "Document Root" or "Web Root" settings
3. Change it from `/home/username/public_html` to `/home/username/public_html/public`
4. Save changes

**Why this is best**: Your assets will load correctly without any workarounds.

**Option B: If You Cannot Change Document Root**

If your hosting doesn't allow changing the document root, the root `.htaccess` and `index.php` files have been updated to handle this automatically.

The updated `index.php` in the root will:
- Serve static files (CSS, JS, images) directly from the public folder
- Route all other requests through Laravel properly

### Step 3: Set Correct File Permissions

Run these commands in your project root via SSH:

```bash
# Set folder permissions
chmod -R 755 storage bootstrap/cache

# Set ownership (replace 'www-data' with your web server user if different)
chown -R www-data:www-data storage bootstrap/cache

# Or if you don't have root access:
chmod -R 777 storage bootstrap/cache
```

### Step 4: Create Storage Symlink

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public` for file uploads.

### Step 5: Clear and Cache Configuration

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache for production (optional, improves performance)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 6: Run Composer Install

```bash
composer install --optimize-autoloader --no-dev
```

The `--no-dev` flag excludes development dependencies in production.

### Step 7: Build Assets (If Using Mix/Vite)

If you've made changes to CSS/JS:

```bash
# For Laravel Mix
npm install
npm run production

# For Vite (if using Vite)
npm install
npm run build
```

---

## Verification Steps

After completing the above steps:

1. **Check your browser console** (F12) for any 404 errors
2. **Verify APP_URL** matches your domain exactly
3. **Test asset loading** by visiting: `https://yourdomain.com/assets/vendor/css/core.css`
4. **Check file permissions**: `ls -la storage` should show writable permissions

---

## Common Issues & Fixes

### Issue 1: Assets still showing 404

**Solution**: Check if your `.env` file has the correct `APP_URL`:
```bash
php artisan config:clear
```

### Issue 2: Mixed Content Error (HTTP/HTTPS)

If your site uses HTTPS but assets load as HTTP:

Add this to `app/Providers/AppServiceProvider.php` in the `boot()` method:

```php
public function boot()
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

### Issue 3: .htaccess not working

Ensure `mod_rewrite` is enabled on your server:
```bash
# For Apache
sudo a2enmod rewrite
sudo service apache2 restart
```

### Issue 4: Images in storage folder not loading

Make sure you created the storage link:
```bash
php artisan storage:link
```

---

## For Cloudways Users

If deploying to Cloudways, the `.htaccess` in the `public` folder already has Cloudways-specific HTTPS rules. Just ensure:

1. Your application URL in Cloudways settings matches your domain
2. SSL is enabled in Cloudways
3. Document root is set to `/public_html/public`

---

## Quick Checklist

- [ ] `.env` file configured with correct APP_URL
- [ ] Document root points to `/public` folder (or root .htaccess is in place)
- [ ] File permissions set correctly (755 for folders, 644 for files)
- [ ] Storage symlink created
- [ ] Caches cleared
- [ ] Composer dependencies installed
- [ ] Assets compiled (npm run production)
- [ ] Browser cache cleared

---

## Server Configuration Examples

### Apache Virtual Host Example

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/yourproject/public

    <Directory /var/www/yourproject/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/yourproject/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Need More Help?

If assets still don't load:

1. Check your browser's Network tab (F12 → Network) to see the exact URLs being requested
2. Verify the file actually exists in the `public` folder
3. Check server error logs for specific errors
4. Ensure your hosting supports PHP 8.2+ (required by this project)

---

## Summary

The main fix is ensuring your server's document root points to the `/public` folder. If you cannot change that, the updated root `index.php` file will handle routing static assets correctly.

**Most Important**: Always set the correct `APP_URL` in your `.env` file!

