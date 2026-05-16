const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const port = 3005;

const qrPath = path.join(__dirname, '../assets/img/wa_qr.png');
const statusPath = path.join(__dirname, 'status.json');

// Initialize status file if it doesn't exist
if (!fs.existsSync(statusPath)) {
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'initializing' }));
}

app.use('/assets', express.static(path.join(__dirname, '../assets')));

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'cms_final_session',
        dataPath: path.join(__dirname, 'sessions_final')
    }),
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--window-size=1280,720'
        ]
    }
});

client.on('qr', (qr) => {
    console.log('QR RECEIVED');
    qrcode.toFile(qrPath, qr, { scale: 10 }, (err) => {
        if (!err) {
            fs.writeFileSync(statusPath, JSON.stringify({ 
                status: 'qr_ready', 
                updated_at: new Date().toLocaleTimeString() 
            }));
        }
    });
});

client.on('ready', () => {
    console.log('CLIENT READY');
    // Delete QR image when connected to avoid confusion
    if (fs.existsSync(qrPath)) fs.unlinkSync(qrPath);
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'connected' }));
});

client.on('disconnected', (reason) => {
    console.log('CLIENT DISCONNECTED:', reason);
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'disconnected', reason }));
    // Cleanup on disconnect
    if (fs.existsSync(qrPath)) fs.unlinkSync(qrPath);
});

client.on('auth_failure', msg => {
    console.error('AUTH FAIL:', msg);
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'error', message: msg }));
});

client.initialize().catch(err => {
    console.error('INITIALIZATION ERROR:', err.message);
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'error', message: err.message }));
});

app.post('/send-message', express.json(), async (req, res) => {
    const { phone, message } = req.body;
    
    if (!phone || !message) {
        return res.status(400).json({ status: 'error', message: 'Phone and message are required' });
    }

    try {
        const statusData = JSON.parse(fs.readFileSync(statusPath));
        if (statusData.status !== 'connected') {
            return res.status(503).json({ status: 'error', message: 'WhatsApp is not connected' });
        }

        let cleanPhone = phone.replace(/\D/g, '');
        if (cleanPhone.startsWith('03') && cleanPhone.length === 11) {
            cleanPhone = '92' + cleanPhone.substring(1);
        } else if (!cleanPhone.startsWith('92') && cleanPhone.length === 10) {
            cleanPhone = '92' + cleanPhone;
        }

        const numberId = `${cleanPhone}@c.us`;
        await client.sendMessage(numberId, message);
        res.json({ status: 'success' });
    } catch (error) {
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.get('/status', (req, res) => {
    if (fs.existsSync(statusPath)) {
        res.json(JSON.parse(fs.readFileSync(statusPath)));
    } else {
        res.json({ status: 'offline' });
    }
});

app.listen(port, () => {
    console.log(`WhatsApp Bridge running on port ${port}`);
});
