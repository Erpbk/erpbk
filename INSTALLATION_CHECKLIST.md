# WhatsApp Bike Notification System - Installation Checklist

> **Print this page and check off items as you complete them**

---

## 📋 Pre-Installation Checklist

- [ ] Laravel ERP system is running
- [ ] PHP >= 8.1 is installed
- [ ] Node.js >= 16.0.0 is installed
- [ ] npm >= 8.0.0 is installed
- [ ] You have access to WhatsApp account
- [ ] You know which WhatsApp group to use
- [ ] You have server/admin access

---

## 🔧 Part 1: Laravel Setup (10 minutes)

### Step 1: Update Environment File
- [ ] Open `.env` file in Laravel root directory
- [ ] Add the following lines:
  ```env
  WHATSAPP_NOTIFICATIONS_ENABLED=true
  WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
  QUEUE_CONNECTION=database
  ```
- [ ] Save and close the file

### Step 2: Setup Database Queue (if not already done)
- [ ] Run: `php artisan queue:table`
- [ ] Run: `php artisan migrate`
- [ ] Verify: Check database for `jobs` and `failed_jobs` tables

### Step 3: Clear Caches
- [ ] Run: `php artisan config:clear`
- [ ] Run: `php artisan cache:clear`
- [ ] Run: `php artisan event:clear`

### Step 4: Start Queue Worker (Development)
- [ ] Run: `php artisan queue:work` in a terminal
- [ ] Keep this terminal open
- [ ] Verify it says "Processing jobs..."

**OR for Production:**
- [ ] Setup Supervisor (see Step 12 below)

---

## 🚀 Part 2: Node.js Microservice Setup (15 minutes)

### Step 5: Navigate to Service Directory
- [ ] Open terminal/command prompt
- [ ] Run: `cd whatsapp-service`
- [ ] Verify: You're in the `whatsapp-service` directory

### Step 6: Install Dependencies
- [ ] Run: `npm install`
- [ ] Wait for installation to complete (2-5 minutes)
- [ ] Verify: No critical errors in output

### Step 7: Create Environment File
- [ ] Run: `cp env.template .env` (Linux/Mac)
  - OR `copy env.template .env` (Windows)
- [ ] Verify: `.env` file now exists in `whatsapp-service/`

### Step 8: Configure Basic Settings
- [ ] Open `whatsapp-service/.env` in text editor
- [ ] Verify/update these settings:
  ```env
  PORT=3000
  NODE_ENV=production
  WHATSAPP_SESSION_NAME=bike-notifications
  LOG_LEVEL=info
  ```
- [ ] Save file (leave `WHATSAPP_GROUP_ID` empty for now)

### Step 9: First Run - Authentication
- [ ] Run: `npm start` in the `whatsapp-service` directory
- [ ] Wait for QR code to appear in console (30-60 seconds)
- [ ] **Important**: Don't close this terminal yet!

### Step 10: Scan QR Code
- [ ] Open WhatsApp on your phone
- [ ] Go to: Settings → Linked Devices
- [ ] Tap: "Link a Device"
- [ ] Scan the QR code displayed in the terminal
- [ ] Wait for "✓ WhatsApp authenticated successfully!" message

### Step 11: Get WhatsApp Group ID
- [ ] Look for "AVAILABLE WHATSAPP GROUPS" section in console
- [ ] Find your target notification group in the list
- [ ] Copy the Group ID (format: `1234567890-1234567890@g.us`)
- [ ] Write it down: ___________________________________

### Step 12: Configure Group ID
- [ ] Stop the Node.js service (Press Ctrl+C)
- [ ] Open `whatsapp-service/.env` again
- [ ] Add the line:
  ```env
  WHATSAPP_GROUP_ID=paste-your-group-id-here
  ```
- [ ] Save the file

### Step 13: Restart Service
- [ ] Run: `npm start` again
- [ ] Verify: No QR code this time (already authenticated)
- [ ] Verify: "WhatsApp bot connected successfully" message

---

## ✅ Part 3: Testing (5 minutes)

### Step 14: Test Node.js Service
- [ ] Open new terminal
- [ ] Run: `curl http://localhost:3000/api/health`
- [ ] Verify: Response shows `"status": "ok"` and `"connected": true`

### Step 15: Test Laravel Configuration
- [ ] Run: `php artisan tinker`
- [ ] Type: `env('WHATSAPP_NOTIFICATIONS_ENABLED')`
- [ ] Verify: Returns `true`
- [ ] Type: `exit`

### Step 16: Test Bike Assignment
- [ ] Go to your Laravel application in browser
- [ ] Navigate to Bikes section
- [ ] Assign a bike to a rider with status "Active"
- [ ] **Check your WhatsApp group!**
- [ ] Verify: Formatted notification message appears

---

## 🎯 Part 4: Production Setup (Optional, 20 minutes)

### Step 17: Setup Supervisor for Queue Worker (Linux/Mac)

