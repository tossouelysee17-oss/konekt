<?php
// api/messages.php — chat : conversations et historique
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$uid = require_auth();

switch ($action) {

    case 'conversations': {
        // Liste les utilisateurs avec qui j'ai déjà échangé + dernier message
        $stmt = db()->prepare(
          "SELECT u.id, u.nom, u.prenom, u.avatar,
                  (SELECT contenu FROM messages m
                     WHERE (m.sender_id=u.id AND m.receiver_id=?)
                        OR (m.receiver_id=u.id AND m.sender_id=?)
                     ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                  (SELECT created_at FROM messages m
                     WHERE (m.sender_id=u.id AND m.receiver_id=?)
                        OR (m.receiver_id=u.id AND m.sender_id=?)
                     ORDER BY m.created_at DESC LIMIT 1) AS last_at
             FROM users u
             WHERE u.id IN (
               SELECT DISTINCT IF(sender_id=?, receiver_id, sender_id)
               FROM messages WHERE sender_id=? OR receiver_id=?
             )
             ORDER BY last_at DESC"
        );
        $stmt->execute([$uid,$uid,$uid,$uid,$uid,$uid,$uid]);
        json_ok(['conversations' => $stmt->fetchAll()]);
    }

    case 'history': {
        $other = (int)($_GET['with'] ?? 0);
        if (!$other) json_error('with requis');
        $stmt = db()->prepare(
          'SELECT * FROM messages
           WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
           ORDER BY created_at ASC'
        );
        $stmt->execute([$uid, $other, $other, $uid]);
        // marquer lus
        db()->prepare('UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=?')
            ->execute([$other, $uid]);
        json_ok(['messages' => $stmt->fetchAll()]);
    }

    case 'send': {
        $data  = body_json();
        $rid   = (int)($data['receiver_id'] ?? 0);
        $txt   = trim($data['contenu'] ?? '');
        $img   = $data['image'] ?? null;
        if (!$rid || ($txt === '' && !$img)) json_error('Message vide');
        $stmt = db()->prepare('INSERT INTO messages (sender_id, receiver_id, contenu, image) VALUES (?,?,?,?)');
        $stmt->execute([$uid, $rid, $txt, $img]);
        json_ok(['id' => (int)db()->lastInsertId()]);
    }

    default: json_error('Action inconnue', 404);
}
