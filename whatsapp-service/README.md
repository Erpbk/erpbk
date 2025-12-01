# WhatsApp Bike Notification Service

A Node.js microservice that sends automated WhatsApp notifications to a group whenever a bike is assigned to a rider in your ERP system.

## Features

- 🤖 Automated WhatsApp notifications using VenomBot
- 🏍️ Bike assignment notifications with formatted messages
- 📱 QR code authentication
- 🔄 Auto-reconnection handling
- 📊 Health check and status endpoints
- 📝 Comprehensive logging with Winston

## Prerequisites

- Node.js >= 16.0.0
- npm >= 8.0.0
- Google Chrome/Chromium (installed automatically by Puppeteer)
- WhatsApp account

## Installation

1. Navigate to the whatsapp-service directory:
```bash
cd whatsapp-service
```

2. Install dependencies:
```bash
npm install
```

3. Create environment configuration:
```bash
cp env.template .env
```

4. Edit `.env` and configure your settings:
```
PORT=3000
NODE_ENV=production
WHATSAPP_SESSION_NAME=bike-notifications
LOG_LEVEL=info
```

## First-Time Setup

### 1. Start the Service

```bash
npm start
```

### 2. Scan QR Code

When you start the service for the first time, it will display a QR code in the console:

```
==================================================
SCAN THIS QR CODE WITH YOUR WHATSAPP:
==================================================
[QR CODE ASCII ART WILL BE DISPLAYED HERE]
==================================================
```

- Open WhatsApp on your phone
- Go to Settings > Linked Devices
- Tap "Link a Device"
- Scan the QR code displayed in the console

### 3. Get Your Group ID

After authentication, the service will list all your WhatsApp groups:

```
============================================================
AVAILABLE WHATSAPP GROUPS:
============================================================
1. Bike Operations Team
   ID: 1234567890@g.us
   Participants: 25
------------------------------------------------------------
2. Fleet Management
   ID: 9876543210@g.us
   Participants: 15
------------------------------------------------------------
```

### 4. Configure Group ID

Copy the Group ID of your target group and add it to your `.env` file:

```
WHATSAPP_GROUP_ID=1234567890@g.us
```

### 5. Restart the Service

```bash
npm start
```

## API Endpoints

### Health Check
```
GET /api/health
```

Response:
```json
{
  "status": "ok",
  "bot": {
    "status": "connected",
    "connected": true,
    "groupConfigured": true,
    "groupId": "1234567890@g.us",
    "sessionName": "bike-notifications"
  },
  "timestamp": "2024-01-01T12:00:00.000Z"
}
```

### Get Status
```
GET /api/status
```

### Send Message
```
POST /api/send-message
Content-Type: application/json

{
  "type": "bike_assignment",
  "message": "Your formatted message here",
  "data": {
    "rider_id": 123,
    "rider_name": "John Doe",
    "bike_plate": "ABC-1234",
    "bike_id": 456
  }
}
```

### Get QR Code
```
GET /api/qr-code
```

### Restart Session
```
POST /api/restart
```

## Message Format

When a bike is assigned, the service sends a formatted message like this:

```
🏍️ *BIKE ASSIGNMENT NOTIFICATION* 🏍️

━━━━━━━━━━━━━━━━━━━━━━

👤 *RIDER DETAILS*
• Name: *John Doe*
• Rider ID: RD001
• Contact: +971501234567
• Customer: ABC Company
• Supervisor: Fleet Manager
• Hub: Dubai

🏍️ *BIKE DETAILS*
• Plate Number: *ABC-1234*
• Bike Code: BK001
• Model: Honda CBR
• Color: Red
• Leasing Company: XYZ Leasing
• Chassis: CH1234567890

📋 *ASSIGNMENT INFO*
• Date: 01 Jan 2024, 02:30 PM
• Status: *Active*
• Assigned By: Admin User

━━━━━━━━━━━━━━━━━━━━━━
_System generated notification_
🕐 01 Jan 2024, 02:30 PM
```

## Running in Production

### Using PM2 (Recommended)

1. Install PM2 globally:
```bash
npm install -g pm2
```

2. Start the service:
```bash
pm2 start server.js --name whatsapp-service
```

3. Save PM2 configuration:
```bash
pm2 save
```

4. Setup PM2 to start on system boot:
```bash
pm2 startup
```

5. Monitor the service:
```bash
pm2 monit
```

### Using systemd (Linux)

Create a systemd service file `/etc/systemd/system/whatsapp-service.service`:

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

## Development Mode

Run with auto-reload using nodemon:

```bash
npm run dev
```

## Troubleshooting

### QR Code Not Appearing

1. Check if Chrome/Chromium is installed
2. Ensure you have proper permissions to create browser instances
3. Check logs in `logs/error.log`

### Session Keeps Disconnecting

1. Ensure stable internet connection
2. Check if WhatsApp is not logged in elsewhere
3. Try restarting the session via API: `POST /api/restart`

### Messages Not Sending

1. Verify `WHATSAPP_GROUP_ID` is correctly set in `.env`
2. Check if you're still a member of the group
3. Verify bot status: `GET /api/status`

### Chrome/Chromium Issues on Linux

Install required dependencies:
```bash
# Ubuntu/Debian
sudo apt-get install -y \
  gconf-service libasound2 libatk1.0-0 libc6 libcairo2 \
  libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 \
  libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 \
  libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 \
  libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 \
  libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 \
  libxss1 libxtst6 ca-certificates fonts-liberation \
  libappindicator1 libnss3 lsb-release xdg-utils wget
```

## Logs

Logs are stored in the `logs/` directory:
- `combined.log` - All logs
- `error.log` - Error logs only

View logs in real-time:
```bash
tail -f logs/combined.log
```

## Security Considerations

1. **Never commit** your `.env` file or WhatsApp session tokens
2. Run the service with limited user permissions
3. Use firewall rules to restrict access to the API
4. Consider adding API authentication if exposing to the internet
5. Regularly update dependencies for security patches

## Integration with Laravel

The service is automatically integrated with your Laravel ERP system. When a bike is assigned in Laravel:

1. Laravel fires a `BikeAssignedEvent`
2. Event listener dispatches `SendWhatsAppNotificationJob`
3. Job calls this Node.js service via HTTP
4. Service formats and sends message to WhatsApp group

## Support

For issues and questions:
1. Check logs: `logs/error.log`
2. Verify service status: `GET /api/health`
3. Review Laravel logs: `storage/logs/laravel.log`

## License

MIT

