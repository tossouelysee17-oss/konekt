/* =========================================================
   api.js — wrapper AJAX autour de toutes les API PHP
   Toutes les pages passent par cette fonction.
   ========================================================= */

const API_BASE = (() => {
  // Détermine automatiquement /konekt/ selon l'URL courante
  const p = window.location.pathname;
  const i = p.indexOf('/konekt/');
  return i >= 0 ? p.substring(0, i + 8) : '/konekt/';
})();

async function api(path, { method = 'GET', body = null, form = false } = {}) {
  const opts = { method, headers: {} };
  const user = Session.get();
  if (user) opts.headers['X-User-Id'] = String(user.id);

  if (body && !form) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  } else if (form) {
    opts.body = body; // FormData
  }

  const res = await fetch(API_BASE + 'api/' + path, opts);
  let data;
  try { data = await res.json(); } catch (_) { data = { success: false, message: 'Réponse invalide' }; }
  if (!data.success) throw new Error(data.message || 'Erreur');
  return data;
}

// Upload d'image – renvoie l'URL relative
async function uploadImage(file) {
  const fd = new FormData();
  fd.append('file', file);
  const r = await api('upload.php', { method: 'POST', body: fd, form: true });
  return r.url;
}

// Format date FR courte
function timeAgo(dateStr) {
  const d = new Date(dateStr.replace(' ', 'T'));
  const s = Math.floor((Date.now() - d.getTime()) / 1000);
  if (s < 60) return "à l'instant";
  if (s < 3600) return Math.floor(s / 60) + ' min';
  if (s < 86400) return Math.floor(s / 3600) + ' h';
  if (s < 604800) return Math.floor(s / 86400) + ' j';
  return d.toLocaleDateString('fr-FR');
}

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[c]));
}

// Affiche une alerte temporaire
function toast(msg, type = 'success') {
  const el = document.createElement('div');
  el.className = 'alert alert-' + type;
  el.textContent = msg;
  el.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.3)';
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 3200);
}
