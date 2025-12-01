const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const bodyParser = require('body-parser');
const WhatsAppBot = require('./whatsapp-bot');
const logger = require('./logger');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(cors());
app.use(morgan('combined', { stream: logger.stream }));
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Initialize WhatsApp bot
const whatsappBot = new WhatsAppBot();

// Routes
app.get('/', (req, res) => {
  res.json({
    service: 'WhatsApp Bike Notification Service',
    status: 'running',
    version: '1.0.0'
  });
});

// Health check endpoint
app.get('/api/health', (req, res) => {
  const botStatus = whatsappBot.getStatus();
  res.json({
    status: 'ok',
    bot: botStatus,
    timestamp: new Date().toISOString()
  });
});

// Get WhatsApp session status
app.get('/api/status', (req, res) => {
  try {
    const status = whatsappBot.getStatus();
    res.json({
      success: true,
      status: status,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    logger.error('Error getting status:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to get status',
      error: error.message
    });
  }
});

// Send message to WhatsApp group
app.post('/api/send-message', async (req, res) => {
  try {
    const { type, message, data } = req.body;

    if (!message) {
      return res.status(400).json({
        success: false,
        message: 'Message is required'
      });
    }

    logger.info('Received message request:', { type, data });

    // Send message to WhatsApp group
    const result = await whatsappBot.sendMessageToGroup(message);

    if (result.success) {
      logger.info('Message sent successfully');
      res.json({
        success: true,
        message: 'Message sent successfully',
        result: result
      });
    } else {
      logger.error('Failed to send message:', result.error);
      res.status(500).json({
        success: false,
        message: 'Failed to send message',
        error: result.error
      });
    }
  } catch (error) {
    logger.error('Error sending message:', error);
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

// Get QR code for authentication
app.get('/api/qr-code', (req, res) => {
  const qrCode = whatsappBot.getQRCode();
  
  if (qrCode) {
    res.json({
      success: true,
      qr_code: qrCode
    });
  } else {
    res.json({
      success: false,
      message: 'No QR code available. Session may already be connected.'
    });
  }
});

// Get all WhatsApp groups
app.get('/api/groups', async (req, res) => {
  try {
    const statusInfo = whatsappBot.getStatus();

    // Proceed if client exists; allow certain intermediate statuses (e.g. waitChat)
    if (!whatsappBot.client) {
      return res.status(400).json({
        success: false,
        message: 'WhatsApp client not ready. Please start the service and scan QR.',
        status: statusInfo.status
      });
    }

    const allowedStatuses = [
      'connected',
      'isLogged',
      'qrReadSuccess',
      'successPageWhatsapp',
      'waitChat',
      'openBrowser',
      'inChat'
    ];

    logger.info('Fetching WhatsApp groups via API...', {
      sessionStatus: statusInfo.status,
      allowed: allowedStatuses.includes(statusInfo.status)
    });

    // Try to fetch chats with retries
    let chats = [];
    let lastErr = null;
    for (let i = 1; i <= 6; i++) {
      try {
        const result = await whatsappBot.client.getAllChats();
        if (Array.isArray(result)) {
          chats = result;
          break;
        }
      } catch (e) {
        lastErr = e;
      }
      await new Promise(r => setTimeout(r, 1000));
    }

    // Fallback: try getAllGroups if available
    if ((!Array.isArray(chats) || chats.length === 0) && typeof whatsappBot.client.getAllGroups === 'function') {
      try {
        logger.info('Primary chat list empty. Trying getAllGroups() fallback via API...');
        const groupsOnly = await whatsappBot.client.getAllGroups();
        chats = Array.isArray(groupsOnly) ? groupsOnly : [];
      } catch (fallbackErr) {
        lastErr = fallbackErr;
      }
    }

    const groups = (chats || [])
      .filter(chat => chat.isGroup || chat.isGroup === true || chat.id?._serialized?.endsWith('@g.us'))
      .map(group => ({
        id: group.id?._serialized || group.id,
        name: group.name,
        participants: group.groupMetadata?.participants?.length || 0
      }));

    logger.info(`Found ${groups.length} groups`);

    res.json({
      success: true,
      count: groups.length,
      groups
    });
  } catch (error) {
    logger.error('Error fetching groups:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to fetch groups',
      error: error.message
    });
  }
});

// Force restart WhatsApp session
app.post('/api/restart', async (req, res) => {
  try {
    logger.info('Restarting WhatsApp session...');
    await whatsappBot.restart();
    res.json({
      success: true,
      message: 'WhatsApp session restarted'
    });
  } catch (error) {
    logger.error('Error restarting session:', error);
    res.status(500).json({
      success: false,
      message: 'Failed to restart session',
      error: error.message
    });
  }
});

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error('Unhandled error:', err);
  res.status(500).json({
    success: false,
    message: 'Internal server error',
    error: process.env.NODE_ENV === 'development' ? err.message : 'An error occurred'
  });
});

// Start server
app.listen(PORT, async () => {
  logger.info(`WhatsApp Service running on port ${PORT}`);
  logger.info(`Environment: ${process.env.NODE_ENV || 'development'}`);
  
  // Initialize WhatsApp bot
  try {
    await whatsappBot.initialize();
    logger.info('WhatsApp bot initialized successfully');
  } catch (error) {
    logger.error('Failed to initialize WhatsApp bot:', error);
  }
});

// Handle graceful shutdown
process.on('SIGTERM', async () => {
  logger.info('SIGTERM signal received: closing HTTP server');
  await whatsappBot.close();
  process.exit(0);
});

process.on('SIGINT', async () => {
  logger.info('SIGINT signal received: closing HTTP server');
  await whatsappBot.close();
  process.exit(0);
});

