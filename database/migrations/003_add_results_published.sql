-- Per-event-instance results publishing. When 0 the instance's results are
-- hidden from public surfaces (public results portal + live big-screen prize
-- winners). Safe to run once on the live database.

ALTER TABLE `event_instances`
    ADD COLUMN `results_published` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;
