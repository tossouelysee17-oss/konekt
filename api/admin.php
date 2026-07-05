<?php
//Partie 4 : Back-office

//Destiny// Rôle: Gestion admins, utilisateur, articles, stats

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$data   = body_json();

switch ($action) {

    // ---------- Consulté par modérateur ET admin ----------
    case 'users': {
        require_role(['moderator','admin']);
        $stmt = db()->query('SELECT id, nom, prenom, email, role, avatar, created_at FROM users ORDER BY created_at DESC');
        json_ok(['users' => $stmt->fetchAll()]);
    }

    case 'articles': {
        require_role(['moderator','admin']);
        $stmt = db()->query(
            'SELECT a.*, u.nom, u.prenom FROM articles a
             JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC'
        );
        json_ok(['articles' => $stmt->fetchAll()]);
    }

    case 'delete-article': {
        require_role(['moderator','admin']);
        $id = (int)($data['id'] ?? 0);
        db()->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
        json_ok(['deleted' => true]);
    }

    case 'delete-user': {
        $me = require_role(['moderator','admin']);
        $id = (int)($data['id'] ?? 0);
        if ($id === (int)$me['id']) json_error('Impossible de se supprimer soi-même');
        // Un modérateur ne peut pas supprimer un admin ou un modérateur
        $t = db()->prepare('SELECT role FROM users WHERE id = ?');
        $t->execute([$id]);
        $target = $t->fetch();
        if (!$target) json_error('Utilisateur introuvable', 404);
        if ($me['role'] === 'moderator' && $target['role'] !== 'user') {
            json_error('Un modérateur ne peut supprimer que des utilisateurs standards', 403);
        }
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        json_ok(['deleted' => true]);
    }

    // ---------- Réservé à l'administrateur ----------
    case 'set-role': {
        require_role(['admin']);
        $id   = (int)($data['id'] ?? 0);
        $role = $data['role'] ?? 'user';
        if (!in_array($role, ['user','moderator','admin'], true)) json_error('Rôle invalide');
        db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        json_ok(['message' => 'Rôle mis à jour']);
    }

    case 'create-staff': {
        require_role(['admin']);
        foreach (['nom','prenom','email','password','role'] as $f) if (empty($data[$f])) json_error("Champ $f manquant");
        if (!in_array($data['role'], ['moderator','admin'], true)) json_error('Rôle invalide');
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        try {
            $stmt = db()->prepare('INSERT INTO users (nom,prenom,email,password_hash,role) VALUES (?,?,?,?,?)');
            $stmt->execute([$data['nom'],$data['prenom'],$data['email'],$hash,$data['role']]);
        } catch (PDOException $e) {
            json_error('Email déjà utilisé');
        }
        json_ok(['id' => (int)db()->lastInsertId()]);
    }

    default: json_error('Action inconnue', 404);
}
