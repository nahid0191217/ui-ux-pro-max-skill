# Med Solutions — Full-Stack Laravel 12 Medical Tourism Platform

End-to-end Bangladesh-to-India Medical Tourism Assistance Platform built with **Laravel 12**, **MySQL**, **Tailwind CSS v4**, **Alpine.js / Livewire 3**, and **Laravel Breeze Role-Based Access Control (`admin`, `staff`, `patient`)**.

---

## Core Services Covered

1. **Doctor Appointments:** Expert assistance in finding top specialist doctors in the required hospital department across India and securing priority appointments.
2. **Visa Processing & Travel:** Complete Indian Medical Visa processing, official hospital Visa Invitation Letters (VIL), and confirmed air and train ticketing.
3. **Telemedicine Consultations:** Direct video conferencing with Indian specialist doctors before travelling from Bangladesh and seamless follow-up consultations after returning home.
4. **Treatment Plan & Cost Estimate:** Clear pre-travel guidance on optimal medical procedures, expected hospital stay duration, and transparent treatment cost estimates.
5. **Pickup, Drop & Interpreter:** Reliable patient pickup and drop-off from Indian airports and railway stations, paired with dedicated Bengali-speaking medical interpreters.
6. **Hospital-Near Accommodation:** Clean, safe, and budget-friendly guest houses and apartments with self-cooking kitchen facilities located beside the hospital.

---

## Complete Directory & File Structure

```text
projects/med-solutions/
├── app/
│   ├── Enums/
│   │   ├── RequestStatus.php                 # pending, processing, confirmed, completed, cancelled
│   │   └── UserRole.php                      # admin, staff, patient
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── HomeController.php            # Landing page, guide steps, cached services & contact form
│   │   │   ├── ServiceRequestController.php  # Booking form, private report storage, tracking & signed download
│   │   │   ├── ConsultationController.php    # Pre-travel & post-return Telemedicine booking
│   │   │   ├── Patient/
│   │   │   │   └── DashboardController.php   # Patient status tracker & meeting links
│   │   │   └── Admin/
│   │   │       └── DashboardController.php   # Lead management, Visa/Appt updater, Telemedicine & CMS/RBAC
│   │   ├── Middleware/
│   │   │   └── EnsureUserHasRole.php         # Role-based route middleware (`role:admin,staff`)
│   │   └── Requests/
│   │       ├── StoreServiceRequest.php       # BD phone regex, multi-service & MIME validation
│   │       ├── StoreConsultationRequest.php  # Telemedicine validation
│   │       └── StoreContactRequest.php       # Contact inquiry validation
│   ├── Livewire/
│   │   └── ServiceStatusTracker.php          # Real-time Livewire status lookup widget
│   ├── Models/
│   │   ├── User.php
│   │   ├── ServiceRequest.php
│   │   ├── Consultation.php
│   │   ├── Contact.php
│   │   ├── Service.php
│   │   ├── Testimonial.php
│   │   └── BlogPost.php
│   └── Policies/
│       └── ServiceRequestPolicy.php          # Private medical report & request authorization
├── bootstrap/
│   └── app.php                               # Registers `role` middleware alias
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 2026_10_26_000001_create_service_requests_table.php
│   │   ├── 2026_10_26_000002_create_consultations_table.php
│   │   ├── 2026_10_26_000003_create_contacts_table.php
│   │   └── 2026_10_26_000004_create_cms_content_tables.php
│   └── seeders/
│       └── DatabaseSeeder.php                # Seeds Admin/Staff/Patient, 6 English services, testimonials & demo data
├── design-system/
│   └── med-solutions/
│       └── MASTER.md                         # UI/UX Pro Max Design System specification
├── resources/
│   ├── css/app.css                           # Tailwind v4 tokens (`Figtree` + `Noto Sans`)
│   ├── js/app.js                             # Alpine.js initialization
│   └── views/
│       ├── layouts/app.blade.php             # Accessible master layout with sticky header & footer
│       ├── components/
│       │   ├── form-error-summary.blade.php  # WCAG-compliant focusable error summary
│       │   ├── service-card.blade.php        # Reusable service showcase card
│       │   └── status-badge.blade.php        # Semantic status pill component
│       ├── livewire/
│       │   └── service-status-tracker.blade.php
│       ├── home.blade.php                    # Full Landing Page (English)
│       ├── services/
│       │   ├── create.blade.php              # Multi-service booking + private medical report upload
│       │   └── track.blade.php               # 4-stage visual status tracker
│       ├── consultations/
│       │   └── create.blade.php              # Pre-travel & Post-return Telemedicine booking
│       ├── patient/
│       │   └── dashboard.blade.php           # Authenticated patient portal
│       └── admin/
│           └── dashboard.blade.php           # Admin & Coordinator control panel
├── routes/
│   └── web.php                               # Public, Authenticated Patient & RBAC Admin routes
├── .env.example
├── composer.json
├── package.json
└── vite.config.js
```

---

## Production Setup Instructions (Laravel + MySQL)

```bash
# 1. Install PHP & Frontend dependencies
composer install
php artisan breeze:install blade
npm install && npm run build

# 2. Configure MySQL environment
cp .env.example .env
php artisan key:generate
# Set DB_DATABASE=med_solutions_bd, DB_USERNAME, DB_PASSWORD in .env

# 3. Run MySQL migrations & seeders
php artisan migrate --seed

# 4. Start development server
php artisan serve
```

### Default Seeded Accounts (demo only — change before going live)
- **Admin:** `admin@example.com` / `Admin@Med2026!`
- **Care Coordinator (Staff):** `staff@example.com` / `Staff@Med2026!`
- **Demo Patient:** `patient@example.com` / `Patient@Med2026!` (Tracking Code: `MS-2026-DEMO1`, phone `01712987654`)

### Login, Roles & Access Control
- **Sign in / Register:** `/login` and `/register` (patients self-register; the public form can only create `patient` accounts).
- **Panels (one per role, used as the sign-in landing page):**
  - `admin` → `/admin` (Admin Panel): leads, visas, telemedicine, CMS content, user & role management.
  - `staff` → `/staff` (Staff Panel): leads, visas, appointments, telemedicine links, inquiries. No CMS or role management.
  - `patient` → `/patient` (Patient Portal): own requests and consultations only.
- **Guards:** each panel is open only to its own role; other roles are redirected to their own panel. Role changes and CMS edits are admin-only. Every form is CSRF-protected.
- **Cookies:** the session cookie is `SameSite=None; Secure; Partitioned` so sign-in works when the app is shown inside another site's iframe (e.g. the Arena preview).
- **Tracking privacy:** a tracking code shows details only to its owner, to staff, to the browser that created it, or to anyone who supplies the registered phone number.
- **Brute-force protection:** login is rate-limited (throttle middleware in Laravel; 5 failures / 5 minutes per email in the preview).

### Quick Role Switcher (Preview / Demo Only)
The header contains a **Quick Role Switcher** (Admin / Staff / Patient) that signs you in as a seeded demo account without a password. It is for demonstrations only:
- Laravel: enabled only when `DEMO_ROLE_SWITCHER=true` **and** `APP_ENV` is not `production` (see `config/demo.php`). The default is `false`.
- Live preview server: disabled by default (the header switcher is hidden); set `DEMO_ROLE_SWITCHER=1` to show it for demos.
