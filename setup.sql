-- Run this in phpMyAdmin to create missing tables

-- Pasūtījumi (veikala foto pasūtījumi)
CREATE TABLE IF NOT EXISTS `pasutijumi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `klienta_id` int(11) DEFAULT 0,
  `produkts` varchar(255) NOT NULL,
  `foto_fails` varchar(255) DEFAULT '',
  `crop_data` text DEFAULT '',
  `papildu_info` text DEFAULT '',
  `statuss` enum('jauns','apstiprinats','gatavs','nosutits') DEFAULT 'jauns',
  `izveidots` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pieejamība (admin kalendārs)
CREATE TABLE IF NOT EXISTS `pieejamiba` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datums` date NOT NULL,
  `slots` varchar(20) DEFAULT 'pilns_darbadiens',
  `laiks_no` time DEFAULT '09:00:00',
  `laiks_lidz` time DEFAULT '18:00:00',
  `pieejams` tinyint(1) DEFAULT 1,
  `piezime` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `datums` (`datums`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Galeriju foto (klienta sesijas foto)
CREATE TABLE IF NOT EXISTS `galeriju_foto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `galerijas_id` int(11) NOT NULL,
  `attels_url` varchar(500) NOT NULL,
  `nosaukums` varchar(255) DEFAULT '',
  `seciba` int(11) DEFAULT 0,
  `pievienots` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `galerijas_id` (`galerijas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
