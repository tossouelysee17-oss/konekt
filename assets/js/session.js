/* ============================================
   session.js — gestion de la session utilisateur
   Stockée dans sessionStorage (comme demandé).
   ============================================ */

const Session = {
  KEY: 'konekt.user',

  set(user) { sessionStorage.setItem(this.KEY, JSON.stringify(user)); },

  get() {
    const raw = sessionStorage.getItem(this.KEY);
    return raw ? JSON.parse(raw) : null;
  },

  clear() { sessionStorage.removeItem(this.KEY); },

  // Protège une page client : redirige vers login si non connecté
  requireUser() {
    const u = this.get();
    if (!u) {
      window.location.href = _basePath() + 'vues/clients/login.html';
      return null;
    }
    return u;
  },

  // Protège une page admin
  requireStaff() {
    const u = this.get();
    if (!u || (u.role !== 'admin' && u.role !== 'moderator')) {
      window.location.href = _basePath() + 'vues/back-office/admin-login.html';
      return null;
    }
    return u;
  },

  logout() {
    this.clear();
    window.location.href = _basePath();
  },
};

function _basePath() {
  const p = window.location.pathname;
  const i = p.indexOf('/konekt/');
  return i >= 0 ? p.substring(0, i + 8) : '/konekt/';
}

// Injecte la navbar client dans les pages qui ont <div id="navbar"></div>
function renderNavbar(active = '') {
  const el = document.getElementById('navbar');
  if (!el) return;
  const u = Session.get();
  if (!u) return;
  const base = _basePath();
  el.innerHTML = `
    <div class="navbar">
      <a href="${base}vues/clients/accueil.html" class="brand">KONEKT</a>
      <nav>
        <a href="${base}vues/clients/accueil.html" class="${active==='accueil'?'active':''}">Accueil</a>
        <a href="${base}vues/clients/amis.html" class="${active==='amis'?'active':''}">Amis</a>
        <a href="${base}vues/clients/chat.html" class="${active==='chat'?'active':''}">Chat</a>
        <a href="${base}vues/clients/profil.html" class="${active==='profil'?'active':''}">Profil</a>
      </nav>
      <div class="user">
        <img src="${base}${esc(u.avatar || 'assets/images/avatar-default.svg')}" alt="">
        <button class="btn btn-ghost btn-sm" onclick="Session.logout()">Déconnexion</button>
      </div>
    </div>`;
}
