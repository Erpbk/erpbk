const venom = require('venom-bot');
const logger = require('./logger');
require('dotenv').config();

class WhatsAppBot {
  constructor() {
    this.client = null;
    this.qrCode = null;
    this.status = 'disconnected';
    this.groupId = process.env.WHATSAPP_GROUP_ID || null;
    this.sessionName = process.env.WHATSAPP_SESSION_NAME || 'bike-notifications';
  }

  /**
   * Initialize WhatsApp bot with VenomBot
   */
  async initialize() {
    try {
      logger.info('Initializing WhatsApp bot...');
      
      this.client = await venom.create(
        this.sessionName,
        (base64Qr, asciiQR) => {
          // QR code callback
          this.qrCode = base64Qr;
          logger.info('QR Code generated. Please scan to authenticate.');
          console.log('\n');
          console.log('='.repeat(50));
          console.log('SCAN THIS QR CODE WITH YOUR WHATSAPP:');
          console.log('='.repeat(50));
          console.log(asciiQR);
          console.log('='.repeat(50));
          console.log('\n');
        },
        (statusSession) => {
          // Status callback
          this.status = statusSession;
          logger.info(`Session status: ${statusSession}`);
          
          if (statusSession === 'isLogged') {
            logger.info('✓ WhatsApp authenticated successfully!');
            this.qrCode = null;
          }
        },
        {
          headless: false,
          devtools: false,
          useChrome: true,
          debug: false,
          logQR: true,
          browserArgs: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu'
          ],
          autoClose: 0,
          disableWelcome: true,
          updatesLog: false
        }
      );

      // Listen for incoming messages (log group IDs to help user capture target group)
      this.client.onMessage((message) => {
        try {
          const isGroup = !!message.isGroupMsg || (message.chatId ? String(message.chatId).endsWith('@g.us') : false);
          const chatId = message.chatId || message.from;
          const preview = typeof message.body === 'string' ? message.body.substring(0, 50) : '';
          logger.info('Received message', {
            from: message.from,
            chatId: chatId,
            isGroup: isGroup,
            preview: preview
          });
          if (isGroup) {
            logger.info('GROUP ID DETECTED - copy this id for .env WHATSAPP_GROUP_ID', {
              groupId: chatId
            });
          }
        } catch (e) {
          logger.debug('onMessage logging error', { error: e?.message });
        }
      });

      this.status = 'connected';
      logger.info('WhatsApp bot connected successfully');

      // If group ID not set, list all groups
      if (!this.groupId) {
        await this.listAllGroups();
      }

      return true;
    } catch (error) {
      logger.error('Failed to initialize WhatsApp bot:', error);
      this.status = 'error';
      throw error;
    }
  }

  /**
   * List all WhatsApp groups to help find the target group ID
   */
  async listAllGroups() {
    try {
      if (!this.client) {
        throw new Error('WhatsApp client not initialized');
      }

      logger.info('Fetching all WhatsApp groups...');

      // Wait for chats to become available (WhatsApp can report waitChat for a while)
      let chats = [];
      for (let attempt = 1; attempt <= 8; attempt++) {
        try {
          // getAllChats may briefly return undefined/null before the UI fully loads
          const result = await this.client.getAllChats();
          if (Array.isArray(result) && result.length >= 0) {
            chats = result;
            break;
          }
        } catch (err) {
          // swallow and retry
        }
        logger.info(`Waiting for chats to load (attempt ${attempt}/8)...`);
        await new Promise(r => setTimeout(r, 1500));
      }

      // Fallback: if chats are empty or undefined, try getAllGroups if available
      if ((!Array.isArray(chats) || chats.length === 0) && typeof this.client.getAllGroups === 'function') {
        try {
          logger.info('Primary chat list empty. Trying getAllGroups() fallback...');
          const groupsOnly = await this.client.getAllGroups();
          chats = Array.isArray(groupsOnly) ? groupsOnly : [];
        } catch (fallbackErr) {
          logger.error('getAllGroups() fallback failed:', fallbackErr);
        }
      }
      const groups = (chats || []).filter(chat => chat.isGroup || chat.isGroup === true || chat.id?._serialized?.endsWith('@g.us'));

      console.log('\n');
      console.log('='.repeat(60));
      console.log('AVAILABLE WHATSAPP GROUPS:');
      console.log('='.repeat(60));
      
      groups.forEach((group, index) => {
        console.log(`${index + 1}. ${group.name}`);
        console.log(`   ID: ${group.id._serialized}`);
        console.log(`   Participants: ${group.groupMetadata?.participants?.length || 0}`);
        console.log('-'.repeat(60));
      });

      console.log('\nAdd the Group ID to your .env file as WHATSAPP_GROUP_ID');
      console.log('='.repeat(60));
      console.log('\n');

      logger.info(`Found ${groups.length} groups`);
    } catch (error) {
      logger.error('Failed to list groups:', error);
    }
  }

  /**
   * Send message to configured WhatsApp group
   */
  async sendMessageToGroup(message) {
    try {
      if (!this.client) {
        throw new Error('WhatsApp client not initialized');
      }

      if (this.status !== 'connected' && this.status !== 'isLogged') {
        throw new Error(`WhatsApp not connected. Current status: ${this.status}`);
      }

      if (!this.groupId) {
        throw new Error('WhatsApp group ID not configured. Please set WHATSAPP_GROUP_ID in .env');
      }

      logger.info(`Sending message to group: ${this.groupId}`);

      // Send message to group
      const result = await this.client.sendText(this.groupId, message);

      logger.info('Message sent successfully', {
        groupId: this.groupId,
        messageId: result.id
      });

      return {
        success: true,
        messageId: result.id,
        timestamp: new Date().toISOString()
      };
    } catch (error) {
      logger.error('Failed to send message to group:', error);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Send message to specific chat/group by ID
   */
  async sendMessage(chatId, message) {
    try {
      if (!this.client) {
        throw new Error('WhatsApp client not initialized');
      }

      const result = await this.client.sendText(chatId, message);
      
      logger.info('Message sent', {
        chatId: chatId,
        messageId: result.id
      });

      return {
        success: true,
        messageId: result.id
      };
    } catch (error) {
      logger.error('Failed to send message:', error);
      return {
        success: false,
        error: error.message
      };
    }
  }

  /**
   * Get current bot status
   */
  getStatus() {
    return {
      status: this.status,
      connected: this.status === 'connected' || this.status === 'isLogged',
      groupConfigured: !!this.groupId,
      groupId: this.groupId,
      sessionName: this.sessionName
    };
  }

  /**
   * Get QR code for authentication
   */
  getQRCode() {
    return this.qrCode;
  }

  /**
   * Restart WhatsApp session
   */
  async restart() {
    try {
      logger.info('Restarting WhatsApp session...');
      
      if (this.client) {
        await this.client.close();
      }
      
      this.client = null;
      this.qrCode = null;
      this.status = 'disconnected';

      await this.initialize();
      
      logger.info('WhatsApp session restarted successfully');
    } catch (error) {
      logger.error('Failed to restart session:', error);
      throw error;
    }
  }

  /**
   * Close WhatsApp connection
   */
  async close() {
    try {
      if (this.client) {
        logger.info('Closing WhatsApp connection...');
        await this.client.close();
        this.status = 'disconnected';
        logger.info('WhatsApp connection closed');
      }
    } catch (error) {
      logger.error('Error closing WhatsApp connection:', error);
    }
  }
}

module.exports = WhatsAppBot;

