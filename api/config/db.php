<?php
// ============================================================
//  Connexion à la base de données MySQL via PDO
//  Modifie les identifiants si nécessaire (XAMPP par défaut : root, pas de mot de passe)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'konekt');
define('DB_USER', 'root');
define('DB_PASS', '');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}
