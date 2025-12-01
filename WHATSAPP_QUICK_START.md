# WhatsApp Bike Notifications - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Laravel Configuration (2 minutes)

Add to your `.env`:
```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
QUEUE_CONNECTION=database
```

Run:
```bash
php artisan config:clear
php artisan queue:work &
```

### Step 2: Node.js Setup (3 minutes)

```bash
cd whatsapp-service
npm install
cp env.template .env
```

Edit `.env`:
```env
PORT=3000
NODE_ENV=production
WHATSAPP_SESSION_NAME=bike-notifications
```

Start:
```bash
npm start
```

### Step 3: Configure WhatsApp

1. **Scan QR Code** - Displayed in console
2. **Copy Group ID** - Listed after authentication
3. **Add to .env**:
   ```env
   WHATSAPP_GROUP_ID=your-group-id-here@g.us
   ```
4. **Restart**: `npm start`

### Step 4: Test

Assign a bike to a rider → Check WhatsApp group! 🎉

---

## 🔍 Verify Setup

```bash
# Check Node.js service
curl http://localhost:3000/api/health

# Check Laravel config
php artisan tinker
>>> env('WHATSAPP_NOTIFICATIONS_ENABLED')

# Check queue worker
ps aux | grep "queue:work"
```

---

## 🚨 Common Issues

### No Message Received?

1. Check queue worker is running: `ps aux | grep queue:work`
2. Check Node.js service: `curl http://localhost:3000/api/health`
3. Check logs: `tail -f storage/logs/laravel.log`

### Service Won't Start?

```bash
cd whatsapp-service
rm -rf node_modules
npm install
npm start
```

### WhatsApp Disconnected?

```bash
curl -X POST http://localhost:3000/api/restart
```

---

## 📚 Need More Details?

See [WHATSAPP_INTEGRATION_GUIDE.md](WHATSAPP_INTEGRATION_GUIDE.md) for comprehensive documentation.

---

## 🎯 Message Format Preview

```
Bike  🏍
Bike No : ABC-1234
Noon I,d : 106399
Name : Asif Ur Rehman
Date : 14-10-25
Time: 02:30 pm
Note : Give to Asif Ur Rehman
Project : Keeta
Emirates : Dubai
```

---

## 🚀 Production Setup

### Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/laravel-queue.conf`:
```ini
[program:laravel-queue-worker]
command=php /path/to/artisan queue:work
autostart=true
autorestart=true
user=www-data
```

Start:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

### Node.js (PM2)

```bash
npm install -g pm2
cd whatsapp-service
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

---

That's it! Your WhatsApp notifications are now live. 🎉

