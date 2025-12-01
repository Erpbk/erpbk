# WhatsApp Bike Assignment Notification System

## 🎯 Overview

This system automatically sends formatted WhatsApp messages to a designated group whenever a bike is assigned to a rider in your ERP system.

### Architecture

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│   Laravel ERP   │  HTTP   │   Node.js        │  WSS    │   WhatsApp      │
│                 │ ───────>│   Microservice   │ ───────>│   Group         │
│  Bike Assigned  │         │   (VenomBot)     │         │                 │
└─────────────────┘         └──────────────────┘         └─────────────────┘
       │
       ├─> Event: BikeAssignedEvent
       ├─> Listener: SendBikeAssignmentNotification
       ├─> Job: SendWhatsAppNotificationJob (Queued)
       └─> Service: WhatsAppService
```

### Components

**Laravel Side:**
- `BikeAssignedEvent` - Fired when bike is assigned
- `SendBikeAssignmentNotification` - Event listener
- `SendWhatsAppNotificationJob` - Queued job for async processing
- `WhatsAppService` - Service to communicate with Node.js microservice

**Node.js Microservice:**
- Express API server
- VenomBot for WhatsApp integration
- Message formatting and delivery
- QR code authentication
- Session management

---

## 📋 Prerequisites

### Laravel (Already Installed)
- PHP >= 8.1
- Laravel >= 10.x
- Composer

### Node.js Microservice (Required)
- Node.js >= 16.0.0
- npm >= 8.0.0
- Chrome/Chromium (auto-installed by Puppeteer)

### WhatsApp
- Active WhatsApp account
- Access to the target WhatsApp group

---

## 🚀 Installation & Setup

### Part 1: Laravel Configuration

#### Step 1: Update .env File

Add these configuration variables to your `.env` file:

```env
# WhatsApp Notifications
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000

# Queue Configuration (if not already set)
QUEUE_CONNECTION=database
```

#### Step 2: Run Queue Migration (if needed)

If you haven't set up database queues:

```bash
php artisan queue:table
php artisan migrate
```

#### Step 3: Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan event:clear
```

#### Step 4: Start Queue Worker

The system uses queued jobs for better performance. Start the queue worker:

```bash
# Development
php artisan queue:work

# Production (use Supervisor - see Production Deployment section)
```

---

### Part 2: Node.js Microservice Setup

#### Step 1: Navigate to Service Directory

```bash
cd whatsapp-service
```

#### Step 2: Install Dependencies

```bash
npm install
```

This will install:
- express (API server)
- venom-bot (WhatsApp integration)
- winston (logging)
- cors, helmet, morgan (middleware)

#### Step 3: Configure Environment

```bash
cp env.template .env
```

Edit `.env`:
```env
PORT=3000
NODE_ENV=production
WHATSAPP_SESSION_NAME=bike-notifications
LOG_LEVEL=info
```

#### Step 4: First Run - Get WhatsApp Groups

Start the service for the first time:

```bash
npm start
```

**What happens:**
1. A QR code will appear in the console
2. Scan it with WhatsApp (Settings > Linked Devices > Link a Device)
3. After authentication, all your WhatsApp groups will be listed

**Example output:**
```
============================================================
AVAILABLE WHATSAPP GROUPS:
============================================================
1. Bike Operations Team
   ID: 1234567890-1234567890@g.us
   Participants: 25
------------------------------------------------------------
2. Fleet Management
   ID: 9876543210-9876543210@g.us
   Participants: 15
------------------------------------------------------------
```

#### Step 5: Configure Group ID

1. Copy the Group ID of your target notification group
2. Add it to `.env`:

```env
WHATSAPP_GROUP_ID=1234567890-1234567890@g.us
```

#### Step 6: Restart Service

```bash
# Stop current process (Ctrl+C)
npm start
```

---

## 🎉 Testing the Integration

### Test 1: Check Service Health

```bash
curl http://localhost:3000/api/health
```

Expected response:
```json
{
  "status": "ok",
  "bot": {
    "status": "connected",
    "connected": true,
    "groupConfigured": true,
    "groupId": "1234567890-1234567890@g.us"
  }
}
```

### Test 2: Send Test Message via API

```bash
curl -X POST http://localhost:3000/api/send-message \
  -H "Content-Type: application/json" \
  -d '{
    "type": "test",
    "message": "🧪 Test message from WhatsApp Service"
  }'
```

### Test 3: Assign a Bike in Laravel

1. Go to your Laravel application
2. Navigate to Bikes management
3. Assign a bike to a rider with status "Active"
4. Check your WhatsApp group for the notification!

**Expected Message Format:**
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

**Field Mapping:**
- **Bike No**: Bike plate number
- **Noon I,d**: Rider's Noon ID (or Rider ID as fallback)
- **Name**: Rider's full name
- **Date**: Assignment date (dd-mm-yy format)
- **Time**: Assignment time (12-hour format)
- **Note**: Auto-generated note
- **Project**: Customer/Project name
- **Emirates**: Location/Hub

