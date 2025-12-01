#!/bin/bash

# WhatsApp Service Startup Script

echo "=================================================="
echo "  WhatsApp Bike Notification Service"
echo "=================================================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found!"
    echo "📝 Creating .env from template..."
    cp env.template .env
    echo "✅ .env file created. Please configure it before starting the service."
    echo ""
    echo "Required configuration:"
    echo "  1. Set PORT (default: 3000)"
    echo "  2. Run 'npm start' once to get WhatsApp groups list"
    echo "  3. Set WHATSAPP_GROUP_ID in .env"
    echo "  4. Run this script again"
    exit 1
fi

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
    echo ""
fi

# Create logs directory if it doesn't exist
mkdir -p logs

# Check if PM2 is available
if command -v pm2 &> /dev/null; then
    echo "🚀 Starting with PM2..."
    pm2 start ecosystem.config.js
    pm2 save
    echo ""
    echo "✅ Service started with PM2"
    echo "📊 Monitor: pm2 monit"
    echo "📝 Logs: pm2 logs whatsapp-service"
    echo "🔄 Restart: pm2 restart whatsapp-service"
    echo "🛑 Stop: pm2 stop whatsapp-service"
else
    echo "🚀 Starting with Node.js..."
    echo "⚠️  Consider installing PM2 for production: npm install -g pm2"
    echo ""
    node server.js
fi

echo ""
echo "=================================================="

