# PIMS — Installation & Setup Guide

**PIMS** (Patient Information Management System) is a simple Laravel 12 clinic system with login, role-based permissions, an audit trail, and maintenance screens. This guide walks you through getting it running on your own computer, step by step. No prior Laravel experience needed.

---

## 1. What you need installed first

You said you already have these, but here is the checklist:

- **PHP 8.2 or newer** (Laravel 12 requires 8.2+). Check with: `php -v`
- **Composer** (PHP package manager). Check with: `composer -V`
- **MySQL 8** (or MariaDB). Make sure the MySQL server is running.
- **A code editor** like VS Code.

> You do **not** need Node.js or npm for this project. The interface uses plain CSS, so there is no front-end build step.

---

## 2. Unzip the project

Unzip `pims.zip` somewhere easy to find, for example:

```
C:\projects\pims         (Windows)
~/projects/pims          (macOS / Linux)
```

Open a terminal **inside that `pims` folder**. Every command below is run from there.

---

## 3. Install the PHP packages

This downloads Laravel itself and its dependencies into a `vendor/` folder:

```bash
composer install
```

If you get a memory error, run `composer install --no-dev` instead.

---

## 4. Create your environment file

The project ships with a template called `.env.example`. Copy it to `.env`:

```bash
# Windows (PowerShell)
copy .env.example .env

# macOS / Linux
cp .env.example .env
```

Then generate the application key (this secures sessions and cookies):

```bash
php artisan key:generate
```

---

## 5. Create the database and import the SQL

### 5a. Create an empty database

Open MySQL (via the terminal, phpMyAdmin, MySQL Workbench, etc.) and create a database named **`pims_db`**:

```sql
CREATE DATABASE pims_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5b. Import the schema, then the demo data — **in this order**

The SQL files are inside the project at `database/sql/`.

```bash
# 1) The tables (schema) — source of truth
mysql -u root -p pims_db < database/sql/PIMS_DB_corrected.sql

# 2) The demo data (users, permissions, sample lab tests, schedules)
mysql -u root -p pims_db < database/sql/PIMS_seed.sql
```

> If you prefer a GUI like phpMyAdmin: select the `pims_db` database, open the **Import** tab, choose `PIMS_DB_corrected.sql` and run it, then repeat the import for `PIMS_seed.sql`.

> **Important:** Do **not** run `php artisan migrate`. This project uses the provided SQL files as the single source of truth, not Laravel migrations.

---

## 6. Point Laravel at your database

Open the `.env` file and confirm the database lines match your MySQL setup. The defaults are:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pims_db
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL `root` user has a password, put it after `DB_PASSWORD=`.

---

## 7. Link the storage folder (for uploaded lab result files)

MedTechs can upload lab result files (PDF/image). This command makes those uploaded files viewable in the browser:

```bash
php artisan storage:link
```

(You only ever need to run this once.)

---

## 8. Run the application

```bash
php artisan serve
```

Now open your browser to:

```
http://localhost:8000
```

You should see the PIMS login page.

---

## 9. Demo accounts

All demo accounts use the password **`password123`**.

| Role          | Email                | What they can do                                              |
|---------------|----------------------|--------------------------------------------------------------|
| Super Admin   | `admin@pims.test`    | Role permissions, users, maintenance, audit dashboards       |
| Doctor        | `doctor@pims.test`   | View patient records, run consultations, diagnose, prescribe |
| Receptionist  | `recept@pims.test`   | View patient info, add walk-in patients                      |
| MedTech       | `medtech@pims.test`  | Encode lab results, fulfill soft-copy requests               |
| Patient       | `patient@pims.test`  | Update info, book appointments, request lab results          |

A second doctor (`doctor2@pims.test`) also exists so patients have more than one schedule to choose from when booking.

New patients can also self-register from the login page using the **Create an account** link.

---

## 10. A quick tour by role

- **Patient** — update personal information, book an open doctor schedule (optionally attaching lab tests), and request a soft copy of a lab result.
- **Receptionist** — search patient information and register a walk-in patient (OTP is bypassed; an email *or* mobile number is required).
- **Doctor** — see scheduled check-ups, open a patient's chart (basic info, medical history, past diagnoses, lab results, prescriptions), start a consultation, then record a diagnosis and write a prescription.
- **MedTech** — see scheduled lab tests, encode results and upload soft copies, and fulfill soft-copy requests coming from patients and doctors.
- **Super Admin** — per-role audit dashboards, the full audit trail, role-permission management, user management, and maintenance screens for lab categories and lab tests.

---

## 11. Notes on how this was built (for your understanding)

These are intentional, beginner-friendly design choices:

1. **Standard Laravel MVC only.** Controllers talk to Eloquent models which map to your tables, and Blade views render the pages. There is no Repository Pattern, Service Layer, or DTOs — just the plain framework structure so the code is easy to follow.

2. **Simple custom role/permission checks (not the Spatie package).** Your database already defines its own `user_roles` and `role_has_permissions` tables. The Spatie package expects differently-named tables, so forcing it in would have caused conflicts. Instead, the `User` model has small, readable `hasRole()` and `hasPermission()` helpers, and two middleware (`role:` and `permission:`) protect the routes. A Super Admin automatically passes every permission check.

3. **No OTP in registration.** Patient self-registration and receptionist walk-in registration both skip OTP, exactly as requested.

4. **File-based sessions and cache.** So you do **not** need to create any extra Laravel tables — you only import the two provided SQL files.

5. **Plain black-and-white interface.** A single CSS file at `public/css/app.css`, no images, no logos, no emojis.

6. **Audit trail.** A small helper (`app/Helpers/AuditLogger.php`) writes a row to `audit_logs` whenever something important happens (login, create, update, upload, etc.). The Super Admin can review these.

---

## 12. Troubleshooting

- **"could not find driver" / SQLSTATE errors** → make sure the PHP MySQL extension (`pdo_mysql`) is enabled and that MySQL is running.
- **Login fails for demo accounts** → confirm you imported **both** SQL files, schema first then seed.
- **Uploaded lab files show a broken link** → make sure you ran `php artisan storage:link`.
- **A blank page or 500 error** → set `APP_DEBUG=true` in `.env` to see the actual message; the most common cause is a missing `php artisan key:generate` step.
- **Permission/role page says access denied** → log in with the matching role from the demo table above.

---

## 13. Project structure at a glance

```
pims/
├── app/
│   ├── Helpers/AuditLogger.php        # writes audit_logs rows
│   ├── Http/
│   │   ├── Controllers/               # one folder per role
│   │   └── Middleware/                # CheckRole, CheckPermission
│   └── Models/                        # Eloquent models for every table
├── config/                            # standard Laravel config
├── database/
│   └── sql/
│       ├── PIMS_DB_corrected.sql      # the schema (import first)
│       └── PIMS_seed.sql              # demo data (import second)
├── public/
│   ├── css/app.css                    # the black & white styling
│   └── index.php                      # entry point
├── resources/views/                   # Blade templates (the pages)
├── routes/web.php                     # all the app's URLs
└── .env.example                       # copy to .env
```

That's it — you now have a working PIMS. Enjoy exploring it.
