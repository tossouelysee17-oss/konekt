<?php
// api/users.php — profil, liste des utilisateurs, modification
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$data   = body_json();

switch ($action) {

    case 'list': { // liste des utilisateurs (pour la page Amis)
        $uid = require_auth();
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = db()->prepare(
            "SELECT u.id, u.nom, u.prenom, u.avatar, u.bio,
                    (SELECT status FROM friendships f
                       WHERE (f.sender_id=u.id AND f.receiver_id=?)
                          OR (f.receiver_id=u.id AND f.sender_id=?)
                       LIMIT 1) AS friend_status,
                    (SELECT sender_id FROM friendships f
                       WHERE (f.sender_id=u.id AND f.receiver_id=?)
                          OR (f.receiver_id=u.id AND f.sender_id=?)
                       LIMIT 1) AS request_sender
             FROM users u
             WHERE u.id <> ? AND (u.nom LIKE ? OR u.prenom LIKE ?)
             ORDER BY u.prenom"
        );
        $stmt->execute([$uid,$uid,$uid,$uid,$uid,$q,$q]);
        json_ok(['users' => $stmt->fetchAll()]);
    }

    case 'profile': {
        require_auth();
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) json_error('ID requis');
        $stmt = db()->prepare('SELECT id, nom, prenom, email, avatar, bio, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if (!$u) json_error('Utilisateur introuvable', 404);
        json_ok(['user' => $u]);
    }

    case 'update': {
        $uid = require_auth();
        $fields = [];
        $vals   = [];
        foreach (['nom','prenom','bio','avatar'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $vals[] = $data[$f]; }
        }
        if (!$fields) json_error('Aucun champ à mettre à jour');
        $vals[] = $uid;
        $stmt = db()->prepare('UPDATE users SET ' . implode(',', $fields) . ' WHERE id = ?');
        $stmt->execute($vals);
        $s = db()->prepare('SELECT id,nom,prenom,email,avatar,bio,role FROM users WHERE id = ?');
        $s->execute([$uid]);
        json_ok(['user' => $s->fetch()]);
    }

    case 'change-password': {
        $uid = require_auth();
        if (empty($data['ancien']) || empty($data['nouveau'])) json_error('Champs manquants');
        if (strlen($data['nouveau']) < 6) json_error('Nouveau mot de passe trop court');
        $s = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $s->execute([$uid]);
        $u = $s->fetch();
        if (!$u || !password_verify($data['ancien'], $u['password_hash'])) {
            json_error('Ancien mot de passe incorrect', 401);
        }
        $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $up->execute([password_hash($data['nouveau'], PASSWORD_BCRYPT), $uid]);
        json_ok(['message' => 'Mot de passe changé']);
    }

    default:
        json_error('Action inconnue', 404);
}
