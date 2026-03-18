-- ═══════════════════════════════════════════════════════════════
-- LUMINA — DB Setup v2  (palaid phpMyAdmin → SQL cilne)
-- Droši palaizt atkārtoti — neizdzēsīs datus!
-- ═══════════════════════════════════════════════════════════════

-- ── 1. PASŪTĪJUMI ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pasutijumi` (
  `id`          int(11)       NOT NULL AUTO_INCREMENT,
  `klienta_id`  int(11)       DEFAULT 0,
  `produkts`    varchar(255)  NOT NULL DEFAULT '',
  `foto_fails`  varchar(255)  DEFAULT '',
  `crop_data`   text          DEFAULT NULL,
  `papildu_info`text          DEFAULT NULL,
  `statuss`     varchar(30)   DEFAULT 'jauns',
  `izveidots`   datetime      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ja tabula jau eksistē bet trūkst kolonnas — pievieno:
-- (Palaid katru atsevišķi, ignorē kļūdu "Duplicate column name")
ALTER TABLE `pasutijumi` ADD COLUMN `produkts`    varchar(255) NOT NULL DEFAULT '' AFTER `klienta_id`;
ALTER TABLE `pasutijumi` ADD COLUMN `foto_fails`   varchar(255)         DEFAULT '' AFTER `produkts`;
ALTER TABLE `pasutijumi` ADD COLUMN `crop_data`    text                 DEFAULT NULL AFTER `foto_fails`;
ALTER TABLE `pasutijumi` ADD COLUMN `papildu_info` text                 DEFAULT NULL AFTER `crop_data`;
ALTER TABLE `pasutijumi` ADD COLUMN `statuss`      varchar(30)          DEFAULT 'jauns' AFTER `papildu_info`;
ALTER TABLE `pasutijumi` ADD COLUMN `izveidots`    datetime             DEFAULT CURRENT_TIMESTAMP AFTER `statuss`;

-- ── 2. PIEEJAMĪBA (KALENDĀRS) ────────────────────────────────
CREATE TABLE IF NOT EXISTS `pieejamiba` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `datums`     date         NOT NULL,
  `laiks_no`   time         DEFAULT '09:00:00',
  `laiks_lidz` time         DEFAULT '18:00:00',
  `pieejams`   tinyint(1)   DEFAULT 1,
  `piezime`    varchar(255) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `datums` (`datums`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. GALERIJU FOTO ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `galeriju_foto` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `galerijas_id` int(11)      NOT NULL,
  `attels_url`   varchar(500) NOT NULL,
  `nosaukums`    varchar(255) DEFAULT '',
  `seciba`       int(11)      DEFAULT 0,
  `pievienots`   datetime     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `galerijas_id` (`galerijas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. KLIENTI — pievieno izveidots kolonnu ja nav ──────────
ALTER TABLE `klienti` ADD COLUMN `izveidots` datetime DEFAULT CURRENT_TIMESTAMP;

-- ── 5. PRECES (veikala produkti) ──────────────────────────
CREATE TABLE IF NOT EXISTS `preces` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `nosaukums`   varchar(255) NOT NULL,
  `apraksts`    text         DEFAULT NULL,
  `cena`        decimal(8,2) NOT NULL DEFAULT '0.00',
  `kategorija`  varchar(100) DEFAULT '',
  `attels_url`  varchar(500) DEFAULT '',
  `bestseller`  tinyint(1)   DEFAULT 0,
  `aktivs`      tinyint(1)   DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pievieno produktus ja tabulā nav neviena
INSERT INTO `preces` (`nosaukums`, `apraksts`, `cena`, `kategorija`, `attels_url`, `bestseller`, `aktivs`)
SELECT * FROM (VALUES
  ROW('Personalizēts portrets', 'Drukāts portrets uz foto papīra. Augsta izšķirtspēja, ilgstoša krāsu noturība.', 65.00, 'Druka', 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=600&q=80', 1, 1),
  ROW('Kāzu fotodruka 30×40', 'Kāzu mirklis uz luksusa foto papīra. Piegādājam rāmī.', 49.00, 'Druka', 'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', 0, 1),
  ROW('Canvas 50×70', 'Fotoattēls uz audekla ar rāmi, gatavs karināšanai.', 79.00, 'Audekls', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&q=80', 1, 1),
  ROW('Canvas 80×120', 'Liela formāta audekla druka — iespaidīga sienas dekorācija.', 139.00, 'Audekls', 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=600&q=80', 0, 1),
  ROW('Sienas panelis 60×90', 'Alumīnija panelis ar spīdīgu virsmu. Moderns, bez rāmja.', 149.00, 'Metāls', 'https://images.unsplash.com/photo-1551376347-075b0121a65b?w=600&q=80', 0, 1),
  ROW('Fotograāmata 30×30', 'Cietie vāki, 30 lapas iekļautas. Ideāla dāvana vai kāzu albums.', 129.00, 'Albums', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', 0, 1)
) AS new_rows (nosaukums, apraksts, cena, kategorija, attels_url, bestseller, aktivs)
WHERE NOT EXISTS (SELECT 1 FROM `preces` LIMIT 1);


-- ── 5. PRECES — sākotnējie produkti ──────────────────────
CREATE TABLE IF NOT EXISTS `preces` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `nosaukums`   varchar(255) NOT NULL DEFAULT '',
  `kategorija`  varchar(100) DEFAULT '',
  `cena`        decimal(10,2) DEFAULT 0.00,
  `apraksts`    text         DEFAULT NULL,
  `attels_url`  varchar(500) DEFAULT '',
  `bestseller`  tinyint(1)   DEFAULT 0,
  `aktivs`      tinyint(1)   DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pievieno sākotnējos produktus (ja tabula tukša)
INSERT INTO `preces` (`nosaukums`,`kategorija`,`cena`,`apraksts`,`attels_url`,`bestseller`,`aktivs`)
SELECT * FROM (
  SELECT 'Personalizēts portrets','Druka',65.00,'Augstas izšķirtspējas fotodruka uz matēta papīra. Ietvars nav iekļauts.','https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=600&q=80',1,1
  UNION ALL SELECT 'Kāzu fotodruka','Druka',49.00,'Eleganta kāzu fotodruka. Piemērota dāvanai vai interjera dekorēšanai.','https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80',0,1
  UNION ALL SELECT 'Ģimenes portrets','Druka',55.00,'Siltā ģimenes foto izdruka. Ideāla mājas interjeram.','https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=600&q=80',0,1
  UNION ALL SELECT 'Dabas ainava','Druka',45.00,'Latvijas dabas skaistums — augstas kvalitātes fotodruka.','https://images.unsplash.com/photo-1504701954957-2010ec3bcec1?w=600&q=80',0,1
) tmp
WHERE NOT EXISTS (SELECT 1 FROM `preces` LIMIT 1);

-- ── 6. KLIENTI — pievieno kolonnas ja trūkst ────────────
ALTER TABLE `klienti` ADD COLUMN `izveidots` datetime DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `klienti` ADD COLUMN `kopeja_summa` decimal(10,2) DEFAULT 0.00;
