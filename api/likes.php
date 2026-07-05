<?php
// api/likes.php — gestion des likes / dislikes (toggle)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$uid  = require_auth();
$data = body_json();
$aid  = (int)($data['article_id'] ?? 0);
$type = ($data['type'] ?? '') === 'dislike' ? 'dislike' : 'like';
if (!$aid) json_error('article_id requis');

$stmt = db()->prepare('SELECT id, type FROM likes WHERE article_id = ? AND user_id = ?');
$stmt->execute([$aid, $uid]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['type'] === $type) {
        // Même réaction → on retire
        $del = db()->prepare('DELETE FROM likes WHERE id = ?');
        $del->execute([$existing['id']]);
    } else {
        // Réaction inverse → on change
        $up = db()->prepare('UPDATE likes SET type = ? WHERE id = ?');
        $up->execute([$type, $existing['id']]);
    }
} else {
    $ins = db()->prepare('INSERT INTO likes (article_id, user_id, type) VALUES (?,?,?)');
    $ins->execute([$aid, $uid, $type]);
}

// Renvoie les compteurs à jour
$c = db()->prepare(
    "SELECT
       (SELECT COUNT(*) FROM likes WHERE article_id=? AND type='like') AS likes_count,
       (SELECT COUNT(*) FROM likes WHERE article_id=? AND type='dislike') AS dislikes_count,
       (SELECT type FROM likes WHERE article_id=? AND user_id=? LIMIT 1) AS my_reaction"
);
$c->execute([$aid, $aid, $aid, $uid]);
json_ok($c->fetch());
