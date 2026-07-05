<?php
// ============================================================
//  api/auth.php — Inscription / Connexion / Mot de passe oublié
//  Actions : register, login, forgot, reset
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$data   = body_json();

switch ($action) {

    case 'register': {
        foreach (['nom','prenom','email','password'] as $f) {
            if (empty($data[$f])) json_error("Champ manquant : $f");
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) json_error('Email invalide');
        if (strlen($data['password']) < 6) json_error('Mot de passe trop court (6 caractères min)');

        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) json_error('Cet email est déjà utilisé');

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $ins = db()->prepare('INSERT INTO users (nom, prenom, email, password_hash) VALUES (?,?,?,?)');
        $ins->execute([$data['nom'], $data['prenom'], $data['email'], $hash]);
        $uid = (int)db()->lastInsertId();

        // Email de bienvenue HTML
        $html = email_template(
            'Bienvenue sur Konekt !',
            "Bonjour {$data['prenom']},\n\nTon compte a été créé avec succès. Tu peux dès maintenant te connecter et commencer à partager.",
            'Se connecter',
            'http://localhost/konekt/vues/clients/login.html',
            "Si tu n'es pas à l'origine de cette inscription, ignore simplement ce message."
        );
        send_html_email($data['email'], 'Bienvenue sur Konekt', $html);

        json_ok(['user_id' => $uid, 'message' => 'Inscription réussie']);
    }

    case 'login': {
        if (empty($data['email']) || empty($data['password'])) json_error('Email et mot de passe requis');
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($data['password'], $u['password_hash'])) {
            json_error('Identifiants incorrects', 401);
        }
        unset($u['password_hash'], $u['reset_token'], $u['reset_expires']);
        json_ok(['user' => $u]);
    }

    case 'admin-login': {
        if (empty($data['email']) || empty($data['password'])) json_error('Email et mot de passe requis');
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($data['password'], $u['password_hash'])) {
            json_error('Identifiants incorrects', 401);
        }
        if (!in_array($u['role'], ['admin','moderator'], true)) {
            json_error('Accès réservé au back-office', 403);
        }
        unset($u['password_hash'], $u['reset_token'], $u['reset_expires']);
        json_ok(['user' => $u]);
    }

    case 'forgot': {
        if (empty($data['email'])) json_error('Email requis');
        $stmt = db()->prepare('SELECT id, prenom FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        $u = $stmt->fetch();
        // Toujours répondre OK pour ne pas divulguer l'existence du compte
        if ($u) {
            $token = bin2hex(random_bytes(24));
            $exp = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $up = db()->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
            $up->execute([$token, $exp, $u['id']]);
            $link = 'http://localhost/konekt/vues/clients/reset.html?token=' . $token;
            $html = email_template(
                'Réinitialisation de ton mot de passe',
                "Bonjour {$u['prenom']},\n\nTu as demandé à réinitialiser ton mot de passe. Le lien ci-dessous est valable 1 heure.",
                'Choisir un nouveau mot de passe',
                $link,
                "Si tu n'as rien demandé, ignore ce message."
            );
            send_html_email($data['email'], 'Réinitialisation Konekt', $html);
        }
        json_ok(['message' => 'Si un compte existe, un email a été envoyé']);
    }

    case 'reset': {
        if (empty($data['token']) || empty($data['password'])) json_error('Champs manquants');
        if (strlen($data['password']) < 6) json_error('Mot de passe trop court');
        $stmt = db()->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()');
        $stmt->execute([$data['token']]);
        $u = $stmt->fetch();
        if (!$u) json_error('Lien invalide ou expiré', 400);
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $up = db()->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
        $up->execute([$hash, $u['id']]);
        json_ok(['message' => 'Mot de passe mis à jour']);
    }

    default:
        json_error('Action inconnue', 404);
}