**Create Supervisor Configuration:**
- [ ] Run: `sudo nano /etc/supervisor/conf.d/laravel-queue.conf`
- [ ] Paste the following (update paths):
  ```ini
  [program:laravel-queue-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/your/laravel/artisan queue:work --sleep=3 --tries=3
  autostart=true
  autorestart=true
  user=www-data
  numprocs=2
  redirect_stderr=true
  stdout_logfile=/path/to/your/laravel/storage/logs/queue-worker.log
  ```
- [ ] Save and exit (Ctrl+X, Y, Enter)
- [ ] Run: `sudo supervisorctl reread`
- [ ] Run: `sudo supervisorctl update`
- [ ] Run: `sudo supervisorctl start laravel-queue-worker:*`
- [ ] Verify: `sudo supervisorctl status`

**OR for Windows:**
- [ ] Create a scheduled task to run `php artisan queue:work`
- [ ] Set it to run at startup

### Step 18: Setup PM2 for Node.js Service

- [ ] Install PM2: `npm install -g pm2`
- [ ] Navigate: `cd whatsapp-service`
- [ ] Start with PM2: `pm2 start ecosystem.config.js`
- [ ] Save configuration: `pm2 save`
- [ ] Setup auto-start: `pm2 startup`
  - [ ] Follow the command it outputs
- [ ] Verify: `pm2 status`

---

## 📊 Part 5: Verification (5 minutes)

### Step 19: Complete System Check
- [ ] Check Node.js health: `curl http://localhost:3000/api/health`
- [ ] Check WhatsApp status: `curl http://localhost:3000/api/status`
- [ ] Check queue worker: `ps aux | grep "queue:work"` or `sudo supervisorctl status`
- [ ] Check PM2 status: `pm2 status whatsapp-service`

### Step 20: Monitor Logs
- [ ] Laravel logs: `tail -f storage/logs/laravel.log`
- [ ] WhatsApp logs: `tail -f whatsapp-service/logs/combined.log`
- [ ] PM2 logs: `pm2 logs whatsapp-service`
- [ ] Verify: No critical errors

### Step 21: Final Integration Test
- [ ] Assign another bike to a rider
- [ ] Check WhatsApp group within 5 seconds
- [ ] Verify: Message received with correct information
- [ ] Verify: Message is properly formatted

---

## 📝 Post-Installation

### Step 22: Documentation
- [ ] Bookmark these files for reference:
  - [ ] `README_WHATSAPP.md` - Main README
  - [ ] `WHATSAPP_QUICK_START.md` - Quick reference
  - [ ] `WHATSAPP_INTEGRATION_GUIDE.md` - Detailed guide
- [ ] Share relevant docs with your team

### Step 23: Monitoring Setup
- [ ] Add to monitoring tools (if any)
- [ ] Set up alerts for service failures (optional)
- [ ] Document support procedures

### Step 24: Team Training
- [ ] Inform team about new feature
- [ ] Share troubleshooting steps
- [ ] Document who to contact for issues

---

## 🔄 Maintenance Schedule

### Daily
- [ ] Check WhatsApp service is running
- [ ] Verify messages are being sent

### Weekly
- [ ] Review logs for errors
- [ ] Check queue worker status
- [ ] Verify WhatsApp session is connected

### Monthly
- [ ] Update npm packages: `cd whatsapp-service && npm audit fix`
- [ ] Review and archive old logs
- [ ] Test disaster recovery procedure

---

## 🚨 Troubleshooting Quick Reference

### If messages not sending:
1. [ ] Check queue worker: `ps aux | grep queue:work`
2. [ ] Check Node.js: `curl http://localhost:3000/api/health`
3. [ ] Check logs: `tail -f storage/logs/laravel.log`
4. [ ] Check failed jobs: `php artisan queue:failed`

### If WhatsApp disconnected:
1. [ ] Restart Node.js: `pm2 restart whatsapp-service`
2. [ ] Check status: `curl http://localhost:3000/api/status`
3. [ ] If needed, re-scan QR code

### If queue stuck:
1. [ ] Restart queue: `php artisan queue:restart`
2. [ ] Or: `sudo supervisorctl restart laravel-queue-worker:*`
3. [ ] Retry failed: `php artisan queue:retry all`

---

## ✅ Sign-Off

**Installed By**: ___________________________________

**Date**: ___________________________________

**Tested By**: ___________________________________

**Production URL**: ___________________________________

**WhatsApp Group**: ___________________________________

**Notes**: 
___________________________________
___________________________________
___________________________________

---

## 📞 Support Contacts

**Technical Issues**: Check logs first, then refer to `WHATSAPP_INTEGRATION_GUIDE.md`

**Node.js Service**: See `whatsapp-service/README.md`

**Laravel Integration**: See `WHATSAPP_INTEGRATION_GUIDE.md`

---

## 🎉 Congratulations!

Your WhatsApp Bike Notification System is now fully installed and operational!

**Next Steps:**
- Monitor for a few days
- Gather feedback from team
- Adjust settings as needed
- Enjoy automated notifications! 🚀

---

**Installation Complete**: [ ] YES

**Date Completed**: ___________________________________

**System Status**: [ ] Operational [ ] Testing [ ] Issues

---

*For detailed information, see [README_WHATSAPP.md](README_WHATSAPP.md)*

