-- Ajout de la table ocr_templates pour la configuration des modèles OCR
CREATE TABLE IF NOT EXISTS `ocr_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `document_type` VARCHAR(100), -- Ex: Facture, Contrat
    `zones` JSON NOT NULL, -- Stockera les coordonnées des zones d'extraction (JSON)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout de la table groups pour les groupes d'utilisateurs (services)
CREATE TABLE IF NOT EXISTS `groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout de la table de liaison user_groups (un utilisateur peut appartenir à plusieurs groupes)
CREATE TABLE IF NOT EXISTS `user_groups` (
    `user_id` INT NOT NULL,
    `group_id` INT NOT NULL,
    PRIMARY KEY (`user_id`, `group_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout de la colonne group_id à la table documents
-- Un document appartient à un seul groupe pour simplifier
ALTER TABLE `documents` ADD COLUMN `group_id` INT NULL;
ALTER TABLE `documents` ADD CONSTRAINT `fk_document_group` FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE SET NULL;

-- Mise à jour du rôle 'archivist' pour inclure le nouveau rôle 'contributor'
-- Ceci est nécessaire si vous avez déjà des utilisateurs et que vous voulez que 'archivist' puisse gérer 'contributor'
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'archivist', 'contributor', 'viewer') DEFAULT 'viewer';

-- Si vous voulez ajouter un groupe par défaut, par exemple "Service Général"
INSERT IGNORE INTO `groups` (`name`, `description`) VALUES ('Service Général', 'Groupe par défaut pour les documents et utilisateurs.');

-- Si vous voulez assigner l'admin par défaut au groupe "Service Général"
INSERT IGNORE INTO `user_groups` (`user_id`, `group_id`)
SELECT u.id, g.id FROM `users` u, `groups` g WHERE u.username = 'admin' AND g.name = 'Service Général';
