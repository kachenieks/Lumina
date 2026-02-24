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
