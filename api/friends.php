<?php
// api/friends.php — invitations et gestion d'amitié
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/helpers/response.php';

$action = $_GET['action'] ?? '';
$uid    = require_auth();
$data   = body_json();

switch ($action) {

    case 'send': {
        $rid = (int)($data['user_id'] ?? 0);
        if (!$rid || $rid === $uid) json_error('Utilisateur invalide');
        try {
            $stmt = db()->prepare('INSERT INTO friendships (sender_id, receiver_id, status) VALUES (?,?,"pending")');
            $stmt->execute([$uid, $rid]);
        } catch (PDOException $e) {
            json_error('Invitation déjà envoyée');
        }
        json_ok(['message' => 'Invitation envoyée']);
    }

    case 'respond': {
        $fid = (int)($data['id'] ?? 0);
        $ok  = ($data['accept'] ?? false) ? 'accepted' : 'refused';
        $stmt = db()->prepare('UPDATE friendships SET status = ? WHERE id = ? AND receiver_id = ?');
        $stmt->execute([$ok, $fid, $uid]);
        json_ok(['message' => 'OK']);
    }

    case 'pending': { // invitations reçues en attente
        $stmt = db()->prepare(
            'SELECT f.id, u.id AS user_id, u.nom, u.prenom, u.avatar
             FROM friendships f JOIN users u ON u.id = f.sender_id
             WHERE f.receiver_id = ? AND f.status = "pending"'
        );
        $stmt->execute([$uid]);
        json_ok(['requests' => $stmt->fetchAll()]);
    }

    case 'list': { // mes amis acceptés
        $stmt = db()->prepare(
            'SELECT u.id, u.nom, u.prenom, u.avatar
             FROM friendships f
             JOIN users u ON u.id = IF(f.sender_id=?, f.receiver_id, f.sender_id)
             WHERE (f.sender_id=? OR f.receiver_id=?) AND f.status="accepted"'
        );
        $stmt->execute([$uid,$uid,$uid]);
        json_ok(['friends' => $stmt->fetchAll()]);
    }

    default: json_error('Action inconnue', 404);
}
