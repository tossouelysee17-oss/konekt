# 🌐 Konekt — Réseau social (Projet PHP / AJAX)

Réseau social type Facebook en **PHP natif + MySQL + AJAX**, avec un chat
temps réel **Node.js / Socket.io**.

---

## 👥 Membres du groupe (à compléter)
________________________________________________________________________
| # | Nom & Prénom              | Rôle sur le projet                   |
|---|---------------------------|--------------------------------------|
| 1 | TOSSOU Elysée             | Fondations & Authentification        |
| 2 | de SOUZA Steve comlan           | Fil d'articles, likes & commentaires |
| 3 | ALLOGNON Narcisse         | Amis, profil & chat                  |
| 4 | GANMANDOUALO Destiny      | Back-office (dashboard, gestion)     |
|___|___________________________|______________________________________|
---

## 🚀 Installation — 4 étapes (à suivre par chaque membre du groupe)

### 1️⃣ Installer XAMPP
Télécharger et installer **XAMPP** : <https://www.apachefriends.org/>
Ouvrir le panneau de contrôle → cliquer **Start** sur **Apache** et **MySQL**.

### 2️⃣ Copier le dossier `konekt`
Récupérer le projet depuis GitHub, puis copier **uniquement le dossier
`konekt/`** dans le dossier `htdocs` de XAMPP :

- **Windows** : `C:\xampp\htdocs\konekt`
- **Mac** : `/Applications/XAMPP/htdocs/konekt`
- **Linux** : `/opt/lampp/htdocs/konekt`

### 3️⃣ Importer la base de données
1. Ouvrir <http://localhost/phpmyadmin>
2. Cliquer sur l'onglet **Importer**
3. Sélectionner le fichier **`konekt/sql/konekt.sql`**
4. Cliquer sur **Exécuter** en bas de la page

C'est fait. La base `konekt` est créée avec les 4 comptes de démo prêts
à l'emploi (aucun script supplémentaire à lancer).

### 4️⃣ Ouvrir le site
Aller sur : **<http://localhost/konekt>**

---

## 🔑 Identifiants de test

Mot de passe pour **tous** les comptes : **`Admin123!`**
________________________________________________
| Rôle          | Email                        |
|---------------|------------------------------|
| Administrateur| `admin@konekt.local`         |
| Modérateur    | `moderateur@konekt.local`    |
| Utilisateur   | `moussa@konekt.local`        |
| Utilisateur   | `fatou@konekt.local`         |
|_______________|______________________________|
- Espace membre : <http://localhost/konekt/vues/clients/login.html>
- Back-office : <http://localhost/konekt/vues/back-office/admin-login.html>

---

## 💬 (Optionnel) Activer le chat temps réel

Le chat fonctionne **par défaut en polling AJAX** (rafraîchissement toutes
les 3s) — pas besoin de Node.js pour tester.

Pour le vrai temps réel via Socket.io :
1. Installer **Node.js 18+** : <https://nodejs.org/>
2. Ouvrir un terminal dans `konekt/chat-server/`
3. Lancer :
   ```bash
   npm install
   node server.js
   ```

---

## 🎨 Identité

- Palette : bleu nuit `#0f1b3d`, or `#c9a84c`, crème `#f5f0e0`
- Typographie : Inter + Georgia (logo)
- Design sobre, éditorial

---

## 📁 Architecture

```
konekt/
├── index.html                  ← landing
├── assets/  (css, js, images)
├── vues/
│   ├── clients/       (login, register, forgot, reset, accueil, amis, profil, chat)
│   └── back-office/   (admin-login, dashboard, gestion-articles, ...)
├── api/                         ← scripts PHP
│   ├── auth.php, articles.php, likes.php, comments.php,
│   │   friends.php, messages.php, upload.php, admin.php, stats.php
│   ├── config/  (db.php, mailer.php)
│   └── helpers/ (response.php)
├── chat-server/                 ← serveur Node Socket.io (optionnel)
└── sql/konekt.sql               ← script complet de la BDD
```

---

## ✅ Fonctionnalités

- **Auth** : inscription, connexion, mot de passe oublié (emails HTML)
- **Fil** : publications avec image, likes/dislikes, commentaires AJAX
- **Amis** : recherche, invitations (envoyer / accepter / refuser)
- **Profil** : édition infos + photo + mot de passe
- **Chat** : conversations, envoi texte + images, temps réel Socket.io
- **Back-office** : dashboard, gestion articles / utilisateurs / modérateurs
- **Zéro rechargement** après le chargement initial (tout en `fetch`)
- **Sessions** via `sessionStorage`

---

## 📧 Emails en local

Sur XAMPP, `mail()` n'envoie rien sans SMTP configuré. Tous les emails sont
journalisés dans `konekt/logs/emails.log` — ouvre ce fichier après une
inscription ou "mot de passe oublié" pour récupérer le lien.

---

## 🔒 Sécurité

- Mots de passe hashés bcrypt (`password_hash` / `password_verify`)
- Requêtes préparées PDO (anti-injection SQL)
- Validation d'input côté serveur
- Contrôle des rôles côté serveur pour toutes les actions admin

---

## 🐛 Dépannage

| Problème | Solution |
|---|---|
| "Erreur BDD" | Vérifier qu'Apache **et** MySQL sont démarrés dans XAMPP |
| Login échoue | Vérifier que l'import de `konekt.sql` s'est bien passé (4 comptes visibles dans phpMyAdmin) |
| Images pas visibles | Vérifier les droits d'écriture sur `konekt/assets/images/uploads/` |
| Page blanche | Ouvrir via `http://localhost/konekt` — jamais via double-clic sur les `.html` |
