-- --------------------------------------------------------
-- Hôte :                        127.0.0.1
-- Version du serveur:           10.4.32-MariaDB - mariadb.org binary distribution
-- SE du serveur:                Win64
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Listage de la structure de la base pour techbase
CREATE DATABASE IF NOT EXISTS `techbase` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `techbase`;

-- Listage de la structure de la table techbase. fiches
CREATE TABLE IF NOT EXISTS `fiches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rubrique_id` int(11) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `etat` enum('À faire','En cours','Terminé') NOT NULL DEFAULT 'À faire',
  `description` text NOT NULL,
  `priorite` enum('Standard','Important','Critique','À surveiller') NOT NULL DEFAULT 'Standard',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rubrique` (`rubrique_id`),
  CONSTRAINT `fiches_ibfk_1` FOREIGN KEY (`rubrique_id`) REFERENCES `rubriques` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.fiches : ~11 rows (environ)
/*!40000 ALTER TABLE `fiches` DISABLE KEYS */;
INSERT INTO `fiches` (`id`, `rubrique_id`, `titre`, `etat`, `description`, `priorite`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Configuration du pare-feu Fortinet', 'Terminé', '## 1. Vue d’ensemble\r\n[formulaire de demande asso août 2023-1.pdf](/techbase/uploads/1760293949_68ebf43d61663.pdf)\r\n![IMG_0063.JPG](/techbase/uploads/1760293868_68ebf3ec8b42e.jpg)\r\n- Objectif : sécuriser réseau, NAT, VPN, filtrage apps/logs.\r\n- Interfaces : `wan1`, `internal`, `dmz`.\r\n- Accès admin : GUI (HTTPS) + CLI (SSH), limiter par IP, activer 2FA.\r\n- Modes : `NAT/Route` (par défaut), `Transparent` (bridge).\r\n\r\n## 2. Config basique CLI\r\n```\r\n# Interfaces\r\nconfig system interface\r\n  edit "wan1"\r\n    set ip 203.0.113.10/29\r\n    set allowaccess ping https ssh\r\n  next\r\n  edit "internal"\r\n    set ip 192.168.10.1/24\r\n    set allowaccess ping https\r\n  next\r\nend\r\n\r\n# Route par défaut\r\nconfig router static\r\n  edit 1\r\n    set gateway 203.0.113.1\r\n    set device "wan1"\r\n  next\r\nend\r\n\r\n# Policy LAN -> Internet\r\nconfig firewall policy\r\n  edit 1\r\n    set name "LAN-to-Internet"\r\n    set srcintf "internal"\r\n    set dstintf "wan1"\r\n    set srcaddr "all"\r\n    set dstaddr "all"\r\n    set action accept\r\n    set schedule "always"\r\n    set service "HTTP" "HTTPS" "DNS"\r\n    set nat enable\r\n  next\r\nend\r\n', 'Critique', '2025-10-11 20:32:17', '2025-10-20 20:55:19'),
	(2, 3, 'Mise à jour Windows Server 2022', 'En cours', 'Planification de la mise à jour des serveurs Windows Server 2019 vers 2022. Étapes de préparation et post-migration.', 'Important', '2025-10-11 20:32:17', '2025-10-11 20:37:12'),
	(3, 3, 'Installation d\'imprimantes réseau Ricoh', 'Terminé', 'Guide d\'installation des imprimantes Ricoh sur le réseau. Inclut les profils de pilotes et les adresses IP.', 'Standard', '2025-10-11 20:32:17', '2025-10-11 20:37:16'),
	(4, 1, 'Problème de connectivité réseau VLAN 10', 'À faire', '## 2.1 Serveurs Windows (physiques & virtuels) \r\n### 2.1.1 Inventaire des Serveurs Clés\r\n\r\nTous les serveurs sont situés dans le VLAN 10 (192.168.10.x).\r\n\r\n| Nom du Serveur | Adresse IP | Rôles Principaux | Statut (Physique/VM) |\r\n|----------------|------------|------------------|----------------------|\r\n| SRV-AD01 | 192.168.10.10 | Active Directory (AD), DNS, DHCP | VM |\r\n| SRV-AD02 | 192.168.10.11 | Active Directory (AD), DNS | VM |\r\n| SRV-FICHIER | 192.168.10.20 | Partage de Fichiers, Sauvegarde Fortinet | VM |\r\n| SRV-EXCH | 192.168.10.30 | Exchange Server (Messagerie) | VM |\r\n\r\n### 2.1.2 Documentation des GPO critiques\r\n\r\nLes GPO (Group Policy Objects) sont gérées via **SRV-AD01**.\r\n\r\n**GPO - User_Lockdown :** Applique le fond d\'écran standard, désactive l\'accès au Panneau de configuration pour les utilisateurs non-IT.  \r\n**GPO - Security_Patching :** Force l\'application des mises à jour Windows Update (via WSUS, non détaillé ici) sur tous les postes de travail chaque mercredi à 20h00.  \r\n**GPO - Shared_Drives :** Monte automatiquement les lecteurs réseau (U:, V:, P:) basés sur l\'appartenance au groupe AD.\r\n\r\n## 2.2 Messagerie Exchange / Outlook\r\n\r\nProcédures de gestion des comptes et de la solution anti-spam Mailinblack.\r\n\r\n**Contenu à documenter.**', 'Important', '2025-10-11 20:32:17', '2025-10-12 10:52:13'),
	(5, 5, 'Sauvegarde Veeam Exchange', 'Terminé', 'Configuration de la sauvegarde Veeam pour Exchange. Inclut les politiques de retention et les alertes.', 'Critique', '2025-10-11 20:32:17', '2025-10-11 20:37:29'),
	(6, 5, 'Création d\'un plan de reprise d\'activité', 'À faire', 'Élaboration d\'un plan de reprise d\'activité pour les services critiques. Inclure les RTO/RPO.', 'Critique', '2025-10-11 20:32:17', '2025-10-11 20:37:39'),
	(7, 3, 'Gestion des licences Office 365', 'En cours', '## 1. Vue d’ensemble\r\n- Objectif : attribuer, modifier et supprimer des licences Office 365 pour utilisateurs.\r\n- Types de licences : Microsoft 365 Business, E3, E5, etc.\r\n- Accès admin : portail Microsoft 365 ou PowerShell (`Connect-MsolService`).\r\n\r\n## 2. Vérifier les licences disponibles\r\n### Via portail\r\n- Admin Center → Facturation → Licences.\r\n- Voir nombre de licences disponibles et assignées.\r\n\r\n### Via PowerShell\r\n```\r\nConnect-MsolService\r\nGet-MsolAccountSku\r\n', 'Standard', '2025-10-11 20:32:17', '2025-10-12 13:04:28'),
	(8, 8, 'Diagnostic panne serveur ESXi', 'En cours', 'Procédure de diagnostic pour serveur ESXi en panne. Étapes de vérification hardware et logs.', 'Important', '2025-10-11 20:32:17', '2025-10-15 23:22:55'),
	(9, 5, 'Configuration du VPN AnyDesk pour télémaintenance', 'Terminé', 'Guide de configuration du VPN AnyDesk pour interventions distantes sur postes clients.', 'Important', '2025-10-11 20:32:17', '2025-10-11 22:35:47'),
	(10, 1, 'Inventaire des postes de travail', 'En cours', 'Liste des postes de travail avec specs, dates d\'achat, responsables. \r\n> À compléter avec les nouveaux arrivants.', 'Standard', '2025-10-11 20:32:17', '2025-10-15 23:22:58'),
	(11, 2, 'Les postes connectés au VLAN 10', 'À faire', '**Résumé du problème :**  \r\nLes postes connectés au **VLAN 10 (Réseau Utilisateurs)** rencontrent des pertes de connectivité intermittentes vers les ressources internes et Internet.  \r\nLes utilisateurs signalent des coupures de réseau aléatoires, notamment lors des connexions aux serveurs de fichiers et aux applications métiers.\r\n\r\n---\r\n\r\n#### Symptômes observés\r\n- Ping instable ou impossible vers la passerelle VLAN 10  \r\n- Déconnexions aléatoires des sessions RDP et SMB  \r\n- Aucun problème détecté sur les VLANs adjacents (20, 30)  \r\n- Certains postes récupèrent une **adresse IP APIPA (169.254.x.x)**  \r\n\r\n---\r\n\r\n#### Environnement concerné\r\n- **Switchs d’accès :** Cisco Catalyst 2960X  \r\n- **Switch cœur de réseau :** Cisco Catalyst 3850  \r\n- **Routeur principal :** Cisco ISR 4331  \r\n- **VLAN 10 :**  \r\n  - ID : 10  \r\n  - Nom : USERS  \r\n  - Sous-réseau : 192.168.10.0/24  \r\n  - Passerelle : 192.168.10.1  \r\n  - DHCP : Serveur Windows 2019 (192.168.1.10)  \r\n\r\n---\r\n\r\n#### Hypothèses possibles\r\n1. **Problème de configuration VLAN :** VLAN 10 non configuré ou mal propagé sur certains trunks  \r\n2. **Problème de trunking :** VLAN 10 manquant dans la liste `allowed VLANs`  \r\n3. **Problème de DHCP :** `ip helper-address` absent ou incorrect  \r\n4. **Problème matériel :** port ou câble défectueux  \r\n5. **Boucle réseau ou tempête de broadcast**\r\n\r\n---\r\n\r\n#### Étapes de diagnostic\r\n```\r\n# Vérification de la configuration VLAN\r\nshow vlan brief\r\nshow interfaces trunk\r\n\r\n# Test de connectivité inter-VLAN\r\nping 192.168.10.1\r\nping 192.168.10.10\r\n\r\n# Vérification du relais DHCP\r\nshow running-config interface vlan 10\r\n\r\n# Analyse des logs\r\nshow log | include DHCP\r\nshow log | include err-disable\r\n', 'Standard', '2025-10-12 09:53:02', '2025-10-12 10:47:49');
