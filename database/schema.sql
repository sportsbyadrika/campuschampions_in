-- =====================================================================
--  Campus Champions - Database Schema
--  MySQL 5.7+ / 8.0  |  Engine: InnoDB  |  Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- institutions (campuses)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `institutions` (
    `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                    VARCHAR(150) NOT NULL,
    `address`                 VARCHAR(255) DEFAULT NULL,
    `contact_email`           VARCHAR(150) DEFAULT NULL,
    `contact_phone`           VARCHAR(30)  DEFAULT NULL,
    `subscription_start_date` DATE DEFAULT NULL,
    `subscription_end_date`   DATE DEFAULT NULL,
    `status`                  ENUM('active','expired','trial') NOT NULL DEFAULT 'trial',
    `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_institutions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`               VARCHAR(150) NOT NULL,
    `password`            VARCHAR(255) NOT NULL,
    `full_name`           VARCHAR(150) NOT NULL,
    `role`                ENUM('super_admin','campus_admin','event_user','campus_staff') NOT NULL,
    `campus_id`           INT UNSIGNED DEFAULT NULL,
    `status`              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `last_login`          TIMESTAMP NULL DEFAULT NULL,
    `reset_token`         VARCHAR(64) DEFAULT NULL,
    `reset_token_expiry`  DATETIME DEFAULT NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_campus` (`campus_id`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_reset_token` (`reset_token`),
    CONSTRAINT `fk_users_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- login_attempts (rate limiting)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`       VARCHAR(150) NOT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL,
    `successful`  TINYINT(1) NOT NULL DEFAULT 0,
    `attempted_at`TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_email_time` (`email`, `attempted_at`),
    KEY `idx_login_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Master data (per-campus)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `campus_id`   INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_courses_campus` (`campus_id`),
    CONSTRAINT `fk_courses_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `divisions` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `campus_id`   INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_divisions_campus` (`campus_id`),
    CONSTRAINT `fk_divisions_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `houses` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `color_code`  VARCHAR(20)  DEFAULT NULL,
    `campus_id`   INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_houses_campus` (`campus_id`),
    CONSTRAINT `fk_houses_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_category_groups` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `campus_id`   INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ccg_campus` (`campus_id`),
    CONSTRAINT `fk_ccg_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contestant_masters
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contestant_masters` (
    `id`                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `unique_number`            VARCHAR(50) NOT NULL,
    `admission_number`         VARCHAR(50) DEFAULT NULL,
    `name`                     VARCHAR(150) NOT NULL,
    `dob`                      DATE DEFAULT NULL,
    `gender`                   ENUM('M','F','O') DEFAULT NULL,
    `photo_path`               VARCHAR(255) DEFAULT NULL,
    `course_id`                INT UNSIGNED DEFAULT NULL,
    `division_id`              INT UNSIGNED DEFAULT NULL,
    `house_id`                 INT UNSIGNED DEFAULT NULL,
    `course_category_group_id` INT UNSIGNED DEFAULT NULL,
    `mobile`                   VARCHAR(30)  DEFAULT NULL,
    `email`                    VARCHAR(150) DEFAULT NULL,
    `guardian_name`            VARCHAR(150) DEFAULT NULL,
    `campus_id`                INT UNSIGNED NOT NULL,
    `status`                   ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contestant_unique_number` (`unique_number`),
    KEY `idx_contestant_campus` (`campus_id`),
    KEY `idx_contestant_course` (`course_id`),
    KEY `idx_contestant_division` (`division_id`),
    KEY `idx_contestant_house` (`house_id`),
    KEY `idx_contestant_ccg` (`course_category_group_id`),
    KEY `idx_contestant_name` (`name`),
    KEY `idx_contestant_admission` (`admission_number`),
    CONSTRAINT `fk_contestant_campus`   FOREIGN KEY (`campus_id`)   REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_contestant_course`   FOREIGN KEY (`course_id`)   REFERENCES `courses` (`id`)      ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_contestant_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_contestant_house`    FOREIGN KEY (`house_id`)    REFERENCES `houses` (`id`)       ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_contestant_ccg`      FOREIGN KEY (`course_category_group_id`) REFERENCES `course_category_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- meet_masters
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meet_masters` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200) NOT NULL,
    `start_date`  DATE DEFAULT NULL,
    `end_date`    DATE DEFAULT NULL,
    `location`    VARCHAR(200) DEFAULT NULL,
    `details`     TEXT DEFAULT NULL,
    `logo_path`             VARCHAR(255) DEFAULT NULL,
    `banner_path`           VARCHAR(255) DEFAULT NULL,
    `institution_logo_path` VARCHAR(255) DEFAULT NULL,
    `winners_scroll_speed`  SMALLINT UNSIGNED NOT NULL DEFAULT 28,
    `campus_id`   INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive','completed') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_meet_campus` (`campus_id`),
    CONSTRAINT `fk_meet_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- discipline_masters (per meet)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `discipline_masters` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `meet_id`     INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_discipline_meet` (`meet_id`),
    CONSTRAINT `fk_discipline_meet` FOREIGN KEY (`meet_id`)
        REFERENCES `meet_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- event_masters (per discipline)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_masters` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(150) NOT NULL,
    `discipline_id` INT UNSIGNED NOT NULL,
    `event_type`    ENUM('individual','group') NOT NULL DEFAULT 'individual',
    `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_discipline` (`discipline_id`),
    CONSTRAINT `fk_event_discipline` FOREIGN KEY (`discipline_id`)
        REFERENCES `discipline_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categories (per meet)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `meet_id`     INT UNSIGNED NOT NULL,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_categories_meet` (`meet_id`),
    CONSTRAINT `fk_categories_meet` FOREIGN KEY (`meet_id`)
        REFERENCES `meet_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- event_instances
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_instances` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_id`      INT UNSIGNED NOT NULL,
    `category_id`   INT UNSIGNED NOT NULL,
    `label`         VARCHAR(200) NOT NULL,
    `instance_date` DATE DEFAULT NULL,
    `instance_time` TIME DEFAULT NULL,
    `venue`         VARCHAR(200) DEFAULT NULL,
    `status`        ENUM('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_instance_event` (`event_id`),
    KEY `idx_instance_category` (`category_id`),
    KEY `idx_instance_date` (`instance_date`),
    CONSTRAINT `fk_instance_event`    FOREIGN KEY (`event_id`)    REFERENCES `event_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_instance_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- event_user_assignments  (which event users may enter results where)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `event_user_assignments` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED NOT NULL,
    `event_instance_id` INT UNSIGNED NOT NULL,
    `assigned_by`       INT UNSIGNED DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_assignment` (`user_id`, `event_instance_id`),
    KEY `idx_assignment_instance` (`event_instance_id`),
    CONSTRAINT `fk_assignment_user`     FOREIGN KEY (`user_id`)           REFERENCES `users` (`id`)           ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_assignment_instance` FOREIGN KEY (`event_instance_id`) REFERENCES `event_instances` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_assignment_by`       FOREIGN KEY (`assigned_by`)       REFERENCES `users` (`id`)           ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- contestant_registrations
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contestant_registrations` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contestant_id`     INT UNSIGNED NOT NULL,
    `event_instance_id` INT UNSIGNED NOT NULL,
    `registration_date` DATE DEFAULT NULL,
    `status`            ENUM('registered','confirmed','cancelled') NOT NULL DEFAULT 'registered',
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_registration` (`contestant_id`, `event_instance_id`),
    KEY `idx_reg_instance` (`event_instance_id`),
    CONSTRAINT `fk_reg_contestant` FOREIGN KEY (`contestant_id`)     REFERENCES `contestant_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reg_instance`   FOREIGN KEY (`event_instance_id`) REFERENCES `event_instances` (`id`)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- point_configs  (position -> points, per meet; defines how results score)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `point_configs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `meet_id`    INT UNSIGNED NOT NULL,
    `position`   ENUM('first','second','third','participant') NOT NULL,
    `points`     DECIMAL(6,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pointconfig` (`meet_id`, `position`),
    CONSTRAINT `fk_pointconfig_meet` FOREIGN KEY (`meet_id`)
        REFERENCES `meet_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- results
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `results` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_instance_id` INT UNSIGNED NOT NULL,
    `contestant_id`     INT UNSIGNED NOT NULL,
    `position`          ENUM('first','second','third','participant') NOT NULL DEFAULT 'participant',
    `points`            DECIMAL(6,2) NOT NULL DEFAULT 0,
    `remarks`           VARCHAR(255) DEFAULT NULL,
    `entered_by`        INT UNSIGNED DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_result` (`event_instance_id`, `contestant_id`),
    KEY `idx_result_contestant` (`contestant_id`),
    KEY `idx_result_position` (`position`),
    CONSTRAINT `fk_result_instance`   FOREIGN KEY (`event_instance_id`) REFERENCES `event_instances` (`id`)    ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_result_contestant` FOREIGN KEY (`contestant_id`)     REFERENCES `contestant_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_result_user`       FOREIGN KEY (`entered_by`)        REFERENCES `users` (`id`)              ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- certificate_templates
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificate_templates` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150) NOT NULL,
    `body_html`   MEDIUMTEXT NOT NULL,
    `campus_id`   INT UNSIGNED DEFAULT NULL,
    `is_default`  TINYINT(1) NOT NULL DEFAULT 0,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_certtpl_campus` (`campus_id`),
    CONSTRAINT `fk_certtpl_campus` FOREIGN KEY (`campus_id`)
        REFERENCES `institutions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- certificates
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_instance_id`  INT UNSIGNED NOT NULL,
    `contestant_id`      INT UNSIGNED NOT NULL,
    `certificate_number` VARCHAR(50) NOT NULL,
    `template_used`      INT UNSIGNED DEFAULT NULL,
    `issue_date`         DATE DEFAULT NULL,
    `file_path`          VARCHAR(255) DEFAULT NULL,
    `status`             ENUM('generated','issued','revoked') NOT NULL DEFAULT 'generated',
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_certificate_number` (`certificate_number`),
    KEY `idx_cert_instance` (`event_instance_id`),
    KEY `idx_cert_contestant` (`contestant_id`),
    CONSTRAINT `fk_cert_instance`   FOREIGN KEY (`event_instance_id`) REFERENCES `event_instances` (`id`)    ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cert_contestant` FOREIGN KEY (`contestant_id`)     REFERENCES `contestant_masters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cert_template`   FOREIGN KEY (`template_used`)     REFERENCES `certificate_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_logs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) DEFAULT NULL,
    `record_id`  INT UNSIGNED DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_table` (`table_name`, `record_id`),
    KEY `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
