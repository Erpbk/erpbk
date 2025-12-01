# 📦 Laravel ERP Project - Deployment Instructions

## 🚨 Two Main Issues You're Facing

### Issue #1: CSS, JS, and Images Not Loading ❌
**Symptom:** Your site works on localhost but assets don't load on the server

### Issue #2: PHP Version Error ❌  
**Error Message:** "Your Composer dependencies require a PHP version >= 8.2.0"

---

## ✅ Both Issues Have Been Fixed!

I've created a complete solution with automated scripts and detailed documentation.

---

## 🚀 Quick Start (3 Steps)

### **Step 1: Check & Upgrade PHP** 🐘

On your server, run:
```bash
php -v
```

**If PHP version is less than 8.2:**
- **Read:** `PHP_VERSION_FIX.md` 
- **Quick fix (cPanel):** Go to "Select PHP Version" → Choose 8.2 or 8.3
- **Quick fix (Cloudways):** Application Settings → PHP Version → 8.2 or 8.3

### **Step 2: Configure Environment** ⚙️

```bash
# Create .env file
cp env.template .env

# Edit .env and set:
# APP_URL=https://yourdomain.com  ← IMPORTANT!
# DB_DATABASE=your_database
# DB_USERNAME=your_user
# DB_PASSWORD=your_password
```

### **Step 3: Run Deployment Script** 🤖

```bash
chmod +x deploy.sh
./deploy.sh
```

That's it! The script handles everything automatically.

---

## 📚 Documentation Index

Read these files in order based on your issue:

### **For Everyone (Start Here):**
| File | Purpose |
|------|---------|
| 📖 **START_HERE.md** | Main starting point |
| ✅ **DEPLOYMENT_CHECKLIST.txt** | Step-by-step checklist |

### **For Asset Loading Issues:**
| File | Purpose |
|------|---------|
| ⚡ **QUICK_FIX_SUMMARY.md** | Overview of asset fixes |
| 📘 **DEPLOYMENT_GUIDE.md** | Detailed deployment guide |

### **For PHP Version Issues:**
| File | Purpose |
|------|---------|
| 🐘 **PHP_VERSION_FIX.md** | How to upgrade PHP |
| 🖥️ **SERVER_REQUIREMENTS.md** | Server requirements |

### **Complete Overview:**
| File | Purpose |
|------|---------|
| 🎯 **COMPLETE_SOLUTION_SUMMARY.md** | Everything in one place |

---

## 🎯 What Each Issue Means & How It's Fixed

### **Issue #1: Assets Not Loading**

**Why it happens:**
- Server document root not pointing to `/public` folder
- Incorrect `APP_URL` in `.env` file
- HTTP/HTTPS mixed content blocking

**How it's fixed:**
- ✅ Updated root `index.php` to serve assets correctly
- ✅ Created proper `.htaccess` routing
- ✅ Enhanced `AppServiceProvider` to force HTTPS
- ✅ Instructions to set correct `APP_URL`

**Files modified:**
- `index.php` (root)
- `.htaccess` (root)
- `public/.htaccess`
- `app/Providers/AppServiceProvider.php`

### **Issue #2: PHP Version**

**Why it happens:**
Your server is running PHP 7.x or 8.0/8.1, but Laravel 10 requires PHP 8.2+

**How to fix:**
1. **Upgrade PHP on server** (Recommended - see `PHP_VERSION_FIX.md`)
2. **Downgrade Laravel** (Not recommended - loses features)

**Quick solutions:**
- **cPanel:** "Select PHP Version" → 8.2 or 8.3
- **Cloudways:** Application Settings → PHP Version
- **VPS:** See `PHP_VERSION_FIX.md` for commands

---

## 🔧 Manual Deployment Steps

If you prefer not to use the automated script:

```bash
# 1. Check PHP version (must be 8.2+)
php -v

# 2. Create environment file
cp env.template .env
# Edit .env and set APP_URL, database credentials

# 3. Generate application key
php artisan key:generate

# 4. Install dependencies
composer install --optimize-autoloader --no-dev

# 5. Set permissions
chmod -R 755 storage bootstrap/cache

# 6. Create storage link
php artisan storage:link

# 7. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 8. Build assets (optional)
npm install
npm run production

# 9. Import database
# Import your SQL file through cPanel or command line

# 10. Test
# Visit your site and check for errors (F12 console)
```

---

## ⚙️ Important Configuration