/*!40000 ALTER TABLE `fiches` ENABLE KEYS */;

-- Listage de la structure de la table techbase. fiche_tag
CREATE TABLE IF NOT EXISTS `fiche_tag` (
  `fiche_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`fiche_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `fiche_tag_ibfk_1` FOREIGN KEY (`fiche_id`) REFERENCES `fiches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fiche_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.fiche_tag : ~20 rows (environ)
/*!40000 ALTER TABLE `fiche_tag` DISABLE KEYS */;
INSERT INTO `fiche_tag` (`fiche_id`, `tag_id`) VALUES
	(1, 15),
	(1, 35),
	(1, 52),
	(3, 24),
	(3, 45),
	(8, 1),
	(8, 10),
	(8, 15),
	(8, 40),
	(8, 49),
	(8, 53),
	(10, 1),
	(10, 6),
	(10, 22),
	(10, 25),
	(10, 41),
	(11, 1),
	(11, 19),
	(11, 22),
	(11, 26);
/*!40000 ALTER TABLE `fiche_tag` ENABLE KEYS */;

-- Listage de la structure de la table techbase. groupe_tags
CREATE TABLE IF NOT EXISTS `groupe_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `ordre` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.groupe_tags : ~4 rows (environ)
/*!40000 ALTER TABLE `groupe_tags` DISABLE KEYS */;
INSERT INTO `groupe_tags` (`id`, `nom`, `code`, `ordre`) VALUES
	(1, 'Domaine technique', 'domaine_technique', 10),
	(2, 'Thèmes fonctionnels', 'themes_fonctionnels', 20),
	(3, 'Technologies', 'technologies', 30),
	(4, 'Type de contenu', 'type_contenu', 40);
/*!40000 ALTER TABLE `groupe_tags` ENABLE KEYS */;

-- Listage de la structure de la table techbase. medias
CREATE TABLE IF NOT EXISTS `medias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `taille` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `date_upload` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.medias : ~0 rows (environ)
/*!40000 ALTER TABLE `medias` DISABLE KEYS */;
/*!40000 ALTER TABLE `medias` ENABLE KEYS */;

-- Listage de la structure de la table techbase. rubriques
CREATE TABLE IF NOT EXISTS `rubriques` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `niveau` tinyint(4) NOT NULL DEFAULT 0,
  `ordre` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_ordre` (`ordre`),
  CONSTRAINT `rubriques_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `rubriques` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.rubriques : ~8 rows (environ)
/*!40000 ALTER TABLE `rubriques` DISABLE KEYS */;
INSERT INTO `rubriques` (`id`, `parent_id`, `nom`, `slug`, `niveau`, `ordre`) VALUES
	(1, NULL, 'Infrastructure', 'infrastructure', 0, 10),
	(2, NULL, 'Systèmes & Serveurs', 'systemes-serveurs', 0, 20),
	(3, NULL, 'Postes & Périphériques', 'postes-peripheriques', 0, 30),
	(4, NULL, 'Téléphonie', 'telephonie', 0, 40),
	(5, NULL, 'Sécurité', 'securite', 0, 50),
	(6, NULL, 'Support', 'support', 0, 60),
	(7, NULL, 'Administration', 'administration', 0, 70),
	(8, NULL, 'Communications', 'communications', 0, 65);
/*!40000 ALTER TABLE `rubriques` ENABLE KEYS */;

-- Listage de la structure de la table techbase. tags
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupe_id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `ordre` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_par_groupe` (`groupe_id`,`nom`),
  KEY `idx_groupe` (`groupe_id`),
  CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`groupe_id`) REFERENCES `groupe_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Listage des données de la table techbase.tags : ~62 rows (environ)
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` (`id`, `groupe_id`, `nom`, `slug`, `ordre`) VALUES
	(1, 1, 'Infrastructure', 'infrastructure', 10),
	(2, 1, 'Réseau', 'reseau', 20),
	(3, 1, 'Systèmes', 'systemes', 30),
	(4, 1, 'Serveurs', 'serveurs', 40),
	(5, 1, 'Postes de travail', 'postes-de-travail', 50),
	(6, 1, 'Périphériques', 'peripheriques', 60),
	(7, 1, 'Téléphonie', 'telephonie', 70),
	(8, 1, 'Sécurité', 'securite', 80),
	(9, 1, 'Audiovisuel', 'audiovisuel', 90),
	(10, 1, 'Administration', 'administration', 100),
	(11, 1, 'Support', 'support', 110),
	(12, 1, 'Checklist', 'checklist', 120),
	(13, 2, 'Schéma réseau', 'schema-reseau', 10),
	(14, 2, 'VLAN', 'vlan', 20),
	(15, 2, 'Pare-feu', 'pare-feu', 30),
	(16, 2, 'Virtualisation', 'virtualisation', 40),
	(17, 2, 'Stockage', 'stockage', 50),
	(18, 2, 'Sauvegarde', 'sauvegarde', 60),
	(19, 2, 'Active Directory', 'active-directory', 70),
	(20, 2, 'DNS', 'dns', 80),
	(21, 2, 'Messagerie', 'messagerie', 90),
	(22, 2, 'Base de données', 'base-de-donnees', 100),
	(23, 2, 'Télémaintenance', 'telemaintenance', 110),
	(24, 2, 'Impression', 'impression', 120),
	(25, 2, 'Inventaire', 'inventaire-theme', 130),
	(26, 2, 'Contrôle d’accès', 'controle-acces', 140),
	(27, 2, 'Vidéosurveillance', 'videosurveillance', 150),
	(28, 2, 'Procédure d’incident', 'procedure-incident', 160),
	(29, 2, 'RGPD', 'rgpd', 170),
	(30, 2, 'Formation', 'formation', 180),
	(31, 2, 'Contrat', 'contrat-theme', 190),
	(32, 2, 'Commande', 'commande', 200),
	(33, 2, 'Budget', 'budget', 210),
	(34, 2, 'Veille technologique', 'veille-technologique', 220),
	(35, 3, 'Fortinet', 'fortinet', 10),
	(36, 3, 'Veeam', 'veeam', 20),
	(37, 3, 'vSphere', 'vsphere', 30),
	(38, 3, 'Windows Server', 'windows-server', 40),
	(39, 3, 'Exchange', 'exchange', 50),
	(40, 3, 'Outlook', 'outlook', 60),
	(41, 3, 'Access', 'access', 70),
	(42, 3, 'SQL', 'sql', 80),
	(43, 3, 'Alcatel', 'alcatel', 90),
	(44, 3, 'VOIP', 'voip', 100),
	(45, 3, 'Ricoh', 'ricoh', 110),
	(46, 3, 'TeamViewer', 'teamviewer', 120),
	(47, 3, 'AnyDesk', 'anydesk', 130),
	(48, 3, 'Synology', 'synology', 140),
	(49, 3, 'Office 365', 'office-365', 150),
	(50, 3, 'ESXi', 'esxi', 160),
	(51, 4, 'Procédure', 'procedure', 10),
	(52, 4, 'Configuration', 'configuration', 20),
	(53, 4, 'Incident', 'incident', 30),
	(54, 4, 'Diagnostic', 'diagnostic', 40),
	(55, 4, 'Inventaire', 'inventaire-contenu', 50),
	(56, 4, 'Plan', 'plan', 60),
	(57, 4, 'Check-list', 'check-list', 70),
	(58, 4, 'Contact', 'contact', 80),
	(59, 4, 'Contrat', 'contrat-contenu', 90),
	(60, 4, 'Note interne', 'note-interne', 100),
	(61, 4, 'À valider', 'a-valider', 110),
	(62, 4, 'Obsolète', 'obsolete', 120);
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
