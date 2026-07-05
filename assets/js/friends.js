const Friends = {
  init() {
    document.getElementById('q').oninput = (e) => {
      clearTimeout(this._t);
      this._t = setTimeout(() => this.loadUsers(e.target.value), 250);
    };
    this.loadPending();
    this.loadUsers('');
    this.loadMine();
  },

  async loadPending() {
    try {
      const r = await api('friends.php?action=pending');
      const el = document.getElementById('pending');
      if (!r.requests.length) { el.innerHTML = '<div class="text-muted">Aucune invitation en attente.</div>'; return; }
      el.innerHTML = r.requests.map(u => `
        <div class="friend-item between" style="justify-content:space-between">
          <div class="flex gap-2" style="align-items:center">
            <img src="${_basePath() + esc(u.avatar || 'assets/images/avatar-default.svg')}" style="width:40px;height:40px;border-radius:50%">
            <div><div class="name">${esc(u.prenom)} ${esc(u.nom)}</div></div>
          </div>
          <div class="flex gap-2">
            <button class="btn btn-sm" onclick="Friends.respond(${u.id}, true, this)">Accepter</button>
            <button class="btn btn-ghost btn-sm" onclick="Friends.respond(${u.id}, false, this)">Refuser</button>
          </div>
        </div>`).join('');
    } catch (err) { toast(err.message,'error'); }
  },

  async respond(friendshipId, accept) {
    try {
      await api('friends.php?action=respond', { method:'POST', body:{ id:friendshipId, accept }});
      this.loadPending(); this.loadMine();
    } catch (err) { toast(err.message,'error'); }
  },

  async loadUsers(q) {
    try {
      const r = await api('users.php?action=list&q=' + encodeURIComponent(q||''));
      const el = document.getElementById('users');
      if (!r.users.length) { el.innerHTML = '<div class="text-muted">Aucun utilisateur.</div>'; return; }
      el.innerHTML = r.users.map(u => {
        let btn = '';
        if (u.friend_status === 'accepted') btn = '<span class="text-muted" style="font-size:13px">Ami</span>';
        else if (u.friend_status === 'pending') btn = '<span class="text-muted" style="font-size:13px">En attente</span>';
        else btn = `<button class="btn btn-sm" onclick="Friends.invite(${u.id}, this)">Ajouter</button>`;
        return `
        <div class="friend-item" style="justify-content:space-between">
          <a href="profil.html?id=${u.id}" class="flex gap-2" style="align-items:center;color:inherit">
            <img src="${_basePath() + esc(u.avatar || 'assets/images/avatar-default.svg')}" style="width:40px;height:40px;border-radius:50%">
            <div><div class="name">${esc(u.prenom)} ${esc(u.nom)}</div><div class="text-muted" style="font-size:12px">${esc(u.bio || '')}</div></div>
          </a>
          ${btn}
        </div>`;
      }).join('');
    } catch (err) { toast(err.message,'error'); }
  },

  async invite(uid, btn) {
    try {
      await api('friends.php?action=send', { method:'POST', body:{ user_id: uid }});
      btn.outerHTML = '<span class="text-muted" style="font-size:13px">Invitation envoyée</span>';
    } catch (err) { toast(err.message,'error'); }
  },

  async loadMine() {
    try {
      const r = await api('friends.php?action=list');
      const el = document.getElementById('myFriends');
      if (!r.friends.length) { el.innerHTML = '<div class="text-muted">Aucun ami.</div>'; return; }
      el.innerHTML = r.friends.map(f => `
        <a class="friend-item" href="profil.html?id=${f.id}">
          <img src="${_basePath() + esc(f.avatar || 'assets/images/avatar-default.svg')}">
          <span class="n">${esc(f.prenom)} ${esc(f.nom)}</span>
        </a>`).join('');
    } catch (_) {}
  },
};
