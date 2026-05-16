const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const port = 3005;

const qrPath = path.join(__dirname, '../assets/img/wa_qr.png');
const statusPath = path.join(__dirname, 'status.json');

app.use('/assets', express.static(path.join(__dirname, '../assets')));

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'cms_final_session',
        dataPath: path.join(__dirname, 'sessions_final')
    }),
    puppeteer: {
        executablePath: '/usr/bin/google-chrome',
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
    console.log('NEW QR STRING:', qr.substring(0, 30) + '...');
    qrcode.toFile(qrPath, qr, { scale: 10 }, (err) => {
        if (!err) {
            fs.writeFileSync(statusPath, JSON.stringify({ 
                status: 'qr_ready', 
                qr_snippet: qr.substring(0, 10),
                updated_at: new Date().toLocaleTimeString() 
            }));
        }
    });
});

client.on('ready', () => {
    console.log('READY');
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'connected' }));
});

client.on('auth_failure', msg => {
    console.error('AUTH FAIL:', msg);
    fs.writeFileSync(statusPath, JSON.stringify({ status: 'error', message: msg }));
});

client.initialize().catch(err => {
    console.error('INIT ERR:', err);
});

app.post('/send-message', express.json(), async (req, res) => {
    console.log('Incoming Message Request:', req.body);
    const { phone, message } = req.body;
    
    if (!phone || !message) {
        return res.status(400).json({ status: 'error', message: 'Phone and message are required' });
    }

    // Check if client is ready
    const statusData = JSON.parse(fs.readFileSync(statusPath));
    if (statusData.status !== 'connected') {
        return res.status(503).json({ status: 'error', message: 'WhatsApp is not connected' });
    }

    try {
        // Format phone number
        let cleanPhone = phone.replace(/\D/g, ''); // Remove non-digits
        
        // Handle Pakistani numbers: replace leading 03 with 923
        if (cleanPhone.startsWith('03') && cleanPhone.length === 11) {
            cleanPhone = '92' + cleanPhone.substring(1);
        } else if (!cleanPhone.startsWith('92') && cleanPhone.length === 10) {
            // If it's just 10 digits (no leading 0), assume it's PK and add 92
            cleanPhone = '92' + cleanPhone;
        }

        const numberId = cleanPhone.includes('@c.us') ? cleanPhone : `${cleanPhone}@c.us`;
        
        await client.sendMessage(numberId, message);
        console.log(`Message sent to ${cleanPhone}`);
        res.json({ status: 'success', sent_to: cleanPhone });
    } catch (error) {
        console.error('Send error:', error);
        res.status(500).json({ status: 'error', message: error.message });
    }
});

app.get('/test-send', async (req, res) => {
    const { phone } = req.query;
    if (!phone) return res.send('Provide ?phone=923xxxxxxxxx');
    try {
        const numberId = phone.includes('@c.us') ? phone : `${phone}@c.us`;
        await client.sendMessage(numberId, 'Test message from CMSPRO Bridge!');
        res.send('Test message sent to ' + phone);
    } catch (e) {
        res.status(500).send(e.message);
    }
});

app.get('/status', (req, res) => {
    if (fs.existsSync(statusPath)) res.json(JSON.parse(fs.readFileSync(statusPath)));
    else res.json({ status: 'initializing' });
});

app.listen(port, () => console.log(`Final test on ${port}`));
