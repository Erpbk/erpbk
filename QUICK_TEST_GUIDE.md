# Quick Test Guide - Verify WhatsApp Integration

## 🚀 5-Minute Verification

Follow these steps to verify your WhatsApp integration is working:

---

## ✅ **Method 1: Automated Verification (Easiest)**

### **Step 1: Run Verification Script**

Open Command Prompt in your Laravel root directory and run:

```bash
verify-whatsapp.bat
```

This will check:
- ✅ All files exist
- ✅ Configuration is correct
- ✅ Node.js service is responding
- ✅ Event registration
- ✅ Queue setup

**Result:** You'll see [OK] or [FAIL] for each check.

---

## 🔍 **Method 2: Manual Verification (Detailed)**

### **Test 1: Check Files Exist** ⏱️ 30 seconds

```bash
# Check Laravel files
dir app\Events\BikeAssignedEvent.php
dir app\Listeners\SendBikeAssignmentNotification.php
dir app\Jobs\SendWhatsAppNotificationJob.php
dir app\Services\WhatsAppService.php

# Check Node.js files
dir whatsapp-service\server.js
dir whatsapp-service\whatsapp-bot.js
```

**Expected:** All files should be found.

---

### **Test 2: Verify Laravel Configuration** ⏱️ 1 minute

```bash
# Check .env file
type .env | findstr "WHATSAPP"
```

**Expected Output:**
```
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
```

**Test with Tinker:**
```bash
php artisan tinker
```

```php
// Inside Tinker
env('WHATSAPP_NOTIFICATIONS_ENABLED')
// Should return: true

env('WHATSAPP_NODE_SERVICE_URL')  
// Should return: "http://localhost:3000"

exit
```

---

### **Test 3: Start Node.js Service** ⏱️ 1 minute

```bash
cd whatsapp-service
npm start
```

**Expected Output:**
```
WhatsApp Service running on port 3000
Environment: production
Initializing WhatsApp bot...
✓ WhatsApp authenticated successfully!
WhatsApp bot connected successfully
```

**Keep this terminal open!**

---

### **Test 4: Test API Health** ⏱️ 30 seconds

**Open a NEW Command Prompt** and run:

```bash
curl http://localhost:3000/api/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "bot": {
    "status": "connected",
    "connected": true,
    "groupConfigured": true
  }
}
```

---

### **Test 5: Send Test Message** ⏱️ 30 seconds

```bash
curl -X POST http://localhost:3000/api/send-message -H "Content-Type: application/json" -d "{\"type\":\"test\",\"message\":\"Test message - verification\"}"
```

**Expected:**
- Response: `"success": true`
- **Check your WhatsApp group** - you should see: "Test message - verification"

---

### **Test 6: Start Queue Worker** ⏱️ 30 seconds

**Open another NEW Command Prompt** and run:

```bash
php artisan queue:work
```

**Expected Output:**
```
INFO  Processing jobs from the [default] queue.
```

**Keep this terminal open!**

---

### **Test 7: End-to-End Test** ⏱️ 2 minutes

**Now you should have 3 terminals open:**
1. ✅ Node.js service running
2. ✅ Queue worker running
3. ✅ Your main command prompt

**Test the complete flow:**

1. Open your Laravel application in browser
2. Go to Bikes section
3. Assign a bike to a rider:
   - Select any bike
   - Select any rider
   - Set warehouse status to **"Active"**
   - Click Save/Assign

4. **Within 5 seconds**, check your WhatsApp group

**Expected Message:**
```
Bike  🏍
Bike No : ABC-1234
Noon I,d : 106399
Name : Asif Ur Rehman
Date : 18-10-24
Time: 02:30 pm
Note : Give to Asif Ur Rehman
Project : Keeta
Emirates : Dubai
```

---

## 🎯 **Quick Checklist**

Mark each as you complete:

- [ ] Verification script shows all [OK]
- [ ] Laravel .env has WhatsApp settings
- [ ] Node.js service starts without errors
- [ ] API health check returns success
- [ ] Test message appears in WhatsApp
- [ ] Queue worker is running
- [ ] Bike assignment sends notification
- [ ] Message format is correct

