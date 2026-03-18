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

-- ── PRECES tabulas paplašinājums (pasūtījumu sistēma) ────
ALTER TABLE `preces` ADD COLUMN IF NOT EXISTS `tips`         varchar(50)  DEFAULT 'druka' AFTER `kategorija`;
ALTER TABLE `preces` ADD COLUMN IF NOT EXISTS `foto_skaits`  int(3)       DEFAULT 1       AFTER `tips`;
ALTER TABLE `preces` ADD COLUMN IF NOT EXISTS `izmers`       varchar(100) DEFAULT ''      AFTER `foto_skaits`;
ALTER TABLE `preces` ADD COLUMN IF NOT EXISTS `orientacija`  varchar(20)  DEFAULT 'portrait' AFTER `izmers`;

-- Atjaunina esošos produktus
UPDATE `preces` SET tips='druka',    foto_skaits=1,  izmers='30×40 cm',   orientacija='portrait'  WHERE nosaukums LIKE '%Fotodruka%'    AND tips IS NULL;
UPDATE `preces` SET tips='canvas',   foto_skaits=1,  izmers='50×70 cm',   orientacija='portrait'  WHERE nosaukums LIKE '%Canvas%50%'     AND tips IS NULL;
UPDATE `preces` SET tips='canvas',   foto_skaits=1,  izmers='80×120 cm',  orientacija='portrait'  WHERE nosaukums LIKE '%Canvas%80%'     AND tips IS NULL;
UPDATE `preces` SET tips='panelis',  foto_skaits=1,  izmers='60×90 cm',   orientacija='portrait'  WHERE nosaukums LIKE '%panelis%'       AND tips IS NULL;
UPDATE `preces` SET tips='albums',   foto_skaits=20, izmers='30×30 cm',   orientacija='square'    WHERE nosaukums LIKE '%grāmata%'       AND tips IS NULL;
UPDATE `preces` SET tips='druka',    foto_skaits=1,  izmers='20×30 cm',   orientacija='portrait'  WHERE tips IS NULL;

-- Pievieno jaunus produktus (ja nav)
INSERT INTO `preces` (nosaukums, kategorija, tips, foto_skaits, izmers, orientacija, cena, apraksts, attels_url, bestseller, aktivs)
SELECT nosaukums, kategorija, tips, foto_skaits, izmers, orientacija, cena, apraksts, attels_url, bestseller, aktivs FROM (VALUES
  ROW('Fotodruka 20×30',    'Druka',   'druka',   1,  '20×30 cm',  'portrait', 29.00, 'Augstas kvalitātes druka uz matēta papīra.',              'https://images.unsplash.com/photo-1586348943529-beaae6c28db9?w=600&q=80', 0, 1),
  ROW('Fotodruka 30×40',    'Druka',   'druka',   1,  '30×40 cm',  'portrait', 39.00, 'Augstas kvalitātes druka uz matēta papīra.',              'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', 1, 1),
  ROW('Fotodruka 40×60',    'Druka',   'druka',   1,  '40×60 cm',  'portrait', 55.00, 'Liela formāta druka uz glossy papīra.',                   'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', 0, 1),
  ROW('Canvas 50×70',       'Canvas',  'canvas',  1,  '50×70 cm',  'portrait', 79.00, 'Audekla druka ar rāmi, gatava karināšanai.',              'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&q=80', 1, 1),
  ROW('Canvas 80×120',      'Canvas',  'canvas',  1,  '80×120 cm', 'portrait', 139.00,'Liela formāta audekla druka.',                            'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=600&q=80', 0, 1),
  ROW('Alumīnija panelis',  'Panelis', 'panelis', 1,  '60×90 cm',  'portrait', 149.00,'Spīdīgs alumīnija panelis, moderns dizains.',             'https://images.unsplash.com/photo-1551376347-075b0121a65b?w=600&q=80', 0, 1),
  ROW('Mini albums 10 foto','Albums',  'albums',  10, '15×15 cm',  'square',   69.00, 'Mīkstie vāki, 10 lapas (20 foto vietas), 15×15 cm.',     'https://images.unsplash.com/photo-1548267464-c0c4f39e3e0f?w=600&q=80', 0, 1),
  ROW('Albums 20 foto',     'Albums',  'albums',  20, '20×20 cm',  'square',   99.00, 'Cietie vāki, 20 lapas (40 foto vietas), 20×20 cm.',      'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80', 1, 1),
  ROW('Albums 30 foto',     'Albums',  'albums',  30, '25×25 cm',  'square',   129.00,'Premium cietie vāki, 30 lapas, 25×25 cm.',                'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', 0, 1),
  ROW('Lielais albums 50',  'Albums',  'albums',  50, '30×30 cm',  'square',   189.00,'Luksusa izdevums, 50 lapas, ādas vāki, 30×30 cm.',        'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=600&q=80', 0, 1)
) AS v(nosaukums, kategorija, tips, foto_skaits, izmers, orientacija, cena, apraksts, attels_url, bestseller, aktivs)
WHERE NOT EXISTS (SELECT 1 FROM `preces` WHERE `preces`.nosaukums = v.nosaukums);

-- pasutijumi tabula - pievieno foto_urls JSON kolonu
ALTER TABLE `pasutijumi` ADD COLUMN IF NOT EXISTS `foto_urls`  text DEFAULT NULL AFTER `foto_fails`;
