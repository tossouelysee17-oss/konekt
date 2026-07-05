<?php
// ============================================================
//  Helpers de réponse JSON + CORS + parsing du body
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_ok($data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true] + (is_array($data) ? $data : ['data' => $data]));
    exit;
}

function json_error(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

function body_json(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Récupère l'ID utilisateur envoyé par le client (header X-User-Id).
// Comme demandé par l'énoncé, la session est en sessionStorage côté JS ;
// on l'envoie donc à chaque requête via un header.
function current_user_id(): ?int {
    $id = $_SERVER['HTTP_X_USER_ID'] ?? null;
    return $id ? (int)$id : null;
}

function require_auth(): int {
    $id = current_user_id();
    if (!$id) json_error('Non authentifié', 401);
    return $id;
}

function require_role(array $roles): array {
    $uid = require_auth();
    $stmt = db()->prepare('SELECT id, role FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if (!$u || !in_array($u['role'], $roles, true)) {
        json_error('Accès refusé', 403);
    }
    return $u;
}
