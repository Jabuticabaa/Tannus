(function () {
  'use strict';

  var API = '/api/tannus-chat';
  var isOpen = false;

  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) {
      if (k === 'className') e.className = attrs[k];
      else if (k === 'textContent') e.textContent = attrs[k];
      else if (k.startsWith('on')) e.addEventListener(k.slice(2).toLowerCase(), attrs[k]);
      else e.setAttribute(k, attrs[k]);
    });
    if (children) children.forEach(function (c) { if (c) e.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return e;
  }

  var fab = el('button', {
    className: 'chat-fab',
    'aria-label': 'Abrir chat',
    type: 'button',
    onClick: toggleChat
  });
  fab.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';

  var messagesDiv = el('div', { className: 'chat-messages', id: 'chatMessages' });
  var textarea = el('textarea', {
    className: 'chat-input',
    placeholder: 'Digite sua mensagem...',
    rows: '1',
    'aria-label': 'Mensagem'
  });
  var sendBtn = el('button', {
    className: 'chat-send',
    type: 'button',
    'aria-label': 'Enviar',
    onClick: send
  });
  sendBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';

  var inputRow = el('div', { className: 'chat-input-row' }, [textarea, sendBtn]);

  var header = el('div', { className: 'chat-panel__header' }, [
    el('span', { textContent: 'Assistente Tannus' }),
    el('button', {
      type: 'button',
      'aria-label': 'Fechar chat',
      className: 'chat-panel__close',
      onClick: toggleChat
    })
  ]);
  header.querySelector('.chat-panel__close').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

  var panel = el('div', { className: 'chat-panel', 'aria-hidden': 'true' }, [header, messagesDiv, inputRow]);

  function addBubble(text, cls) {
    var bubble = el('div', { className: 'chat-bubble ' + cls, textContent: text });
    messagesDiv.appendChild(bubble);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    return bubble;
  }

  function showTyping() {
    var t = el('div', { className: 'chat-bubble chat-bubble-ai chat-typing', id: 'chatTyping' });
    t.innerHTML = '<span></span><span></span><span></span>';
    messagesDiv.appendChild(t);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    return t;
  }

  function removeTyping() {
    var t = document.getElementById('chatTyping');
    if (t) t.remove();
  }

  function toggleChat() {
    isOpen = !isOpen;
    panel.classList.toggle('chat-panel--open', isOpen);
    panel.setAttribute('aria-hidden', String(!isOpen));
    fab.classList.toggle('chat-fab--active', isOpen);
    if (isOpen) {
      textarea.focus();
      if (messagesDiv.children.length === 0) {
        addBubble('Olá! Sou o assistente da Tannus IA. Como posso ajudar?', 'chat-bubble-ai');
      }
    }
  }

  function send() {
    var msg = textarea.value.trim();
    if (!msg) return;
    textarea.value = '';
    textarea.style.height = 'auto';
    addBubble(msg, 'chat-bubble-user');
    var typing = showTyping();

    fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: msg })
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        removeTyping();
        addBubble(data.reply || 'Sem resposta.', 'chat-bubble-ai');
      })
      .catch(function () {
        removeTyping();
        addBubble('Erro ao conectar com o assistente. Tente novamente.', 'chat-bubble-ai');
      });
  }

  textarea.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });
  textarea.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) toggleChat();
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.body.appendChild(fab);
    document.body.appendChild(panel);
  });
})();
