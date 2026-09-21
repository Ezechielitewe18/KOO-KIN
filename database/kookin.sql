-- ============================================================
-- KOO-KIN - Cuisine Congolaise Authentique
-- Base de données MySQL
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `kookin` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kookin`;

-- ------------------------------------------------------------
-- Table : administrateurs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(190) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin','admin') NOT NULL DEFAULT 'admin',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : categories (menu)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `icone` VARCHAR(50) NULL,
  `ordre` INT NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : plats
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `plats`;
CREATE TABLE `plats` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `categorie_id` INT UNSIGNED NOT NULL,
  `nom` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `prix` INT UNSIGNED NOT NULL,
  `photo` VARCHAR(255) NULL,
  `disponible` TINYINT(1) NOT NULL DEFAULT 1,
  `populaire` TINYINT(1) NOT NULL DEFAULT 0,
  `vegan` TINYINT(1) NOT NULL DEFAULT 0,
  `épicé` TINYINT(1) NOT NULL DEFAULT 0,
  `ordre` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plats_slug` (`slug`),
  KEY `fk_plats_categorie` (`categorie_id`),
  CONSTRAINT `fk_plats_categorie` FOREIGN KEY (`categorie_id`)
    REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : menu du jour
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `menus_jour`;
CREATE TABLE `menus_jour` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(150) NOT NULL DEFAULT 'Menu du jour',
  `plats_text` TEXT NULL,
  `accompagnements_text` TEXT NULL,
  `prix` INT UNSIGNED NULL,
  `note` VARCHAR(255) NULL,
  `date_debut` DATE NULL,
  `date_fin` DATE NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : clients
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(190) NULL,
  `adresse` VARCHAR(255) NULL,
  `commune` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clients_telephone` (`telephone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : commandes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `commandes`;
CREATE TABLE `commandes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `client_id` INT UNSIGNED NULL,
  `client_nom` VARCHAR(150) NOT NULL,
  `client_telephone` VARCHAR(30) NOT NULL,
  `client_adresse` VARCHAR(255) NULL,
  `commune` VARCHAR(100) NULL,
  `type` ENUM('sur_place','emporter','livraison') NOT NULL DEFAULT 'livraison',
  `frais_livraison` INT UNSIGNED NOT NULL DEFAULT 0,
  `sous_total` INT UNSIGNED NOT NULL DEFAULT 0,
  `total` INT UNSIGNED NOT NULL DEFAULT 0,
  `paiement` ENUM('sur_place','livraison','mobile') NOT NULL DEFAULT 'livraison',
  `statut` ENUM('nouvelle','confirmee','en_preparation','prete','en_livraison','livree','annulee') NOT NULL DEFAULT 'nouvelle',
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_commandes_code` (`code`),
  KEY `idx_commandes_client` (`client_id`),
  KEY `idx_commandes_statut` (`statut`),
  KEY `idx_commandes_created` (`created_at`),
  CONSTRAINT `fk_commandes_client` FOREIGN KEY (`client_id`)
    REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : détails des commandes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `commande_details`;
