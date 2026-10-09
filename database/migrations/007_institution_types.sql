-- Institution types master + link from institutions. Safe to run once.

CREATE TABLE IF NOT EXISTS `institution_types` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `institution_types` (`name`) VALUES
    ('Engineering'), ('Polytechnic'), ('ITI'), ('Arts and Science'), ('Management');

ALTER TABLE `institutions`
    ADD COLUMN `institution_type_id` INT UNSIGNED DEFAULT NULL AFTER `name`,
    ADD KEY `idx_institution_type` (`institution_type_id`),
    ADD CONSTRAINT `fk_institution_type` FOREIGN KEY (`institution_type_id`)
        REFERENCES `institution_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
