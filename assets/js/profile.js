const Profile = {
  async init() {
    const me = Session.get();
    const id = parseInt(new URLSearchParams(location.search).get('id')) || me.id;
    this.viewedId = id;
    await this.load(id);
    if (id === me.id) {
      document.getElementById('ownEdit').classList.remove('hidden');
      this.bindForms();
    }
  },

  async load(id) {
    const r = await api('users.php?action=profile&id=' + id);
    const u = r.user;
    const base = _basePath();
    document.getElementById('header').innerHTML = `
      <div class="flex gap-4" style="align-items:center">
        <img src="${base + esc(u.avatar || 'assets/images/avatar-default.svg')}" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:2px solid var(--gold)">
        <div>
          <h2 style="margin:0">${esc(u.prenom)} ${esc(u.nom)}</h2>
          <div class="text-muted">${esc(u.email)}</div>
          <p class="mt-2">${esc(u.bio || '—')}</p>
        </div>
      </div>`;
    if (id === Session.get().id) {
      const f = document.getElementById('editForm');
      f.prenom.value = u.prenom;
      f.nom.value = u.nom;
      f.bio.value = u.bio || '';
      this.currentAvatar = u.avatar;
    }
  },

  bindForms() {
    document.getElementById('avatarFile').onchange = async (e) => {
      const f = e.target.files[0]; if (!f) return;
      try { this.currentAvatar = await uploadImage(f); toast('Image chargée. Clique "Enregistrer".'); }
      catch (err) { toast(err.message,'error'); }
    };

    document.getElementById('editForm').onsubmit = async (e) => {
      e.preventDefault();
      try {
        const r = await api('users.php?action=update', { method:'POST', body:{
          nom: e.target.nom.value, prenom: e.target.prenom.value,
          bio: e.target.bio.value, avatar: this.currentAvatar
        }});
        Session.set(r.user);
        document.getElementById('editMsg').innerHTML = '<div class="alert alert-success">Profil mis à jour</div>';
        this.load(r.user.id);
        renderNavbar('profil');
      } catch (err) {
        document.getElementById('editMsg').innerHTML = '<div class="alert alert-error">'+esc(err.message)+'</div>';
      }
    };

    document.getElementById('pwdForm').onsubmit = async (e) => {
      e.preventDefault();
      try {
        await api('users.php?action=change-password', { method:'POST', body:{
          ancien: e.target.ancien.value, nouveau: e.target.nouveau.value
        }});
        document.getElementById('pwdMsg').innerHTML = '<div class="alert alert-success">Mot de passe mis à jour</div>';
        e.target.reset();
      } catch (err) {
        document.getElementById('pwdMsg').innerHTML = '<div class="alert alert-error">'+esc(err.message)+'</div>';
      }
    };
  },
};
