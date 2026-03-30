(function() {
  // Styles
  var css = document.createElement('style');
  css.textContent = `
    #humito-toggle {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00BCD4, #2ea3f2);
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(0,188,212,0.4);
      z-index: 9999;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    #humito-toggle:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 30px rgba(0,188,212,0.5);
    }
    #humito-toggle svg { width: 28px; height: 28px; }
    #humito-badge {
      position: absolute;
      top: -2px;
      right: -2px;
      width: 16px;
      height: 16px;
      background: #f5a623;
      border-radius: 50%;
      border: 2px solid white;
      animation: humitoPulse 2s infinite;
    }
    @keyframes humitoPulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }

    #humito-toggle.hidden { display: none; }

    #humito-chat {
      position: fixed;
      bottom: 24px;
      right: 24px;
      width: 370px;
      max-height: 520px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 12px 48px rgba(0,0,0,0.15);
      z-index: 9998;
      display: none;
      flex-direction: column;
      overflow: hidden;
      font-family: 'Open Sans', sans-serif;
    }
    #humito-chat.open { display: flex; }

    #humito-header {
      background: linear-gradient(135deg, #0d2137, #1a3a5c);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    #humito-header-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00BCD4, #2ea3f2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }
    #humito-header-info h4 {
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      margin: 0;
    }
    #humito-header-info span {
      color: rgba(255,255,255,0.6);
      font-size: 12px;
    }
    #humito-close {
      margin-left: auto;
      background: none;
      border: none;
      color: rgba(255,255,255,0.5);
      font-size: 20px;
      cursor: pointer;
      padding: 4px;
    }
    #humito-close:hover { color: #fff; }

    #humito-messages {
      flex: 1;
      padding: 16px;
      overflow-y: auto;
      max-height: 340px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .humito-msg {
      max-width: 85%;
      padding: 12px 16px;
      border-radius: 16px;
      font-size: 14px;
      line-height: 1.6;
      word-wrap: break-word;
    }
    .humito-msg.bot {
      background: #f0f4f8;
      color: #333;
      align-self: flex-start;
      border-bottom-left-radius: 4px;
    }
    .humito-msg.user {
      background: linear-gradient(135deg, #00BCD4, #2ea3f2);
      color: #fff;
      align-self: flex-end;
      border-bottom-right-radius: 4px;
    }
    .humito-msg.bot a {
      color: #00BCD4;
      text-decoration: underline;
    }
    .humito-typing {
      align-self: flex-start;
      padding: 12px 16px;
      background: #f0f4f8;
      border-radius: 16px;
      border-bottom-left-radius: 4px;
      font-size: 14px;
      color: #999;
    }
    .humito-typing span {
      animation: humitoDots 1.4s infinite;
      display: inline-block;
    }
    .humito-typing span:nth-child(2) { animation-delay: 0.2s; }
    .humito-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes humitoDots {
      0%, 60%, 100% { opacity: 0.3; }
      30% { opacity: 1; }
    }

    #humito-input-area {
      padding: 12px 16px;
      border-top: 1px solid #e8ecf0;
      display: flex;
      gap: 8px;
      align-items: center;
    }
    #humito-input {
      flex: 1;
      border: 1px solid #e0e4e8;
      border-radius: 24px;
      padding: 10px 16px;
      font-size: 14px;
      font-family: 'Open Sans', sans-serif;
      outline: none;
      transition: border 0.2s;
    }
    #humito-input:focus { border-color: #00BCD4; }
    #humito-send {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00BCD4, #2ea3f2);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s;
      flex-shrink: 0;
    }
    #humito-send:hover { transform: scale(1.1); }
    #humito-send svg { width: 16px; height: 16px; }

    #humito-wa-bar {
      padding: 10px 16px;
      background: #f8f9fa;
      text-align: center;
      border-top: 1px solid #e8ecf0;
    }
    #humito-wa-bar a {
      color: #25D366;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    #humito-wa-bar a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
      #humito-chat {
        right: 0;
        bottom: 0;
        width: 100%;
        max-height: 100vh;
        border-radius: 0;
      }
      #humito-toggle { bottom: 16px; right: 16px; }
    }
  `;
  document.head.appendChild(css);

  // HTML
  var widget = document.createElement('div');
  widget.innerHTML = `
    <button id="humito-toggle" onclick="humito.toggle()">
      <svg viewBox="0 0 24 24" fill="white"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/><path d="M7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/></svg>
      <div id="humito-badge"></div>
    </button>
    <div id="humito-chat">
      <div id="humito-header">
        <div id="humito-header-avatar">💨</div>
        <div id="humito-header-info">
          <h4>Humito</h4>
          <span>Asistente Fumadorex</span>
        </div>
        <button id="humito-close" onclick="humito.toggle()">&times;</button>
      </div>
      <div id="humito-messages"></div>
      <div id="humito-input-area">
        <input id="humito-input" placeholder="Escribe tu mensaje..." onkeydown="if(event.key==='Enter')humito.send()" />
        <button id="humito-send" onclick="humito.send()">
          <svg viewBox="0 0 24 24" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
      <div id="humito-wa-bar">
        <a href="https://wa.me/523343717956?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20Fumadorex" target="_blank">
          &#9742; Hablar con un asesor por WhatsApp
        </a>
      </div>
    </div>
  `;
  document.body.appendChild(widget);

  // Logic
  var history = [];
  var isOpen = false;

  window.humito = {
    toggle: function() {
      isOpen = !isOpen;
      var chat = document.getElementById('humito-chat');
      var badge = document.getElementById('humito-badge');
      var toggle = document.getElementById('humito-toggle');
      if (isOpen) {
        chat.classList.add('open');
        toggle.classList.add('hidden');
        badge.style.display = 'none';
        if (history.length === 0) {
          humito.addMsg('bot', '¡Hola! Soy Humito, el asistente de Fumadorex. ¿En qué puedo ayudarte hoy?');
        }
        document.getElementById('humito-input').focus();
      } else {
        chat.classList.remove('open');
        toggle.classList.remove('hidden');
      }
    },

    addMsg: function(role, text) {
      var messages = document.getElementById('humito-messages');
      var div = document.createElement('div');
      div.className = 'humito-msg ' + role;
      // Convert WhatsApp links
      text = text.replace(
        /WhatsApp/g,
        '<a href="https://wa.me/523343717956?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20Fumadorex" target="_blank">WhatsApp</a>'
      );
      div.innerHTML = text;
      messages.appendChild(div);
      messages.scrollTop = messages.scrollHeight;
    },

    send: function() {
      var input = document.getElementById('humito-input');
      var text = input.value.trim();
      if (!text) return;

      humito.addMsg('user', text);
      history.push({ role: 'user', content: text });
      input.value = '';

      // Show typing
      var messages = document.getElementById('humito-messages');
      var typing = document.createElement('div');
      typing.className = 'humito-typing';
      typing.id = 'humito-typing';
      typing.innerHTML = '<span>.</span><span>.</span><span>.</span>';
      messages.appendChild(typing);
      messages.scrollTop = messages.scrollHeight;

      fetch('/api/humito-chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messages: history })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        var t = document.getElementById('humito-typing');
        if (t) t.remove();
        var reply = data.reply || 'Disculpa, hubo un error. ¿Puedes intentar de nuevo?';
        humito.addMsg('bot', reply);
        history.push({ role: 'assistant', content: reply });
      })
      .catch(function() {
        var t = document.getElementById('humito-typing');
        if (t) t.remove();
        humito.addMsg('bot', 'Error de conexión. Por favor intenta de nuevo.');
      });
    }
  };

  // Auto-open after 15 seconds on first visit
  if (!sessionStorage.getItem('humito-shown')) {
    setTimeout(function() {
      if (!isOpen) {
        // Don't auto-open, just pulse the badge
        sessionStorage.setItem('humito-shown', '1');
      }
    }, 15000);
  }
})();
