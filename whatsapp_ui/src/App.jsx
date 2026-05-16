import React, { useState, useEffect } from 'react';
import './index.css';

function App() {
  const [status, setStatus] = useState('initializing');
  const [qrUpdate, setQrUpdate] = useState(Date.now());
  const [phone, setPhone] = useState('');
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [feedback, setFeedback] = useState({ type: '', text: '' });
  const [logs, setLogs] = useState([]);

  // Poll status
  useEffect(() => {
    const interval = setInterval(async () => {
      try {
        const res = await fetch('/api/status');
        const data = await res.json();
        if (data.status !== status) {
          setStatus(data.status);
          if (data.status === 'qr_ready') {
            setQrUpdate(Date.now()); // Refresh QR image
          }
        }
      } catch (err) {
        setStatus('offline');
      }
    }, 2000);
    return () => clearInterval(interval);
  }, [status]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!phone || !message) return;

    setLoading(true);
    setFeedback({ type: '', text: '' });

    try {
      const res = await fetch('/api/send-message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, message })
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        setFeedback({ type: 'success', text: `Message sent to ${data.sent_to}!` });
        setLogs([{ phone: data.sent_to, time: new Date().toLocaleTimeString(), status: 'Sent' }, ...logs.slice(0, 4)]);
        setMessage('');
      } else {
        setFeedback({ type: 'error', text: data.message || 'Failed to send' });
      }
    } catch (err) {
      setFeedback({ type: 'error', text: 'Network error occurred' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="dashboard">
      <header className="header">
        <h1>WhatsApp Bridge</h1>
        <div className={`status-badge status-${status}`}>
          <span className={`dot ${status === 'connected' || status === 'qr_ready' ? 'dot-pulse' : ''}`} 
                style={{ backgroundColor: status === 'connected' ? '#00a884' : status === 'qr_ready' ? '#ffc107' : '#f44336' }}></span>
          {status.replace('_', ' ')}
        </div>
      </header>

      <div className="main-grid">
        {/* Left Column: Connection Info */}
        <div className="glass-card qr-container">
          <h3>Connection</h3>
          {status === 'connected' ? (
            <div style={{ textAlign: 'center', padding: '2rem' }}>
              <div style={{ fontSize: '4rem', marginBottom: '1rem' }}>✅</div>
              <p>Authenticated & Ready</p>
              <p style={{ color: '#8696a0', fontSize: '0.9rem' }}>Bridge is active on port 3005</p>
            </div>
          ) : (
            <>
              <p style={{ color: '#8696a0' }}>Scan QR code with your WhatsApp to connect</p>
              <div className="qr-image-wrapper">
                {status === 'qr_ready' ? (
                  <img src={`/qr?t=${qrUpdate}`} alt="Scan QR" className="qr-image" />
                ) : (
                  <div className="loader"></div>
                )}
              </div>
              {status === 'initializing' && <p>Initializing WhatsApp Client...</p>}
            </>
          )}
        </div>

        {/* Right Column: Message Composer */}
        <div className="glass-card">
          <h3>Send Message</h3>
          <form onSubmit={handleSend} className="form-group">
            <input 
              type="text" 
              placeholder="Phone Number (e.g. 03001234567)" 
              className="input-field"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              disabled={status !== 'connected' || loading}
            />
            <textarea 
              placeholder="Type your message here..." 
              className="input-field"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              disabled={status !== 'connected' || loading}
            ></textarea>
            
            {feedback.text && (
              <p className={feedback.type === 'success' ? 'success-msg' : 'error-msg'}>
                {feedback.text}
              </p>
            )}

            <button 
              type="submit" 
              className="btn" 
              disabled={status !== 'connected' || loading || !phone || !message}
            >
              {loading ? <div className="loader"></div> : 'Send Message'}
            </button>
          </form>

          {logs.length > 0 && (
            <div className="recent-logs">
              <h4>Recent Activity</h4>
              {logs.map((log, i) => (
                <div key={i} className="log-item">
                  <span>{log.phone}</span>
                  <span style={{ color: '#8696a0' }}>{log.time}</span>
                  <span className="success-msg">{log.status}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
      
      <footer style={{ marginTop: '2rem', color: '#8696a0', fontSize: '0.8rem' }}>
        CMSPRO WhatsApp Notification Bridge v2.0
      </footer>
    </div>
  );
}

export default App;
