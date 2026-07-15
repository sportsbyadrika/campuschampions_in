-- Font size (px) and colour for the certificate number and date overlays.

ALTER TABLE `certificate_templates`
    ADD COLUMN `number_font_size`  SMALLINT UNSIGNED NOT NULL DEFAULT 11        AFTER `number_left`,
    ADD COLUMN `number_font_color` VARCHAR(7)        NOT NULL DEFAULT '#333333' AFTER `number_font_size`,
    ADD COLUMN `date_font_size`     SMALLINT UNSIGNED NOT NULL DEFAULT 11        AFTER `date_left`,
    ADD COLUMN `date_font_color`    VARCHAR(7)        NOT NULL DEFAULT '#333333' AFTER `date_font_size`;
