(function () {
  'use strict';

  // Read API base from data-api attribute on the script tag, or default to relative /api/v1
  const scriptTag = document.currentScript ||
    Array.from(document.querySelectorAll('script[src*="chat-widget"]')).pop();
  const API_URL = (scriptTag && scriptTag.getAttribute('data-api')) || '/api/v1';

  const PRIMARY = '#4F46E5';

  const styles = `
    #hd-widget-btn {
      position: fixed; bottom: 24px; right: 24px; z-index: 9999;
      width: 56px; height: 56px; border-radius: 50%;
      background: ${PRIMARY}; border: none; cursor: pointer;
      box-shadow: 0 4px 20px rgba(79,70,229,0.4);
      display: flex; align-items: center; justify-content: center;
      transition: transform .2s;
    }
    #hd-widget-btn:hover { transform: scale(1.1); }
    #hd-widget-btn svg { width: 26px; height: 26px; fill: #fff; }
    #hd-widget-panel {
      position: fixed; bottom: 92px; right: 24px; z-index: 9999;
      width: 360px; height: 520px; background: #fff;
      border-radius: 16px; box-shadow: 0 8px 40px rgba(0,0,0,0.18);
      display: none; flex-direction: column; overflow: hidden;
      font-family: 'Noto Sans TC', 'Inter', sans-serif;
    }
    #hd-widget-panel.open { display: flex; }
    .hd-header {
      background: ${PRIMARY}; color: #fff; padding: 16px 20px;
      display: flex; align-items: center; gap: 10px;
    }
    .hd-header-avatar {
      width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.3);
      display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px;
    }
    .hd-header-info { flex: 1; }
    .hd-header-title { font-weight: 700; font-size: 15px; }
    .hd-header-sub { font-size: 12px; opacity: 0.8; }
    .hd-close-btn { background: none; border: none; color: #fff; cursor: pointer; font-size: 20px; opacity: 0.8; }
    .hd-messages {
      flex: 1; overflow-y: auto; padding: 16px;
      display: flex; flex-direction: column; gap: 10px;
      background: #F8FAFC;
    }
    .hd-msg { display: flex; flex-direction: column; }
    .hd-msg.visitor { align-items: flex-end; }
    .hd-msg.agent { align-items: flex-start; }
    .hd-bubble {
      max-width: 80%; padding: 10px 14px; border-radius: 14px;
      font-size: 14px; line-height: 1.5;
    }
    .hd-msg.visitor .hd-bubble { background: ${PRIMARY}; color: #fff; border-bottom-right-radius: 4px; }
    .hd-msg.agent .hd-bubble { background: #fff; color: #374151; border-bottom-left-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .hd-msg.system .hd-bubble { background: transparent; color: #94a3b8; font-size: 12px; text-align: center; width: 100%; }
    .hd-msg-time { font-size: 11px; color: #94a3b8; margin-top: 3px; padding: 0 4px; }
    .hd-init-form { padding: 20px; display: flex; flex-direction: column; gap: 12px; }
    .hd-init-form input {
      border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px;
      font-size: 14px; outline: none; transition: border-color .2s;
    }
    .hd-init-form input:focus { border-color: ${PRIMARY}; }
    .hd-start-btn {
      background: ${PRIMARY}; color: #fff; border: none; border-radius: 8px;
      padding: 12px; font-size: 15px; font-weight: 600; cursor: pointer;
      transition: opacity .2s;
    }
    .hd-start-btn:hover { opacity: 0.9; }
    .hd-input-area {
      border-top: 1px solid #e2e8f0; padding: 12px 16px;
      display: flex; gap: 8px; align-items: flex-end; background: #fff;
    }
    .hd-input-area textarea {
      flex: 1; border: 1px solid #e2e8f0; border-radius: 10px;
      padding: 10px 12px; font-size: 14px; resize: none; outline: none;
      min-height: 40px; max-height: 100px; line-height: 1.4;
      transition: border-color .2s; font-family: inherit;
    }
    .hd-input-area textarea:focus { border-color: ${PRIMARY}; }
    .hd-send-btn {
      background: ${PRIMARY}; border: none; border-radius: 10px;
      width: 40px; height: 40px; cursor: pointer; display: flex;
      align-items: center; justify-content: center; flex-shrink: 0;
    }
    .hd-send-btn svg { width: 18px; height: 18px; fill: #fff; }
    .hd-typing { font-size: 12px; color: #94a3b8; padding: 4px 16px; font-style: italic; }
  `;

  function injectStyles() {
    const el = document.createElement('style');
    el.textContent = styles;
    document.head.appendChild(el);
  }

  function formatTime(dt) {
    const d = new Date(dt || Date.now());
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
  }

  function createWidget() {
    injectStyles();

    const btn = document.createElement('button');
    btn.id = 'hd-widget-btn';
    btn.title = '聯繫客服';
    btn.innerHTML = `<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`;

    const panel = document.createElement('div');
    panel.id = 'hd-widget-panel';
    panel.innerHTML = `
      <div class="hd-header">
        <div class="hd-header-avatar">💬</div>
        <div class="hd-header-info">
          <div class="hd-header-title">客服中心</div>
          <div class="hd-header-sub">🟢 客服人員在線</div>
        </div>
        <button class="hd-close-btn" id="hd-close">✕</button>
      </div>
      <div id="hd-body"></div>
    `;

    document.body.appendChild(btn);
    document.body.appendChild(panel);

    btn.addEventListener('click', () => panel.classList.toggle('open'));
    document.getElementById('hd-close').addEventListener('click', () => panel.classList.remove('open'));

    renderInitForm();
  }

  let sessionToken = null;
  let sessionId    = null;
  let pollInterval = null;
  let lastTs       = null;

  function renderInitForm() {
    document.getElementById('hd-body').innerHTML = `
      <div class="hd-init-form">
        <h3 style="text-align:center;color:#1e293b;font-size:16px;margin-bottom:4px;">您好！</h3>
        <p style="text-align:center;color:#64748b;font-size:13px;margin-bottom:8px;">請留下您的資訊，我們將盡快回覆</p>
        <input id="hd-name" type="text" placeholder="您的姓名" />
        <input id="hd-email" type="email" placeholder="電子郵件（選填）" />
        <button class="hd-start-btn" id="hd-start">開始對話</button>
      </div>
    `;
    document.getElementById('hd-start').addEventListener('click', startChat);
    document.getElementById('hd-name').addEventListener('keydown', e => {
      if (e.key === 'Enter') startChat();
    });
  }

  async function startChat() {
    const name  = document.getElementById('hd-name').value.trim() || 'Visitor';
    const email = document.getElementById('hd-email').value.trim();
    const btn   = document.getElementById('hd-start');
    btn.textContent = '連線中...';
    btn.disabled = true;

    try {
      const res = await fetch(`${API_URL}/chat/init`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ visitor_name: name, visitor_email: email || null }),
      });
      const data = await res.json();
      sessionToken = data.data.session_token;
      sessionId    = data.data.session_id;

      renderChatUI(name);
      addMessage({ sender_type: 'system', content: '您好！客服人員將在幾分鐘內回覆您。', created_at: new Date() });
      startPolling();
    } catch (e) {
      btn.textContent = '開始對話';
      btn.disabled = false;
      console.error('[HelpDesk Widget]', e);
    }
  }

  function renderChatUI(name) {
    document.getElementById('hd-body').innerHTML = `
      <div class="hd-messages" id="hd-messages"></div>
      <div id="hd-typing" class="hd-typing" style="display:none">客服人員正在輸入...</div>
      <div class="hd-input-area">
        <textarea id="hd-textarea" placeholder="輸入訊息..." rows="1"></textarea>
        <button class="hd-send-btn" id="hd-send">
          <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
    `;

    document.getElementById('hd-send').addEventListener('click', sendMessage);
    document.getElementById('hd-textarea').addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
  }

  function addMessage(msg) {
    const container = document.getElementById('hd-messages');
    if (!container) return;
    const div = document.createElement('div');
    div.className = `hd-msg ${msg.sender_type}`;
    div.innerHTML = `
      <div class="hd-bubble">${String(msg.content).replace(/</g,'&lt;').replace(/\n/g,'<br>')}</div>
      <div class="hd-msg-time">${formatTime(msg.created_at)}</div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    lastTs = msg.created_at || new Date().toISOString();
  }

  async function sendMessage() {
    const ta  = document.getElementById('hd-textarea');
    const msg = ta.value.trim();
    if (!msg || !sessionId) return;
    ta.value = '';

    addMessage({ sender_type: 'visitor', content: msg, created_at: new Date() });

    await fetch(`${API_URL}/chat/sessions/${sessionId}/visitor-message`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content: msg }),
    }).catch(e => console.error('[HelpDesk Widget]', e));
  }

  function startPolling() {
    pollInterval = setInterval(async () => {
      if (!sessionId) return;
      try {
        const url = `${API_URL}/chat/sessions/${sessionId}/poll${lastTs ? '?since=' + encodeURIComponent(lastTs) : ''}`;
        const res  = await fetch(url);
        const data = await res.json();
        (data.data || []).forEach(msg => {
          if (msg.sender_type !== 'visitor') addMessage(msg);
        });
      } catch {}
    }, 3000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createWidget);
  } else {
    createWidget();
  }
})();
