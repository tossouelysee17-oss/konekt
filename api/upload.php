<?php
// api/upload.php — upload d'image (avatar, post, message)
// POST multipart/form-data avec un champ "file"
require_once __DIR__ . '/helpers/response.php';
require_auth();

if (empty($_FILES['file'])) json_error('Aucun fichier');
$f = $_FILES['file'];
if ($f['error'] !== UPLOAD_ERR_OK) json_error('Erreur upload');
if ($f['size'] > 5 * 1024 * 1024) json_error('Fichier trop volumineux (5 Mo max)');

$allowed = ['image/jpeg' => 'jpg','image/png' => 'png','image/gif' => 'gif','image/webp' => 'webp'];
$mime = mime_content_type($f['tmp_name']);
if (!isset($allowed[$mime])) json_error('Format non supporté');

$dir = __DIR__ . '/../assets/images/uploads';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
$name = uniqid('u_', true) . '.' . $allowed[$mime];
$dest = $dir . '/' . $name;
if (!move_uploaded_file($f['tmp_name'], $dest)) json_error('Impossible d\'écrire le fichier');

// URL relative que le front peut afficher partout
json_ok(['url' => 'assets/images/uploads/' . $name]);