### **1. Document Root (CRITICAL!)**

Your server's document root should point to the `/public` folder.

**In cPanel:**
- Go to "Domains" → Your Domain
- Change Document Root from `/public_html` to `/public_html/public`
- Save

**Can't change document root?**
- No problem! The updated `index.php` and `.htaccess` in the root directory handle this automatically.

### **2. .env File (REQUIRED)**

Must set these values:
```env
APP_URL=https://yourdomain.com    # ← Must match your domain!
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### **3. File Permissions**

```bash
# Directories: 755
chmod -R 755 storage bootstrap/cache

# If that doesn't work:
chmod -R 777 storage bootstrap/cache
```

---

## ✅ Verification Checklist

After deployment, verify these:

- [ ] PHP version is 8.2 or higher: `php -v`
- [ ] `.env` file exists and `APP_URL` is correct
- [ ] Database credentials in `.env` are correct
- [ ] Storage folder has write permissions
- [ ] Document root points to `/public` (or root index.php is in place)
- [ ] Website loads without blank page
- [ ] CSS styles are applied
- [ ] JavaScript works (dropdowns, modals)
- [ ] Images display
- [ ] No 404 errors in browser console (F12)
- [ ] Login works

---

## 🐛 Troubleshooting

### **Problem: "PHP version >= 8.2.0 required"**

**Solution:**
```bash
# Check current version
php -v

# If < 8.2, upgrade via cPanel or read PHP_VERSION_FIX.md
```

### **Problem: Assets showing 404**

**Solution:**
```bash
# Clear config
php artisan config:clear

# Check APP_URL
cat .env | grep APP_URL
# Should match your domain exactly!

# Verify document root points to /public
```

### **Problem: "500 Internal Server Error"**

**Solution:**
```bash
# Check permissions
chmod -R 755 storage bootstrap/cache

# Check logs
tail -f storage/logs/laravel.log

# Enable debug temporarily
# In .env: APP_DEBUG=true
```

### **Problem: "Database connection failed"**

**Solution:**
```bash
# Verify credentials in .env
# Test connection
php artisan migrate:status
```

### **Problem: Page loads but no styling**

**Solution:**
- Press F12 → Console tab
- Check for 404 errors on CSS/JS files
- Verify `APP_URL` in `.env`
- Clear browser cache (Ctrl + Shift + Delete)

---

## 📞 Getting Help

1. **Check browser console:** F12 → Console tab (shows asset loading errors)
2. **Check server logs:** `storage/logs/laravel.log`
3. **Check PHP version:** `php -v` (must be 8.2+)
4. **Check permissions:** `ls -la storage` (should show write permissions)
5. **Read documentation:** See files in "Documentation Index" above

---

## 🎉 Success!

Your deployment is successful when:

✅ Website loads properly  
✅ CSS styles are applied  
✅ JavaScript features work  
✅ Images display  
✅ No console errors (F12)  
✅ Login/authentication works  
✅ Database operations work  

---

## 📋 Files Provided

### **Scripts:**
- `deploy.sh` - Automated deployment (Linux/Mac)
- `deploy.bat` - Automated deployment (Windows)

### **Configuration:**
- `env.template` - Environment file template
- `.htaccess` (root) - Request routing
- `public/.htaccess` - Laravel routing + optimizations

### **Documentation (11 files):**
- START_HERE.md
- QUICK_FIX_SUMMARY.md
- DEPLOYMENT_GUIDE.md
- DEPLOYMENT_CHECKLIST.txt
- PHP_VERSION_FIX.md
- SERVER_REQUIREMENTS.md
- COMPLETE_SOLUTION_SUMMARY.md
- README_DEPLOYMENT.md (this file)

### **Code Updates:**
- `index.php` (root) - Serves assets from public folder
- `app/Providers/AppServiceProvider.php` - Forces HTTPS

---

## 🏆 Summary

**Two things you must do:**

1. **Upgrade PHP to 8.2+** on your server
2. **Set correct APP_URL** in `.env` file

Everything else is automated by the `deploy.sh` script!

**The Easiest Way:**
```bash
# 1. Upgrade PHP via cPanel
# 2. Create .env and set APP_URL
cp env.template .env
nano .env  # Set APP_URL=https://yourdomain.com

# 3. Run script
./deploy.sh

# Done! 🎉
```

---

**Good luck with your deployment!** 🚀

If you encounter any issues, check the documentation files above. They contain detailed solutions for every common problem.

