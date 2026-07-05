<?php
// api/comments.php — lecture et ajout de commentaires
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$uid = require_auth();

if ($action === 'list') {
    $aid = (int)($_GET['article_id'] ?? 0);
    if (!$aid) json_error('article_id requis');
    $stmt = db()->prepare(
        "SELECT c.id, c.contenu, c.created_at, u.id AS user_id, u.nom, u.prenom, u.avatar
         FROM comments c JOIN users u ON u.id = c.user_id
         WHERE c.article_id = ? ORDER BY c.created_at ASC"
    );
    $stmt->execute([$aid]);
    json_ok(['comments' => $stmt->fetchAll()]);
}

if ($action === 'add') {
    $data = body_json();
    $aid  = (int)($data['article_id'] ?? 0);
    $txt  = trim($data['contenu'] ?? '');
    if (!$aid || $txt === '') json_error('Champs requis');
    $stmt = db()->prepare('INSERT INTO comments (article_id, user_id, contenu) VALUES (?,?,?)');
    $stmt->execute([$aid, $uid, $txt]);
    json_ok(['id' => (int)db()->lastInsertId()]);
}

json_error('Action inconnue', 404);
