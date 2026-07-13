-- =====================================================================
--  Campus Champions - Seed Data
--  Default super admin + a demo campus with an admin user.
--
--  Default credentials (CHANGE IMMEDIATELY AFTER FIRST LOGIN):
--    Super Admin  -> superadmin@campuschampions.local / Admin@123
--    Campus Admin -> admin@demoschool.local          / Admin@123
--  The password hash below is bcrypt for "Admin@123".
-- =====================================================================

-- Demo campus
INSERT INTO `institutions`
    (`name`, `address`, `contact_email`, `contact_phone`, `subscription_start_date`, `subscription_end_date`, `status`)
VALUES
    ('Demo School', '123 Education Ave', 'contact@demoschool.local', '9000000000', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active');

SET @campus_id = LAST_INSERT_ID();

-- Super admin (no campus)
INSERT INTO `users`
    (`email`, `password`, `full_name`, `role`, `campus_id`, `status`)
VALUES
    ('superadmin@campuschampions.local',
     '$2y$12$C.EWN9ElQVKyLkmDDxVPpOaNsf0ySyJgzAcZZRLNMoUQPBUKgZf9W',
     'Super Administrator', 'super_admin', NULL, 'active');

-- Campus admin for the demo campus
INSERT INTO `users`
    (`email`, `password`, `full_name`, `role`, `campus_id`, `status`)
VALUES
    ('admin@demoschool.local',
     '$2y$12$C.EWN9ElQVKyLkmDDxVPpOaNsf0ySyJgzAcZZRLNMoUQPBUKgZf9W',
     'Demo Campus Admin', 'campus_admin', @campus_id, 'active');

-- A default certificate template (global)
INSERT INTO `certificate_templates` (`name`, `body_html`, `campus_id`, `is_default`, `status`)
VALUES (
    'Default Certificate',
    '<div style="text-align:center;font-family:Georgia,serif;">'
    '<h1 style="color:#2563EB;">Certificate of Achievement</h1>'
    '<p>This is proudly presented to</p>'
    '<h2>{{contestant_name}}</h2>'
    '<p>for securing <strong>{{position}}</strong> place in</p>'
    '<h3>{{event_label}}</h3>'
    '<p>{{meet_title}} &mdash; {{issue_date}}</p>'
    '<p style="margin-top:40px;">Certificate No: {{certificate_number}}</p>'
    '</div>',
    NULL, 1, 'active'
);