---

## 🔧 Production Deployment

### Laravel Queue Worker with Supervisor

Create Supervisor configuration: `/etc/supervisor/conf.d/laravel-queue.conf`

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/laravel/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

### Node.js Service with PM2

#### Option 1: Using PM2 (Recommended)

Install PM2 globally:
```bash
npm install -g pm2
```

Start service:
```bash
cd whatsapp-service
pm2 start ecosystem.config.js
pm2 save
pm2 startup  # Follow the instructions
```

Useful PM2 commands:
```bash
pm2 status              # Check status
pm2 logs whatsapp-service   # View logs
pm2 restart whatsapp-service   # Restart
pm2 monit               # Real-time monitoring
```

#### Option 2: Using systemd

Create `/etc/systemd/system/whatsapp-service.service`:

```ini
[Unit]
Description=WhatsApp Bike Notification Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/whatsapp-service
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=10
StandardOutput=syslog
StandardError=syslog
SyslogIdentifier=whatsapp-service
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable whatsapp-service
sudo systemctl start whatsapp-service
sudo systemctl status whatsapp-service
```

#### Option 3: Using Startup Scripts

**Linux/Mac:**
```bash
cd whatsapp-service
chmod +x start.sh
./start.sh
```

**Windows:**
```bash
cd whatsapp-service
start.bat
```

---

## 📊 Monitoring & Logs

### Laravel Logs

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log

# Check WhatsApp notification logs
grep "WhatsApp" storage/logs/laravel.log
```

### Node.js Service Logs

```bash
# Direct file monitoring
tail -f whatsapp-service/logs/combined.log

# PM2 logs
pm2 logs whatsapp-service

# Specific error logs
tail -f whatsapp-service/logs/error.log
```

### Queue Monitoring

```bash
# Check queue status
php artisan queue:monitor

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

---

## 🐛 Troubleshooting

### Issue 1: Messages Not Sending

**Symptoms:** Bike assigned but no WhatsApp message received

**Checklist:**
1. ✅ Check if notifications are enabled in Laravel `.env`
   ```bash
   php artisan config:cache
   php artisan tinker
   >>> env('WHATSAPP_NOTIFICATIONS_ENABLED')
   ```

2. ✅ Verify Node.js service is running
   ```bash
   curl http://localhost:3000/api/health
   ```

3. ✅ Check queue worker is running
   ```bash
   ps aux | grep "queue:work"
   # or
   sudo supervisorctl status laravel-queue-worker:*
   ```

4. ✅ Check failed jobs
   ```bash
   php artisan queue:failed
   ```

5. ✅ Review logs
   ```bash
   tail -50 storage/logs/laravel.log
   tail -50 whatsapp-service/logs/error.log
   ```

### Issue 2: WhatsApp Session Disconnected

**Symptoms:** Service running but messages fail with "not connected" error

**Solutions:**

1. Check session status:
   ```bash
   curl http://localhost:3000/api/status
   ```

2. Restart session:
   ```bash
   curl -X POST http://localhost:3000/api/restart
   ```

3. Re-authenticate with QR code:
   ```bash
   # Stop service
   pm2 stop whatsapp-service
   
   # Delete session data
   rm -rf whatsapp-service/tokens/
   
   # Start and scan QR code again
   pm2 start whatsapp-service
   pm2 logs whatsapp-service
   ```

### Issue 3: Queue Jobs Not Processing

**Symptoms:** Jobs stuck in queue

**Solutions:**

1. Restart queue worker:
   ```bash
   # Supervisor
   sudo supervisorctl restart laravel-queue-worker:*
   
   # Manual
   killall -9 php
   php artisan queue:work &
   ```

2. Check database connection:
   ```bash
   php artisan db:show
   ```

3. Clear failed jobs:
   ```bash
   php artisan queue:flush
   ```

### Issue 4: Node.js Service Won't Start

**Symptoms:** Service crashes on startup

**Solutions:**

1. Check Node.js version:
   ```bash
   node --version  # Should be >= 16.0.0
   ```

2. Reinstall dependencies:
   ```bash
   cd whatsapp-service
   rm -rf node_modules package-lock.json
   npm install
   ```

3. Check for port conflicts:
   ```bash
   lsof -i :3000
   # Kill the process if needed
   ```

4. Install Chrome dependencies (Linux):
   ```bash
   sudo apt-get install -y \
     gconf-service libasound2 libatk1.0-0 libcairo2 \
     libcups2 libdbus-1-3 libexpat1 libfontconfig1 \
     libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 \
     libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 \
     libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 \
     libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 \
     libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates \
     fonts-liberation libappindicator1 libnss3 lsb-release \
     xdg-utils wget
   ```

---