---

## 🐛 **Common Issues & Quick Fixes**

### **Issue 1: Node.js service won't start**

**Solution:**
```bash
cd whatsapp-service
npm install
npm start
```

---

### **Issue 2: API not responding**

**Check if service is running:**
```bash
curl http://localhost:3000/api/health
```

**If it fails:**
- Make sure Node.js service is running
- Check no other service is using port 3000

---

### **Issue 3: Queue not processing**

**Solution:**
```bash
# Restart queue worker
php artisan queue:restart

# Or start manually
php artisan queue:work
```

---

### **Issue 4: No WhatsApp notification**

**Checklist:**
1. ✅ Node.js service running?
2. ✅ Queue worker running?
3. ✅ WhatsApp connected? (check API status)
4. ✅ Group ID configured in `.env`?

**Check logs:**
```bash
# Laravel logs
type storage\logs\laravel.log | findstr "WhatsApp"

# Node.js logs
type whatsapp-service\logs\combined.log
```

---

### **Issue 5: Configuration not taking effect**

**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan queue:restart
```

---

## 📊 **View Logs in Real-Time**

### **Laravel Logs:**
```bash
# In PowerShell
Get-Content -Path "storage\logs\laravel.log" -Wait -Tail 20
```

### **Node.js Logs:**
```bash
# In PowerShell
Get-Content -Path "whatsapp-service\logs\combined.log" -Wait -Tail 20
```

---

## ✅ **Success Indicators**

Your system is working correctly if:

1. ✅ **Verification script** shows all green [OK]
2. ✅ **Node.js API** responds to health check
3. ✅ **Test message** appears in WhatsApp group
4. ✅ **Queue worker** processes jobs without errors
5. ✅ **Bike assignment** triggers notification within 5 seconds
6. ✅ **Message format** matches your specification

---

## 🔄 **Production Checklist**

Before going live:

- [ ] All tests pass
- [ ] WhatsApp session is stable (test for 24 hours)
- [ ] Queue worker setup with Supervisor/PM2
- [ ] Node.js service setup with PM2
- [ ] Logs are being monitored
- [ ] Backup WhatsApp session tokens
- [ ] Team knows how to check/restart services

---

## 📞 **Quick Reference Commands**

### **Start Services:**
```bash
# Terminal 1: Node.js service
cd whatsapp-service
npm start

# Terminal 2: Queue worker
php artisan queue:work
```

### **Check Status:**
```bash
# API health
curl http://localhost:3000/api/health

# WhatsApp status
curl http://localhost:3000/api/status

# Queue status
php artisan queue:monitor
```

### **View Logs:**
```bash
# Laravel
type storage\logs\laravel.log

# Node.js
type whatsapp-service\logs\combined.log

# Failed jobs
php artisan queue:failed
```

### **Restart Services:**
```bash
# Clear Laravel cache
php artisan config:clear

# Restart queue
php artisan queue:restart

# Restart Node.js (Ctrl+C then npm start)
```

---

## 🎉 **You're Done!**

If all tests pass, your WhatsApp integration is working correctly!

**Next Steps:**
1. Monitor for first few days
2. Set up production deployment (PM2/Supervisor)
3. Train team on basic troubleshooting
4. Enjoy automated notifications! 🚀

---

## 📚 **More Information**

- Detailed guide: [WHATSAPP_INTEGRATION_GUIDE.md](WHATSAPP_INTEGRATION_GUIDE.md)
- Full verification: [VERIFICATION_CHECKLIST.md](VERIFICATION_CHECKLIST.md)
- Quick start: [WHATSAPP_QUICK_START.md](WHATSAPP_QUICK_START.md)
- Message format: [MESSAGE_FORMAT_EXAMPLE.md](MESSAGE_FORMAT_EXAMPLE.md)

---

**Need Help?** Check the troubleshooting section above or review the logs!

