/* admin.js — code partagé du back-office */

function renderAdminShell(active) {
  const u = Session.requireStaff();
  if (!u) return;
  const base = _basePath();
  const links = [
    ['dashboard.html', 'Tableau de bord', 'dashboard'],
    ['gestion-articles.html', 'Articles', 'articles'],
    ['gestion-utilisateurs.html', 'Utilisateurs', 'users'],
  ];
  if (u.role === 'admin') links.push(['gestion-admins.html', 'Staff (Admin+Modérateurs)', 'admins']);

  document.getElementById('adminSide').innerHTML = `
    <div class="brand">KONEKT</div>
    ${links.map(([h,l,k]) => `<a href="${h}" class="${active===k?'active':''}">${l}</a>`).join('')}
    <div class="role-tag">
      Connecté : <b>${esc(u.prenom)} ${esc(u.nom)}</b><br>
      <span class="role-badge ${u.role}">${u.role}</span><br>
      <button class="btn btn-ghost btn-sm mt-2 btn-block" onclick="Session.logout()">Déconnexion</button>
    </div>`;
}

const Admin = {
  async loadStats() {
    const r = await api('stats.php');
    const s = r.stats;
    const cards = [
      ['Utilisateurs', s.users], ['Administrateurs', s.admins], ['Modérateurs', s.moderators],
      ['Articles', s.articles], ['Commentaires', s.comments], ['Likes', s.likes],
      ['Dislikes', s.dislikes], ['Amitiés', s.friendships], ['Messages', s.messages],
      ['Nouveaux (7j)', s.new_users_7d], ['Posts (7j)', s.new_posts_7d],
    ];
    document.getElementById('stats').innerHTML = cards.map(([l,v]) =>
      `<div class="stat"><div class="label">${l}</div><div class="value">${v}</div></div>`
    ).join('');
  },

  async loadArticles() {
    const r = await api('admin.php?action=articles');
    const el = document.getElementById('tbody');
    el.innerHTML = r.articles.map(a => `
      <tr>
        <td>#${a.id}</td>
        <td>${esc(a.prenom)} ${esc(a.nom)}</td>
        <td>${esc((a.contenu||'').slice(0,80))}${(a.contenu||'').length>80?'…':''}</td>
        <td>${new Date(a.created_at.replace(' ','T')).toLocaleString('fr-FR')}</td>
        <td><button class="btn btn-sm btn-danger" onclick="Admin.delArticle(${a.id}, this)">Supprimer</button></td>
      </tr>`).join('');
  },

  async delArticle(id, btn) {
    if (!confirm('Supprimer cet article ?')) return;
    try { await api('admin.php?action=delete-article', { method:'POST', body:{ id }}); btn.closest('tr').remove(); }
    catch (err) { toast(err.message,'error'); }
  },

  async loadUsers() {
    const r = await api('admin.php?action=users');
    const me = Session.get();
    const el = document.getElementById('tbody');
    el.innerHTML = r.users.map(u => `
      <tr>
        <td>#${u.id}</td>
        <td><img src="${_basePath() + esc(u.avatar || 'assets/images/avatar-default.svg')}">${esc(u.prenom)} ${esc(u.nom)}</td>
        <td>${esc(u.email)}</td>
        <td><span class="role-badge ${u.role}">${u.role}</span></td>
        <td>${new Date(u.created_at.replace(' ','T')).toLocaleDateString('fr-FR')}</td>
        <td>${u.id===me.id ? '<span class="text-muted">(vous)</span>' :
          `<button class="btn btn-sm btn-danger" onclick="Admin.delUser(${u.id}, this)">Supprimer</button>`}</td>
      </tr>`).join('');
  },

  async delUser(id, btn) {
    if (!confirm('Supprimer cet utilisateur ?')) return;
    try { await api('admin.php?action=delete-user', { method:'POST', body:{ id }}); btn.closest('tr').remove(); }
    catch (err) { toast(err.message,'error'); }
  },

  async loadStaff() {
    const r = await api('admin.php?action=users');
    const staff = r.users.filter(u => u.role !== 'user');
    document.getElementById('tbody').innerHTML = staff.map(u => `
      <tr>
        <td>#${u.id}</td>
        <td>${esc(u.prenom)} ${esc(u.nom)}</td>
        <td>${esc(u.email)}</td>
        <td>
          <select onchange="Admin.setRole(${u.id}, this.value)">
            <option value="user"      ${u.role==='user'?'selected':''}>user</option>
            <option value="moderator" ${u.role==='moderator'?'selected':''}>moderator</option>
            <option value="admin"     ${u.role==='admin'?'selected':''}>admin</option>
          </select>
        </td>
        <td><button class="btn btn-sm btn-danger" onclick="Admin.delUser(${u.id}, this)">Supprimer</button></td>
      </tr>`).join('');
  },

  async setRole(id, role) {
    try { await api('admin.php?action=set-role', { method:'POST', body:{ id, role }}); toast('Rôle mis à jour'); }
    catch (err) { toast(err.message,'error'); }
  },

  async createStaff(e) {
    e.preventDefault();
    const f = e.target;
    try {
      await api('admin.php?action=create-staff', { method:'POST', body:{
        nom:f.nom.value, prenom:f.prenom.value, email:f.email.value,
        password:f.password.value, role:f.role.value
      }});
      f.reset();
      Admin.loadStaff();
      toast('Compte créé');
    } catch (err) { toast(err.message,'error'); }
  },
};
