<?php
// api/articles.php — CRUD des publications
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? 'feed';
$data   = body_json();

switch ($action) {

    case 'feed': {
        $uid = require_auth();
        $stmt = db()->prepare(
            "SELECT a.*, u.nom, u.prenom, u.avatar,
                (SELECT COUNT(*) FROM likes l WHERE l.article_id=a.id AND l.type='like') AS likes_count,
                (SELECT COUNT(*) FROM likes l WHERE l.article_id=a.id AND l.type='dislike') AS dislikes_count,
                (SELECT COUNT(*) FROM comments c WHERE c.article_id=a.id) AS comments_count,
                (SELECT type FROM likes l WHERE l.article_id=a.id AND l.user_id=? LIMIT 1) AS my_reaction
             FROM articles a
             JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT 100"
        );
        $stmt->execute([$uid]);
        json_ok(['articles' => $stmt->fetchAll()]);
    }

    case 'create': {
        $uid = require_auth();
        $contenu = trim($data['contenu'] ?? '');
        $image   = $data['image'] ?? null;
        if ($contenu === '' && !$image) json_error('Contenu vide');
        $stmt = db()->prepare('INSERT INTO articles (user_id, contenu, image) VALUES (?,?,?)');
        $stmt->execute([$uid, $contenu, $image]);
        json_ok(['id' => (int)db()->lastInsertId()]);
    }

    case 'delete': {
        $uid = require_auth();
        $id = (int)($data['id'] ?? 0);
        if (!$id) json_error('ID requis');
        // seul l'auteur peut supprimer via cette route ; admins passent par admin.php
        $stmt = db()->prepare('DELETE FROM articles WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $uid]);
        json_ok(['deleted' => $stmt->rowCount()]);
    }

    default: json_error('Action inconnue', 404);
}
