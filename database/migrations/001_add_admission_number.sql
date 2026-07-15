-- Migration: add admission_number to contestant_masters
-- Run this on an existing database (phpMyAdmin → SQL, or mysql CLI).
-- Safe to run once; skip if the column already exists.

ALTER TABLE `contestant_masters`
    ADD COLUMN `admission_number` VARCHAR(50) DEFAULT NULL AFTER `unique_number`;

ALTER TABLE `contestant_masters`
    ADD INDEX `idx_contestant_admission` (`admission_number`);
