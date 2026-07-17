-- Multiple slideshow banners per meet + configurable slide interval, shown as
-- a third panel on the live big-screen dashboard.

CREATE TABLE IF NOT EXISTS `meet_banners` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `meet_id`    INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_meet_banner` (`meet_id`),
    CONSTRAINT `fk_meet_banner_meet` FOREIGN KEY (`meet_id`)
        REFERENCES `meet_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `meet_masters`
    ADD COLUMN `banner_interval` SMALLINT UNSIGNED NOT NULL DEFAULT 6;
