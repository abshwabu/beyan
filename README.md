# Beyan — Madrasa Management SaaS

Madrasa Management SaaS: multi-tenant, responsive web app tailored for online, physical, and hybrid madrasas.
First pilot: **Beyan Academy** (Addis Ababa, Ethiopia).

Stack: Laravel 12, Vue 3 + Inertia.js, Tailwind CSS, **SQLite (Production Configured)**. Modular monolith organized by domain modules.

---

## Architecture Overview

### 1. Domain Modules (`app/Domain/` and `Modules/`)
Organized as a modular monolith:
- **`Institution`**: Multi-tenancy context, workspace management, institution branding, modes (online/physical/hybrid), subscription tiers.
- **`Auth`**: Multi-tenant user models, credential authentication, roles (owner, admin, teacher, student, guardian).
- **`Academics`**: Academic calendars, academic years, terms/semesters per institution.
- **`Students`**: Student life-cycle, profiles, enrollments, guardian linkages.
- **`Teachers`**: Instructor profiles, qualifications, halqa/subject assignments.
- **`Attendance`**: Session and daily attendance tracking across physical and online sessions.
- **`Hifz`**: Quran memorization progress (Sabaq, Sabaq Para/Sabqi, Manzil), Tajweed evaluation.
- **`Finance`**: Tuition management, billing schedules, receipts, donor tracking.
- **`Reports`**: Academic progress cards, attendance aggregates, Hifz milestones.

### 2. SQLite Production Configuration
Configured in `config/database.php` and `.env`:
- **WAL Journal Mode**: `PRAGMA journal_mode = WAL;` (concurrent readers do not block writers).
- **Synchronous NORMAL**: `PRAGMA synchronous = NORMAL;` (optimal balance of persistence and I/O latency).
- **Busy Timeout**: `PRAGMA busy_timeout = 5000;` (waits up to 5,000 ms before returning SQLITE_BUSY).
- **Foreign Keys**: `PRAGMA foreign_keys = ON;` (enforces relational integrity and cascading deletes).
- **Transaction Mode IMMEDIATE**: write transactions start with `BEGIN IMMEDIATE TRANSACTION` to prevent deadlocks and lock-upgrade errors.
- **Database File Security**: stored outside the web root (`database/database.sqlite`), with restricted permissions (`0640` file, `0750` folder).

### 3. Multi-Tenancy Architecture
- **Tenant Key**: ULID primary keys (`char(26)` strings) for all tenant tables, portable across SQLite, PostgreSQL, and MySQL.
- **Foreign Keys**: `institution_id` indexed foreign key on all tenant-owned tables.
- **`BelongsToInstitution` Trait**:
  - Automatically applies `InstitutionScope` global scope to filter all queries to the active institution.
  - Automatically auto-fills `institution_id` on model creation.
  - Blocks cross-tenant data manipulation attempts (`DomainException`).
- **`ResolveTenantInstitution` Middleware**:
  - Resolves active institution from authenticated user (`$request->user()->institution`).
  - Sets tenant context on `TenantManager` and enforces strict scoping.
  - Switches application locale to institution preference.
  - Aborts `403` if an institution is suspended or inactive.
- **Model Scanner Guard Test**:
  - `TenantModelScannerTest` inspects all models and database tables with `institution_id` and fails if any tenant table lacks the `BelongsToInstitution` trait.

### 4. Backups and Restore
- **Backup Command**: `php artisan backup:sqlite {--keep-days=14}`
  - Uses SQLite `VACUUM INTO` to create a live, atomic snapshot.
  - Performs `PRAGMA integrity_check` on the backup file.
  - Automatically prunes backups older than 14 days.
  - Scheduled daily at 02:00 in `routes/console.php`.
- **Restore Command**: `php artisan backup:restore {file?} {--latest} {--force}`
  - Pre-verifies backup file integrity (`PRAGMA integrity_check`).
  - Purges active database connections.
  - Atomically replaces active database file and clears stale WAL/SHM files.
  - Re-verifies active database integrity post-restore.

### 5. Registration Flow
- Short, atomic transaction in `RegisterInstitutionAction`:
  - Creates `Institution` (mode, timezone, currency, locale, status).
  - Creates Owner `User`.
  - Creates starter `AcademicYear` and `Term`.
  - Zero external HTTP or email calls inside the transaction; asynchronous events/notifications trigger after commit.

### 6. i18n & RTL Support
- Supported Locales:
  - `en`: English (LTR)
  - `am`: Amharic / አማርኛ (LTR)
  - `om`: Afaan Oromoo (LTR)
  - `ar`: Arabic / العربية (RTL)
- `DirectionHelper::forLocale()` sets HTML `dir="rtl"` dynamically for Arabic.
- Dates stored in UTC and converted to institutional timezone in UI (`TimezoneHelper`).

---

## Quickstart

### 1. Environment & Migrations
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

### 2. Run Backups
```bash
# Create SQLite backup
php artisan backup:sqlite

# Restore latest SQLite backup
php artisan backup:restore --latest --force
```

### 3. Run Test Suite
```bash
php artisan test
```