## 🔒 Security Considerations

### Laravel
- ✅ Keep `.env` file secure and out of version control
- ✅ Use HTTPS for production API calls
- ✅ Implement rate limiting on API endpoints
- ✅ Validate all user inputs
- ✅ Run queue workers with limited user permissions

### Node.js Service
- ✅ Never commit `.env` or WhatsApp session tokens
- ✅ Run service with limited user permissions (not root)
- ✅ Use firewall to restrict API access
- ✅ Keep dependencies updated: `npm audit fix`
- ✅ Consider adding API authentication if exposed to internet
- ✅ Backup WhatsApp session tokens periodically

### Network
- ✅ Run Node.js service on localhost only (production)
- ✅ Use reverse proxy (nginx) for external access
- ✅ Implement IP whitelisting if needed

---

## 📈 Performance Optimization

### Laravel
```php
// config/queue.php - optimize queue settings
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
    ],
],
```

### Node.js
```javascript
// Increase PM2 instances if needed
// ecosystem.config.js
{
  instances: 2,  // For load balancing
  exec_mode: 'cluster'
}
```

### Queue Workers
```bash
# Run multiple queue workers for different queues
php artisan queue:work --queue=notifications,default --sleep=3
```

---

## 🔄 Backup & Recovery

### WhatsApp Session
```bash
# Backup
tar -czf whatsapp-session-backup-$(date +%Y%m%d).tar.gz \
  whatsapp-service/tokens/

# Restore
tar -xzf whatsapp-session-backup-20240101.tar.gz -C whatsapp-service/
```

### Laravel Queue
```bash
# Export failed jobs
php artisan queue:failed --json > failed-jobs-backup.json
```

---

## 📚 API Reference

### Laravel WhatsAppService Methods

```php
// Send bike assignment notification
$whatsAppService->sendBikeAssignmentNotification($bike, $rider, $date, $user);

// Check service health
$whatsAppService->checkServiceHealth();

// Get session status
$whatsAppService->getSessionStatus();
```

### Node.js API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/health` | GET | Health check |
| `/api/status` | GET | Get session status |
| `/api/send-message` | POST | Send message to group |
| `/api/qr-code` | GET | Get QR code |
| `/api/restart` | POST | Restart session |

---

## 🎓 How It Works

### Flow Diagram

```
1. User assigns bike to rider in Laravel UI
                  ↓
2. BikesController updates database
                  ↓
3. Fires BikeAssignedEvent
                  ↓
4. SendBikeAssignmentNotification listener catches event
                  ↓
5. Dispatches SendWhatsAppNotificationJob to queue
                  ↓
6. Queue worker picks up job
                  ↓
7. WhatsAppService formats message
                  ↓
8. HTTP POST to Node.js microservice
                  ↓
9. VenomBot sends message to WhatsApp group
                  ↓
10. Notification appears in WhatsApp group ✅
```

### Code Flow

**Laravel:**
```php
// BikesController.php
$bike->update(['rider_id' => $riderId, 'warehouse' => 'Active']);
event(new BikeAssignedEvent($bike, $rider, now(), Auth::user()));

// Event Listener
SendWhatsAppNotificationJob::dispatch($bike->id, $rider->id)
    ->onQueue('notifications');

// Job executes
$whatsAppService->sendBikeAssignmentNotification(...);

// Service makes HTTP call
Http::post($nodeServiceUrl . '/api/send-message', [...]);
```

**Node.js:**
```javascript
// Express endpoint
app.post('/api/send-message', async (req, res) => {
  const result = await whatsappBot.sendMessageToGroup(message);
  res.json({ success: true });
});

// WhatsApp Bot
async sendMessageToGroup(message) {
  return await this.client.sendText(this.groupId, message);
}
```

---

## 📞 Support

For issues and questions:

1. **Check Logs First**
   - Laravel: `storage/logs/laravel.log`
   - Node.js: `whatsapp-service/logs/`
   - Queue: `php artisan queue:failed`

2. **Verify Configuration**
   - `.env` settings
   - Service connectivity
   - Queue worker status

3. **Test Components Individually**
   - Test Node.js API directly
   - Test queue processing
   - Check WhatsApp session

---

## 📝 Changelog

### Version 1.0.0 (2024-01-01)
- ✨ Initial release
- 🎉 Laravel event-driven architecture
- 🤖 Node.js microservice with VenomBot
- 📱 WhatsApp group notifications
- 🔄 Async queue processing
- 📊 Comprehensive logging
- 🚀 Production deployment guides

---

## 📄 License

MIT

---

## 🙏 Credits

Built with:
- [Laravel](https://laravel.com/)
- [VenomBot](https://github.com/orkestral/venom)
- [Express.js](https://expressjs.com/)
- [Winston](https://github.com/winstonjs/winston)

---

**Need Help?** Check the troubleshooting section or review the logs for detailed error messages.

