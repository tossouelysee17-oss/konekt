/* =========================================================
   feed.js — fil d'articles : liste, publish, like, commentaires
   ========================================================= */

const Feed = {
  currentImage: null,

  async init() {
    this.bind();
    await Promise.all([this.load(), this.loadFriendsMini(), this.loadPendingMini()]);
  },

  bind() {
    document.getElementById('btnPublish').onclick = () => this.publish();
    document.getElementById('postImage').onchange = async (e) => {
      const f = e.target.files[0]; if (!f) return;
      try {
        this.currentImage = await uploadImage(f);
        const p = document.getElementById('postPreview');
        p.src = _basePath() + this.currentImage;
        p.classList.remove('hidden');
      } catch (err) { toast(err.message, 'error'); }
    };
  },

  async publish() {
    const txt = document.getElementById('postText').value.trim();
    if (!txt && !this.currentImage) return toast('Écris quelque chose ou ajoute une image', 'error');
    try {
      await api('articles.php?action=create', { method:'POST',
        body: { contenu: txt, image: this.currentImage } });
      document.getElementById('postText').value = '';
      document.getElementById('postPreview').classList.add('hidden');
      this.currentImage = null;
      document.getElementById('postImage').value = '';
      await this.load();
    } catch (err) { toast(err.message, 'error'); }
  },

  async load() {
    try {
      const r = await api('articles.php?action=feed');
      const feed = document.getElementById('feed');
      if (!r.articles.length) { feed.innerHTML = '<div class="card text-muted text-center">Aucune publication pour le moment.</div>'; return; }
      feed.innerHTML = r.articles.map(a => this.tpl(a)).join('');
      r.articles.forEach(a => this.bindPost(a.id));
    } catch (err) { toast(err.message, 'error'); }
  },

  tpl(a) {
    const base = _basePath();
    const avatar = base + esc(a.avatar || 'assets/images/avatar-default.svg');
    const cover  = a.image ? `<img class="cover" src="${base + esc(a.image)}" alt="">` : '';
    return `
    <article class="card post" data-id="${a.id}">
      <div class="post-head">
        <img src="${avatar}" alt="">
        <div>
          <div class="name">${esc(a.prenom)} ${esc(a.nom)}</div>
          <div class="date">${timeAgo(a.created_at)}</div>
        </div>
      </div>
      <div class="content">${esc(a.contenu)}</div>
      ${cover}
      <div class="actions">
        <button class="btn-like ${a.my_reaction==='like'?'on-like':''}">
          👍 <span class="c-like">${a.likes_count}</span>
        </button>
        <button class="btn-dislike ${a.my_reaction==='dislike'?'on-dislike':''}">
          👎 <span class="c-dislike">${a.dislikes_count}</span>
        </button>
        <button class="btn-comments">💬 <span class="c-comm">${a.comments_count}</span></button>
      </div>
      <div class="comments">
        <div class="comment-list"></div>
        <form class="comment-form">
          <input type="text" placeholder="Écris un commentaire…" required>
          <button class="btn btn-sm" type="submit">Envoyer</button>
        </form>
      </div>
    </article>`;
  },

  bindPost(id) {
    const root = document.querySelector(`.post[data-id="${id}"]`);
    if (!root) return;

    root.querySelector('.btn-like').onclick    = () => this.react(id, 'like', root);
    root.querySelector('.btn-dislike').onclick = () => this.react(id, 'dislike', root);

    const cBtn  = root.querySelector('.btn-comments');
    const cBox  = root.querySelector('.comments');
    const cList = root.querySelector('.comment-list');
    let loaded  = false;
    cBtn.onclick = async () => {
      cBox.classList.toggle('open');
      if (cBox.classList.contains('open') && !loaded) {
        try {
          const r = await api(`comments.php?action=list&article_id=${id}`);
          cList.innerHTML = r.comments.map(c => this.tplComment(c)).join('') || '<div class="text-muted" style="font-size:13px">Aucun commentaire.</div>';
          loaded = true;
        } catch (err) { toast(err.message,'error'); }
      }
    };
    const form = root.querySelector('.comment-form');
    form.onsubmit = async (e) => {
      e.preventDefault();
      const input = form.querySelector('input');
      const txt   = input.value.trim();
      if (!txt) return;
      try {
        await api('comments.php?action=add', { method:'POST', body:{ article_id:id, contenu:txt }});
        input.value = '';
        // recharge la liste
        const r = await api(`comments.php?action=list&article_id=${id}`);
        cList.innerHTML = r.comments.map(c => this.tplComment(c)).join('');
        const cnt = root.querySelector('.c-comm');
        cnt.textContent = r.comments.length;
      } catch (err) { toast(err.message,'error'); }
    };
  },

  tplComment(c) {
    const base = _basePath();
    return `<div class="comment">
      <img src="${base + esc(c.avatar || 'assets/images/avatar-default.svg')}">
      <div class="bubble">
        <div class="who">${esc(c.prenom)} ${esc(c.nom)}</div>
        <div class="txt">${esc(c.contenu)}</div>
      </div>
    </div>`;
  },

  async react(id, type, root) {
    try {
      const r = await api('likes.php', { method:'POST', body:{ article_id:id, type }});
      root.querySelector('.c-like').textContent    = r.likes_count;
      root.querySelector('.c-dislike').textContent = r.dislikes_count;
      const bl = root.querySelector('.btn-like');
      const bd = root.querySelector('.btn-dislike');
      bl.classList.toggle('on-like',    r.my_reaction === 'like');
      bd.classList.toggle('on-dislike', r.my_reaction === 'dislike');
    } catch (err) { toast(err.message, 'error'); }
  },

  async loadFriendsMini() {
    try {
      const r = await api('friends.php?action=list');
      const el = document.getElementById('friendsMini');
      if (!r.friends.length) { el.textContent = 'Aucun ami pour l\'instant.'; return; }
      el.innerHTML = r.friends.slice(0,8).map(f => `
        <a class="friend-item" href="profil.html?id=${f.id}">
          <img src="${_basePath() + esc(f.avatar || 'assets/images/avatar-default.svg')}">
          <span class="n">${esc(f.prenom)} ${esc(f.nom)}</span>
        </a>`).join('');
    } catch (_) {}
  },

  async loadPendingMini() {
    try {
      const r = await api('friends.php?action=pending');
      const el = document.getElementById('pendingMini');
      if (!r.requests.length) return;
      el.innerHTML = r.requests.map(u => `
        <div class="friend-item">
          <img src="${_basePath() + esc(u.avatar || 'assets/images/avatar-default.svg')}">
          <span class="n">${esc(u.prenom)} ${esc(u.nom)}</span>
        </div>`).join('') + '<a href="amis.html" class="btn btn-ghost btn-sm mt-2 btn-block">Voir tout</a>';
    } catch (_) {}
  },
};
