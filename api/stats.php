<?php
// api/stats.php — statistiques pour le dashboard admin
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';
require_role(['moderator','admin']);

$q = fn($sql) => (int)db()->query($sql)->fetchColumn();

json_ok([
    'stats' => [
        'users'       => $q('SELECT COUNT(*) FROM users'),
        'admins'      => $q("SELECT COUNT(*) FROM users WHERE role='admin'"),
        'moderators'  => $q("SELECT COUNT(*) FROM users WHERE role='moderator'"),
        'articles'    => $q('SELECT COUNT(*) FROM articles'),
        'comments'    => $q('SELECT COUNT(*) FROM comments'),
        'likes'       => $q("SELECT COUNT(*) FROM likes WHERE type='like'"),
        'dislikes'    => $q("SELECT COUNT(*) FROM likes WHERE type='dislike'"),
        'friendships' => $q("SELECT COUNT(*) FROM friendships WHERE status='accepted'"),
        'messages'    => $q('SELECT COUNT(*) FROM messages'),
        'new_users_7d'=> $q('SELECT COUNT(*) FROM users WHERE created_at > NOW() - INTERVAL 7 DAY'),
        'new_posts_7d'=> $q('SELECT COUNT(*) FROM articles WHERE created_at > NOW() - INTERVAL 7 DAY'),
    ],
]);
