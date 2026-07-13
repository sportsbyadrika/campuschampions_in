# Campus Champions

A SaaS **School Event Management Platform** for managing school arts (onstage/offstage)
and sports competitions — meets, disciplines, events, contestants, results, certificates
and public result publishing.

Built with **PHP 8 (custom Simple MVC)** + **MySQL**, styled with **Tailwind CSS**
(white/blue theme). Designed to run on **shared hosting** with all dependencies
vendored (no Composer required on the server).

---

## Roles

| Role | Capabilities |
|------|--------------|
| **Super Admin** | Institutions, subscriptions, all data, system reports, audit logs |
| **Campus Admin** | Users, masters, contestants, meets — scoped to their campus |
| **Event User** | Result entry for assigned events, registrations, certificates |
| **Campus Staff** | View-only access, print reports, CSV export |
| **Public** | View published results at `/public-results` (no login) |

---

## Requirements

- PHP **8.0+** with extensions: `pdo_mysql`, `mbstring`, `gd`, `dom`, `zip`, `openssl`, `fileinfo`
- MySQL **5.7+** / MariaDB **10.3+**
- Apache with `mod_rewrite` (for clean URLs) — or Nginx with an equivalent rewrite

## Installation

1. **Upload / clone** this repository to your host.
2. **Point your document root at `/public`** if possible. If you cannot change the
   document root, the included root `.htaccess` transparently forwards requests into
   `/public`.
3. **Create the database** and import the schema + seed:
   ```bash
   mysql -u USER -p YOURDB < database/schema.sql
   mysql -u USER -p YOURDB < database/seed.sql
   ```
4. **Configure the environment**:
   ```bash
   cp .env.example .env
   # then edit .env with your DB credentials, APP_URL and SMTP settings
   ```
5. **Ensure writable dirs**: `storage/logs`, `storage/cache`,
   `public/assets/uploads/*` (chmod 775 or as your host requires).
6. Visit your site and **log in** (see default credentials below), then
   **change the password immediately**.

### Default credentials (from `database/seed.sql`)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `superadmin@campuschampions.local` | `Admin@123` |
| Campus Admin | `admin@demoschool.local` | `Admin@123` |

> ⚠️ Change these immediately in production.

---

## Project structure

```
campuschampions_in/
├── public/                 # Web root (front controller + assets)
│   ├── index.php           # Front controller
│   ├── .htaccess           # Clean-URL rewrites
│   └── assets/             # css, js, uploads
├── app/
│   ├── Core/               # Framework (Router, Database, Auth, View, ...)
│   ├── Controllers/
│   ├── Models/
│   ├── views/
│   └── helpers/functions.php
├── config/
│   ├── config.php          # Env loader + config
│   └── routes.php          # Route definitions
├── database/
│   ├── schema.sql
│   └── seed.sql
├── storage/                # logs, cache (writable)
├── vendor/                 # Composer deps (committed for shared hosting)
└── .env.example
```

---

## Security features

- Password hashing (`password_hash`), prepared statements everywhere (no SQL injection).
- CSRF tokens on all state-changing requests.
- XSS prevention via `e()` (htmlspecialchars) on all output.
- Role-based access control on every route (middleware).
- Rate limiting on login (5 attempts / 15-minute lockout).
- Password reset tokens with 1-hour expiry, no user enumeration.
- Secure, HTTP-only, SameSite session cookies with 30-minute idle timeout.
- Audit logging of critical actions.

---

## Build status (phased delivery)

- [x] **Phase 1 — Foundation**: MVC core, routing, DB schema, auth (login/logout/
      password reset/change), RBAC, rate limiting, layout + navigation, dashboard,
      profile, error pages, CSV + audit infrastructure.
- [x] **Phase 2 — Master data**: reusable CRUD engine (modal add/edit, delete
      confirmation, real-time-ready search, dropdown filters, per-page pagination,
      CSV export, audit) powering courses, divisions, houses, course category groups
      and users (with password hashing + role/campus rules).
- [x] **Phase 3 — Institutions + Contestants**: super-admin institution management
      (subscription periods); full contestant CRUD with FK dropdowns, validated photo
      upload (type/size/dimension checks), and a separate bulk-upload page
      (CSV template → validated preview → import).
- [x] **Phase 4 — Meets & event hierarchy**: meet CRUD plus a per-meet setup hub
      (tabs for points, disciplines, categories, events, event instances) with
      ownership-checked AJAX CRUD, and contestant registration management per event
      instance (register/confirm/cancel/remove).
- [x] **Phase 5 — Results & standings**: result-entry grid per event instance
      (position → auto-filled points with per-row override, remarks, transactional
      save), event-user assignment enforcement (event users only enter results for
      assigned instances), per-instance results CSV, and championship standings
      (house points with bars + individual leaderboard with medal counts, exportable).
- [x] **Phase 6 — Certificates**: certificate template CRUD (HTML body with
      `{{placeholders}}`), and a generation page per event instance that selects a
      template + contestants (with results), renders XSS-safe HTML, and produces
      downloadable A4 landscape PDFs via Dompdf, tracked in the certificates table
      with unique certificate numbers.
- [x] **Phase 7 — Public page, reports & audit**: public results page (no login)
      with search (contestant / unique # / event), meet/category/position filters,
      pagination, print styles, and file-based caching; super-admin system reports
      (platform totals, per-institution breakdown, CSV exports) and an audit-log
      viewer with filters + CSV export.

---

## Notes on the original specification

A few gaps in the brief were resolved during Phase 1 (see the assistant's review):

- Added an **`event_user_assignments`** table so "result entry for *assigned* events"
  is enforceable.
- Added a **`point_configs`** table (position → points, per meet) since `results.points`
  had no source of truth; enables **championship standings**.
- Added **`certificate_templates`** (referenced by `certificates.template_used`).
- Added **`login_attempts`** for rate limiting.
- Basic **subscription awareness**: expired campuses block login (full enforcement TBD).
- Deferred (marked optional in the brief): "Remember Me", dark mode.
