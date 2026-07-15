-- Live-screen settings for meets: logos, banner image and prize-winners
-- scroll speed. Safe to run once on the live database.

ALTER TABLE `meet_masters`
    ADD COLUMN `logo_path`             VARCHAR(255) DEFAULT NULL AFTER `details`,
    ADD COLUMN `banner_path`           VARCHAR(255) DEFAULT NULL AFTER `logo_path`,
    ADD COLUMN `institution_logo_path` VARCHAR(255) DEFAULT NULL AFTER `banner_path`,
    ADD COLUMN `winners_scroll_speed`  SMALLINT UNSIGNED NOT NULL DEFAULT 28 AFTER `institution_logo_path`;
