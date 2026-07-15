-- Certificate template layout configuration + per-meet certificate numbering.
-- All positions/margins are in millimetres. Safe to run once.

ALTER TABLE `certificate_templates`
    ADD COLUMN `orientation`   ENUM('portrait','landscape') NOT NULL DEFAULT 'portrait' AFTER `body_html`,
    ADD COLUMN `margin_top`    SMALLINT UNSIGNED NOT NULL DEFAULT 95  AFTER `orientation`,
    ADD COLUMN `margin_right`  SMALLINT UNSIGNED NOT NULL DEFAULT 22  AFTER `margin_top`,
    ADD COLUMN `margin_bottom` SMALLINT UNSIGNED NOT NULL DEFAULT 80  AFTER `margin_right`,
    ADD COLUMN `margin_left`   SMALLINT UNSIGNED NOT NULL DEFAULT 22  AFTER `margin_bottom`,
    ADD COLUMN `number_top`    SMALLINT UNSIGNED NOT NULL DEFAULT 12  AFTER `margin_left`,
    ADD COLUMN `number_left`   SMALLINT UNSIGNED NOT NULL DEFAULT 15  AFTER `number_top`,
    ADD COLUMN `date_top`      SMALLINT UNSIGNED NOT NULL DEFAULT 262 AFTER `number_left`,
    ADD COLUMN `date_left`     SMALLINT UNSIGNED NOT NULL DEFAULT 20  AFTER `date_top`,
    ADD COLUMN `number_prefix` VARCHAR(30) NOT NULL DEFAULT '' AFTER `date_left`,
    ADD COLUMN `number_suffix` VARCHAR(30) NOT NULL DEFAULT '' AFTER `number_prefix`,
    ADD COLUMN `number_next`   INT UNSIGNED NOT NULL DEFAULT 1  AFTER `number_suffix`;

-- Running per-meet certificate sequence (seeded from the template's number_next).
ALTER TABLE `meet_masters`
    ADD COLUMN `cert_next_seq` INT UNSIGNED DEFAULT NULL;