CREATE TABLE `commande_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commande_id` INT UNSIGNED NOT NULL,
  `plat_id` INT UNSIGNED NULL,
  `plat_nom` VARCHAR(150) NOT NULL,
  `prix_unitaire` INT UNSIGNED NOT NULL,
  `quantite` INT UNSIGNED NOT NULL DEFAULT 1,
  `total` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_details_commande` (`commande_id`),
  KEY `fk_details_plat` (`plat_id`),
  CONSTRAINT `fk_details_commande` FOREIGN KEY (`commande_id`)
    REFERENCES `commandes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_details_plat` FOREIGN KEY (`plat_id`)
    REFERENCES `plats` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : zones de livraison
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `livraisons`;
CREATE TABLE `livraisons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `commune` VARCHAR(100) NOT NULL,
  `zone` VARCHAR(100) NULL,
  `tarif` INT UNSIGNED NOT NULL DEFAULT 6000,
  `delai` VARCHAR(100) NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : réservations de table
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `nb_personnes` INT UNSIGNED NOT NULL DEFAULT 1,
  `date_reservation` DATE NOT NULL,
  `heure_reservation` TIME NOT NULL,
  `message` TEXT NULL,
  `statut` ENUM('en_attente','confirmee','annulee','terminee') NOT NULL DEFAULT 'en_attente',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservations_date` (`date_reservation`),
  KEY `idx_reservations_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : promotions
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plat_id` INT UNSIGNED NULL,
  `nom` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `prix_normal` INT UNSIGNED NOT NULL,
  `prix_promo` INT UNSIGNED NOT NULL,
  `photo` VARCHAR(255) NULL,
  `date_debut` DATE NULL,
  `date_fin` DATE NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_promotions_plat` (`plat_id`),
  CONSTRAINT `fk_promotions_plat` FOREIGN KEY (`plat_id`)
    REFERENCES `plats` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : galerie
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `galerie`;
CREATE TABLE `galerie` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(150) NULL,
  `image` VARCHAR(255) NOT NULL,
  `section` ENUM('plats','restaurant','preparation','evenements','traiteur') NOT NULL DEFAULT 'plats',
  `ordre` INT NOT NULL DEFAULT 0,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_galerie_section` (`section`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : demandes traiteur
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `demandes_traiteur`;
CREATE TABLE `demandes_traiteur` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `type_evenement` VARCHAR(100) NULL,
  `date_evenement` DATE NULL,
  `nb_personnes` INT UNSIGNED NULL,
  `budget` VARCHAR(50) NULL,
  `message` TEXT NULL,
  `statut` ENUM('nouvelle','contacte','traitee','annulee') NOT NULL DEFAULT 'nouvelle',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : messages (contact + chatbox)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NULL,
  `telephone` VARCHAR(30) NULL,
  `sujet` VARCHAR(150) NULL,
  `message` TEXT NOT NULL,
  `origine` ENUM('contact','chatbox') NOT NULL DEFAULT 'contact',
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_lu` (`lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : horaires
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `horaires`;
CREATE TABLE `horaires` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jour` VARCHAR(20) NOT NULL,
  `libelle` VARCHAR(50) NULL,
  `ouverture` TIME NULL,
  `fermeture` TIME NULL,
  `ferme` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : paramètres généraux du site
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `parametres_site`;
CREATE TABLE `parametres_site` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cle` VARCHAR(100) NOT NULL,
  `valeur` TEXT NULL,
  `description` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parametres_cle` (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES INITIALES (à adapter par le propriétaire)
-- ============================================================

-- Compte administrateur : admin / admin123
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'contact@koo-kin.cd', '$2y$10$wZSRgsY5cJcmd/.1m0fo3e3lkdF8ZVdZuEWeL/7qSZRdB1mfUxb0W', 'superadmin');

-- Catégories
INSERT INTO `categories` (`nom`, `slug`, `description`, `icone`, `ordre`) VALUES
('Plats',            'plats',            'Nos plats principaux',          '🍛', 1),
('Accompagnements',  'accompagnements',  'Le bon accompagnement du plat', '🍚', 2),
('Grillades',        'grillades',        'Grillades au feu de bois',      '🍢', 3),
('Boissons',         'boissons',         'Boissons fraîches et locales',  '🥤', 4),
('Desserts',         'desserts',         'Pour finir en douceur',         '🍌', 5),
('Menus spéciaux',   'menus-speciaux',   'Nos formules complètes',        '🌟', 6);

-- Plats
INSERT INTO `plats` (`categorie_id`, `nom`, `slug`, `description`, `prix`, `photo`, `disponible`, `populaire`, `vegan`, `épicé`, `ordre`) VALUES
(1, 'Poulet à la sauce', 'poulet-a-la-sauce', 'Poulet mijoté dans notre sauce maison, servi avec l''accompagnement de votre choix.', 10000, 'plats/poulet-a-la-sauce.jpg', 1, 1, 0, 0, 1),
(1, 'Côtis fumés', 'cotis-fumes', 'Côtis de porc fumés au feu de bois, une spécialité de chez nous.', 10000, 'plats/cotis-fumes.jpg', 1, 1, 0, 0, 2),
(1, 'Malua grillé', 'malua-grille', 'Poisson malua grillé, mariné aux herbes et épices locales.', 10000, 'plats/malua-grille.jpg', 1, 1, 0, 1, 3),
(1, 'Haricots', 'haricots', 'Haricots mijotés à la tomate et aux condiments, un grand classique.', 10000, 'plats/haricots.jpg', 1, 0, 1, 0, 4),
(2, 'Riz', 'riz', 'Riz blanc parfumé, cuit à la vapeur.', 10000, 'plats/riz.jpg', 1, 0, 1, 0, 1),
(2, 'Fufu', 'fufu', 'Fufu de manioc, préparé traditionnellement.', 10000, 'plats/fufu.jpg', 1, 0, 1, 0, 2),
(2, 'Banane plantain', 'banane-plantain', 'Banane plantain mûre, frite ou grillée.', 10000, 'plats/banane-plantain.jpg', 1, 0, 1, 0, 3),
(2, 'Kwanga', 'kwanga', 'Pain de manioc fermenté, une spécialité authentique.', 10000, 'plats/kwanga.jpg', 1, 0, 1, 0, 4),
(3, 'Poulet braisé', 'poulet-braise', 'Poulet entier braisé au feu de bois, tendre et savoureux.', 10000, 'plats/poulet-braise.jpg', 1, 1, 0, 0, 1),
(3, 'Boeuf braisé', 'boeuf-braise', 'Brochettes de boeuf marinées et braisées.', 10000, 'plats/boeuf-braise.jpg', 1, 0, 0, 0, 2),
(4, 'Coca-Cola', 'coca-cola', 'Coca-Cola bien frais, bouteille 50 cl.', 3000, 'boissons/coca-cola.jpg', 1, 0, 1, 0, 1),
(4, 'Fanta', 'fanta', 'Fanta orange bien frais, bouteille 50 cl.', 3000, 'boissons/fanta.jpg', 1, 0, 1, 0, 2),
(4, 'Sprite', 'sprite', 'Sprite citron-citron vert bien frais, bouteille 50 cl.', 3000, 'boissons/sprite.jpg', 1, 0, 1, 0, 3),
(4, 'Vitalo Orange', 'vitalo-orange', 'Boisson Vitalo orange, 50 cl.', 3000, 'boissons/vitalo.jpg', 1, 0, 1, 0, 4),
(4, 'Malta Guinness', 'malta-guinness', 'Malta Guinness, boisson maltée sans alcool.', 3000, 'boissons/malta.jpg', 1, 0, 1, 0, 5),
(4, 'Jus de gingembre', 'jus-de-gingembre', 'Jus de gingembre frais, fait maison.', 3000, 'plats/jus-gingembre.jpg', 1, 0, 1, 0, 6),
(4, 'Eau minérale 1,5L', 'eau-minerale', 'Eau minérale fraîche.', 3000, 'plats/eau.jpg', 1, 0, 1, 0, 7),
(5, 'Crème de yaourt', 'creme-yaourt', 'Crème de yaourt sucrée.', 10000, 'plats/creme-yaourt.jpg', 1, 0, 0, 0, 1),
(6, 'Menu Kintambo', 'menu-kintambo', 'Poulet à la sauce + accompagnement + boisson.', 10000, 'plats/menu-kintambo.jpg', 1, 1, 0, 0, 1);

-- Menu du jour
INSERT INTO `menus_jour` (`titre`, `plats_text`, `accompagnements_text`, `prix`, `note`, `date_debut`, `date_fin`, `actif`) VALUES
('Menu du jour', 'Matembelé\nHaricots\nPoulet à la sauce\nCôtis fumés\nMalua grillé', 'Riz\nFufu\nBanane plantain\nKwanga', 10000, 'Livraison à partir de 6 000 CDF. Nous livrons à partir de 11h.', NULL, NULL, 1);

-- Zones de livraison
INSERT INTO `livraisons` (`commune`, `zone`, `tarif`, `delai`) VALUES
('Kintambo',   'Centre',        6000,  '30 min'),
('Kintambo',   'Vélodrome',     6000,  '30 min'),
('Gombe',      'Toute commune', 6000,  '45 min'),
('Ngaliema',   'Toute commune', 6000, '50 min'),
('Kinshasa',   'Autres communes', 6000, '1h');

-- Réservations
INSERT INTO `reservations` (`nom`, `telephone`, `nb_personnes`, `date_reservation`, `heure_reservation`, `message`, `statut`) VALUES
('Amina Kalonji', '+243 8xx xxx xxx', 4, '2026-09-26', '19:30:00', 'Table près de la fenêtre', 'en_attente');

-- Promotions
INSERT INTO `promotions` (`plat_id`, `nom`, `description`, `prix_normal`, `prix_promo`, `date_debut`, `date_fin`, `actif`) VALUES
(3, 'Malua grillé', 'Offre du moment : poisson malua grillé à prix réduit, du lundi au vendredi.', 15000, 12000, '2026-09-01', '2026-10-01', 1);

-- Horaires
INSERT INTO `horaires` (`jour`, `libelle`, `ouverture`, `fermeture`, `ferme`) VALUES
('Lundi',     'Lundi',    '08:00:00', '22:00:00', 0),
('Mardi',     'Mardi',    '08:00:00', '22:00:00', 0),
('Mercredi',  'Mercredi', '08:00:00', '22:00:00', 0),
('Jeudi',     'Jeudi',    '08:00:00', '22:00:00', 0),
('Vendredi',  'Vendredi', '08:00:00', '22:00:00', 0),
('Samedi',    'Samedi',   '08:00:00', '23:00:00', 0),
('Dimanche',  'Dimanche', '08:00:00', '23:00:00', 0);

-- Paramètres généraux
INSERT INTO `parametres_site` (`cle`, `valeur`, `description`) VALUES
('site_nom',           'KOO-KIN', 'Nom du restaurant'),
('site_slogan',        'Cuisine Congolaise Authentique', 'Slogan'),
('site_description',   'Saveurs de chez nous', 'Description courte'),
('adresse',            '38, Avenue Bandundu, Q/Vélodrome, C/Kintambo — Kinshasa', 'Adresse complète'),
('repere',             'Croisement des avenues Komoriko et Bandundu', 'Repère de localisation'),
('telephone',          '+243 994 266 536', 'Téléphone'),
('whatsapp',           '243994266536', 'Numéro WhatsApp (format international sans +)'),
('facebook',           'https://facebook.com/koo-kin', 'Lien Facebook'),
('instagram',          'https://instagram.com/koo-kin', 'Lien Instagram'),
('tiktok',             'https://tiktok.com/@koo-kin', 'Lien TikTok'),
('email',              'contact@koo-kin.cd', 'Email de contact'),
('livraison_min',      '6000', 'Frais de livraison minimum'),
('livraison_texte',    'Livraison à Kinshasa. À partir de 6 000 CDF, selon le trajet et la commune. Nous livrons à partir de 11h.', 'Texte livraison'),
('horaires_texte',     'Lundi — Dimanche', 'Affichage horaires'),
('histoire',           'KOO-KIN propose une cuisine congolaise authentique, avec le souci des saveurs de chez nous.', 'Texte histoire'),
('hero_titre',         'KOO-KIN', 'Titre hero'),
('hero_sous_titre',    'Découvrez les saveurs authentiques de chez nous.', 'Sous-titre hero'),
('traiteur_texte',     'KOO-KIN vous accompagne également pour vos événements avec un service traiteur adapté à vos besoins.', 'Texte traiteur'),
('footer_texte',       'Cuisine Congolaise Authentique — Saveurs de chez nous.', 'Texte footer'),
('map_embed',          '', 'Code d\'intégration Google Maps (optionnel)'),
('map_lien',           'https://maps.google.com/?q=38+Avenue+Bandundu+Kintambo+Kinshasa', 'Lien itinéraire'),
('devise',             'CDF', 'Devise affichée');

SET FOREIGN_KEY_CHECKS = 1;