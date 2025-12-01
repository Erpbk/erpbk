# Laravel .env Configuration Updates

## Add These Lines to Your .env File

Copy and paste these configuration lines to your Laravel `.env` file:

```env
# ============================================================================
# WhatsApp Bike Assignment Notifications
# ============================================================================

# Enable/Disable WhatsApp notifications
# Set to true to enable, false to disable
WHATSAPP_NOTIFICATIONS_ENABLED=true

# Node.js WhatsApp service URL
# For local development: http://localhost:3000
# For production: http://your-domain.com:3000 or internal IP
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000

# ============================================================================
# Queue Configuration (if not already configured)
# ============================================================================

# Queue driver - use 'database' for reliability
# Other options: redis, sync, sqs
QUEUE_CONNECTION=database

# Default queue name (optional)
# QUEUE_DEFAULT=default

# ============================================================================
```

## After Adding Configuration

### 1. Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 2. Verify Configuration

```bash
php artisan tinker

# In Tinker console:
>>> env('WHATSAPP_NOTIFICATIONS_ENABLED')
=> true

>>> env('WHATSAPP_NODE_SERVICE_URL')
=> "http://localhost:3000"

>>> env('QUEUE_CONNECTION')
=> "database"
```

### 3. Create Queue Tables (if needed)

If you haven't already set up database queues:

```bash
php artisan queue:table
php artisan migrate
```

### 4. Start Queue Worker

```bash
# Development (foreground)
php artisan queue:work

# Development (background)
php artisan queue:work &

# Production (use Supervisor - see WHATSAPP_INTEGRATION_GUIDE.md)
```

## Production Configuration

For production environments:

```env
WHATSAPP_NOTIFICATIONS_ENABLED=true

# Use internal network IP or domain
WHATSAPP_NODE_SERVICE_URL=http://10.0.0.5:3000
# or
WHATSAPP_NODE_SERVICE_URL=http://whatsapp-service.internal:3000

QUEUE_CONNECTION=database
```

## Configuration Options Explained

| Variable | Description | Default | Options |
|----------|-------------|---------|---------|
| `WHATSAPP_NOTIFICATIONS_ENABLED` | Enable/disable notifications | `false` | `true`, `false` |
| `WHATSAPP_NODE_SERVICE_URL` | URL to Node.js service | `http://localhost:3000` | Any valid URL |
| `QUEUE_CONNECTION` | Queue driver to use | `sync` | `database`, `redis`, `sqs`, `sync` |

## Environment-Specific Settings

### Development
```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://localhost:3000
QUEUE_CONNECTION=database
```

### Staging
```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://staging-whatsapp:3000
QUEUE_CONNECTION=redis
```

### Production
```env
WHATSAPP_NOTIFICATIONS_ENABLED=true
WHATSAPP_NODE_SERVICE_URL=http://whatsapp-service:3000
QUEUE_CONNECTION=redis
```

## Disable Notifications

To temporarily disable WhatsApp notifications without stopping the service:

```env
WHATSAPP_NOTIFICATIONS_ENABLED=false
```

Then clear cache:
```bash
php artisan config:clear
```

## Testing Configuration

Test if the configuration is working:

```bash
# Test Node.js service connectivity
curl http://localhost:3000/api/health

# Expected response:
# {"status":"ok","bot":{"status":"connected","connected":true}}
```

## Troubleshooting

### Configuration Not Taking Effect

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart queue worker
php artisan queue:restart
```

### Queue Worker Not Processing Jobs

```bash
# Check queue worker status
ps aux | grep "queue:work"

# If not running, start it
php artisan queue:work
```

### Cannot Connect to WhatsApp Service

1. Verify service is running:
   ```bash
   curl http://localhost:3000/api/health
   ```

2. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Verify URL in `.env` is correct

## Need More Help?

See [WHATSAPP_INTEGRATION_GUIDE.md](WHATSAPP_INTEGRATION_GUIDE.md) for comprehensive documentation and troubleshooting.

