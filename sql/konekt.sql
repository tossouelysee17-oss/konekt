-- ============================================================
--  KONEKT — Base de données du réseau social
--  À importer dans phpMyAdmin (onglet "Importer")
-- ============================================================

DROP DATABASE IF EXISTS konekt;
CREATE DATABASE konekt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE konekt;

-- ---------- Utilisateurs ----------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(80) NOT NULL,
  prenom VARCHAR(80) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) DEFAULT 'assets/images/avatar-default.svg',
  bio TEXT,
  role ENUM('user','moderator','admin') NOT NULL DEFAULT 'user',
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Articles (publications) ----------
CREATE TABLE articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  contenu TEXT NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Likes / Dislikes ----------
CREATE TABLE likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  user_id INT NOT NULL,
  type ENUM('like','dislike') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_reaction (article_id, user_id),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Commentaires ----------
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  article_id INT NOT NULL,
  user_id INT NOT NULL,
  contenu TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Amitiés ----------
CREATE TABLE friendships (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  status ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_pair (sender_id, receiver_id),
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- Messages (chat) ----------
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  contenu TEXT,
  image VARCHAR(255) DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  Comptes de démonstration
--  Mot de passe pour TOUS les comptes ci-dessous : Admin123!
--  (hash bcrypt réel, prêt à l'emploi — aucun script à exécuter)
-- ============================================================
INSERT INTO users (nom, prenom, email, password_hash, role, bio) VALUES
('Root','Admin','admin@konekt.local','$2y$10$2dA5bcci/wucXQy26LJkWOOTpRNSnRYCVSPD8lGcDGPYFCmLXwnEW','admin','Administrateur principal'),
('Diallo','Aissatou','moderateur@konekt.local','$2y$10$2dA5bcci/wucXQy26LJkWOOTpRNSnRYCVSPD8lGcDGPYFCmLXwnEW','moderator','Modératrice de la plateforme'),
('Ndiaye','Moussa','moussa@konekt.local','$2y$10$2dA5bcci/wucXQy26LJkWOOTpRNSnRYCVSPD8lGcDGPYFCmLXwnEW','user','Étudiant en informatique'),
('Sarr','Fatou','fatou@konekt.local','$2y$10$2dA5bcci/wucXQy26LJkWOOTpRNSnRYCVSPD8lGcDGPYFCmLXwnEW','user','Passionnée de photo');

-- Deux publications de démo pour voir le fil d'articles immédiatement

INSERT INTO articles (user_id, contenu) VALUES
(3, 'Premier post sur Konekt ! Le projet PHP avance bien 🎉'),
(4, 'Bonjour à tous, ravie de rejoindre la plateforme.');
