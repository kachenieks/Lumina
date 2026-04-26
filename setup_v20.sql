-- LUMINA v20 — DB izmaiņas (palaid phpMyAdmin SQL cilnē)

-- 1. Viesa kontaktdati rezervācijās
ALTER TABLE `rezervacijas` ADD COLUMN IF NOT EXISTS `viesis_vards`   varchar(100) DEFAULT NULL AFTER `klienta_id`;
ALTER TABLE `rezervacijas` ADD COLUMN IF NOT EXISTS `viesis_epasts`  varchar(255) DEFAULT NULL AFTER `viesis_vards`;
ALTER TABLE `rezervacijas` ADD COLUMN IF NOT EXISTS `viesis_talrunis` varchar(30) DEFAULT NULL AFTER `viesis_epasts`;

-- 2. Galerija — piekļuves kods viesiem
ALTER TABLE `galerijas` ADD COLUMN IF NOT EXISTS `piekluves_kods` varchar(12) DEFAULT NULL AFTER `klienta_id`;
ALTER TABLE `galerijas` ADD COLUMN IF NOT EXISTS `viesis_epasts`  varchar(255) DEFAULT NULL AFTER `piekluves_kods`;

-- 3. Pasūtījumi — foto_urls kolonna (ja vēl nav)
ALTER TABLE `pasutijumi` ADD COLUMN IF NOT EXISTS `foto_urls` text DEFAULT NULL AFTER `foto_fails`;
ALTER TABLE `pasutijumi` ADD COLUMN IF NOT EXISTS `viesis_epasts` varchar(255) DEFAULT NULL AFTER `papildu_info`;
