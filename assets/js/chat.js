const Chat = {
  activeUser: null,
  socket: null,
  pendingImage: null,
  pollTimer: null,

  init() {
    this.me = Session.get();
    this.connectSocket();
    this.loadConversations();

    document.getElementById('chatSearch').oninput = (e) => {
      clearTimeout(this._t);
      this._t = setTimeout(() => this.search(e.target.value), 200);
    };
    document.getElementById('chatForm').onsubmit = (e) => { e.preventDefault(); this.send(); };
    document.getElementById('chatImage').onchange = async (e) => {
      const f = e.target.files[0]; if (!f) return;
      try { this.pendingImage = await uploadImage(f); toast('Image prête — clique Envoyer'); }
      catch (err) { toast(err.message,'error'); }
    };
  },

  connectSocket() {
    if (typeof io === 'undefined') {
      // Fallback : polling toutes les 3 secondes (comme autorisé par l'énoncé)
      this.pollTimer = setInterval(() => {
        if (this.activeUser) this.loadHistory(this.activeUser.id, true);
      }, 3000);
      return;
    }
    this.socket = io('http://localhost:3001');
    this.socket.emit('register', this.me.id);
    this.socket.on('message', (m) => {
      if (this.activeUser && (m.sender_id === this.activeUser.id || m.receiver_id === this.activeUser.id)) {
        this.appendMessage(m);
      }
      this.loadConversations();
    });
  },

  async loadConversations() {
    try {
      const r = await api('messages.php?action=conversations');
      const el = document.getElementById('chatList');
      if (!r.conversations.length) { el.innerHTML = '<div class="text-muted" style="padding:20px;font-size:13px">Aucune conversation. Cherche un ami ci-dessus.</div>'; return; }
      el.innerHTML = r.conversations.map(c => `
        <div class="item ${this.activeUser && this.activeUser.id===c.id ? 'active':''}" onclick="Chat.open(${c.id}, '${esc(c.prenom)} ${esc(c.nom)}', '${esc(c.avatar||'assets/images/avatar-default.svg')}')">
          <img src="${_basePath() + esc(c.avatar || 'assets/images/avatar-default.svg')}">
          <div style="flex:1;overflow:hidden">
            <div class="who">${esc(c.prenom)} ${esc(c.nom)}</div>
            <div class="last">${esc(c.last_message || '')}</div>
          </div>
        </div>`).join('');
    } catch (_) {}
  },

  async search(q) {
    const el = document.getElementById('searchResults');
    if (!q) { el.innerHTML = ''; return; }
    try {
      const r = await api('users.php?action=list&q=' + encodeURIComponent(q));
      el.innerHTML = r.users.slice(0,5).map(u => `
        <div class="item" style="border-radius:6px" onclick="Chat.open(${u.id}, '${esc(u.prenom)} ${esc(u.nom)}', '${esc(u.avatar||'assets/images/avatar-default.svg')}')">
          <img src="${_basePath() + esc(u.avatar || 'assets/images/avatar-default.svg')}" style="width:32px;height:32px;border-radius:50%">
          <div class="who">${esc(u.prenom)} ${esc(u.nom)}</div>
        </div>`).join('');
    } catch (_) {}
  },

  open(id, name, avatar) {
    this.activeUser = { id, name, avatar };
    document.getElementById('chatHead').className = '';
    document.getElementById('chatHead').innerHTML = `
      <div class="chat-head">
        <img src="${_basePath() + esc(avatar)}">
        <div><div style="color:var(--cream);font-weight:600">${esc(name)}</div></div>
      </div>`;
    document.getElementById('chatBody').classList.remove('hidden');
    document.getElementById('chatForm').classList.remove('hidden');
    this.loadHistory(id);
    this.loadConversations();
  },

  async loadHistory(id, silent = false) {
    try {
      const r = await api('messages.php?action=history&with=' + id);
      const body = document.getElementById('chatBody');
      const wasBottom = body.scrollTop + body.clientHeight >= body.scrollHeight - 50;
      body.innerHTML = r.messages.map(m => this.tpl(m)).join('');
      if (!silent || wasBottom) body.scrollTop = body.scrollHeight;
    } catch (err) { if (!silent) toast(err.message,'error'); }
  },

  tpl(m) {
    const mine = m.sender_id == this.me.id;
    const img = m.image ? `<img src="${_basePath() + esc(m.image)}">` : '';
    return `<div class="msg ${mine?'mine':''}">
      <div>
        <div class="bubble">${esc(m.contenu || '')}${img}</div>
        <div class="time">${timeAgo(m.created_at)}</div>
      </div>
    </div>`;
  },

  appendMessage(m) {
    const body = document.getElementById('chatBody');
    body.insertAdjacentHTML('beforeend', this.tpl(m));
    body.scrollTop = body.scrollHeight;
  },

  async send() {
    if (!this.activeUser) return;
    const input = document.getElementById('chatText');
    const txt = input.value.trim();
    if (!txt && !this.pendingImage) return;
    try {
      const r = await api('messages.php?action=send', { method:'POST', body:{
        receiver_id: this.activeUser.id, contenu: txt, image: this.pendingImage
      }});
      const msg = {
        id: r.id, sender_id: this.me.id, receiver_id: this.activeUser.id,
        contenu: txt, image: this.pendingImage,
        created_at: new Date().toISOString().slice(0,19).replace('T',' ')
      };
      this.appendMessage(msg);
      if (this.socket) this.socket.emit('message', msg);
      input.value = ''; this.pendingImage = null;
      document.getElementById('chatImage').value = '';
    } catch (err) { toast(err.message,'error'); }
  },
};
