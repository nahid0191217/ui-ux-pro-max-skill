#!/usr/bin/env python3
"""
Med Solutions — Interactive Live Preview Server (100% English, with login & role-based access)
Mirrors the Laravel + MySQL + Tailwind CSS + Breeze RBAC architecture using
Python's built-in http.server + SQLite3 so users can test every workflow live.
"""

import email.policy
from email.parser import BytesParser
import hashlib
import hmac
import html
import json
import secrets
import threading
import time
import os
import sys
import random
import re
import sqlite3
import string
import urllib.parse
from datetime import datetime, timedelta
from http.cookies import SimpleCookie
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

RUNTIME_DIR = "/tmp/med_solutions_preview"
UPLOAD_DIR = os.path.join(RUNTIME_DIR, "uploads")
DB_PATH = os.path.join(RUNTIME_DIR, "med_solutions_v2_auth.sqlite3")
LOGO_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "public", "images", "med-solutions-logo.jpeg")

os.makedirs(UPLOAD_DIR, exist_ok=True)


def get_db() -> sqlite3.Connection:
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn


SETTING_DEFAULTS = {
    "brand_name": "Med Solutions",
    "brand_tagline": "Cross-Border Medical Care",
    "announcement": "",
    "helpline_phone": "+880 XXXX-XXXXXX",
    "support_email": "support@example.com",
    "office_address": "[add your office address]",
    "hero_badge": "Bangladesh\u2019s Trusted Partner for Medical Treatment in India",
    "hero_title": "Your Right Decision Ensures",
    "hero_highlight": "Your Best Medical Treatment",
    "footer_about": "Your trusted cross-border healthcare partner assisting Bangladeshi patients with specialist appointments, Indian medical visas, telemedicine, airport pickup, and hospital-near stays.",
    "social_facebook": "https://facebook.com",
    "social_youtube": "https://youtube.com",
    "social_linkedin": "https://linkedin.com",
}


def get_settings() -> dict:
    conn = get_db()
    rows = conn.execute("SELECT key, value FROM site_settings").fetchall()
    conn.close()
    cfg = dict(SETTING_DEFAULTS)
    for row in rows:
        if row["key"] in cfg:
            cfg[row["key"]] = row["value"]
    return cfg


def init_db() -> None:
    conn = get_db()
    cur = conn.cursor()

    cur.executescript(
        """
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT,
            role TEXT NOT NULL DEFAULT 'patient',
            password_hash TEXT,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            subtitle TEXT NOT NULL,
            description TEXT NOT NULL,
            icon_name TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS service_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tracking_code TEXT UNIQUE NOT NULL,
            user_id INTEGER,
            patient_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            service_type TEXT NOT NULL,
            selected_services TEXT NOT NULL,
            preferred_hospital TEXT,
            preferred_doctor TEXT,
            details TEXT,
            medical_report_path TEXT,
            medical_report_name TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            visa_status TEXT NOT NULL DEFAULT 'not_requested',
            appointment_status TEXT NOT NULL DEFAULT 'not_requested',
            admin_notes TEXT,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS consultations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            patient_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            email TEXT,
            consultation_stage TEXT NOT NULL DEFAULT 'pre_travel',
            department TEXT NOT NULL,
            preferred_doctor TEXT,
            preferred_date TEXT NOT NULL,
            symptoms_notes TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            meeting_link TEXT,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT NOT NULL,
            subject TEXT,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS site_settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );

        CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_name TEXT NOT NULL,
            district TEXT NOT NULL,
            hospital_treated TEXT NOT NULL,
            quote TEXT NOT NULL,
            rating INTEGER NOT NULL DEFAULT 5
        );
        """
    )

    if cur.execute("SELECT COUNT(*) FROM users").fetchone()[0] == 0:
        now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
        cur.executemany(
            "INSERT INTO users (name, email, phone, role, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?)",
            [
                ("Dr. Mahmudul Hasan (Admin)", "admin@example.com", "+8801700000001", "admin", hash_password("Admin@Med2026!"), now),
                ("Farhana Rahman (Care Coordinator)", "staff@example.com", "+8801700000002", "staff", hash_password("Staff@Med2026!"), now),
                ("Md. Kamrul Islam", "patient@example.com", "01712987654", "patient", hash_password("Patient@Med2026!"), now),
                ("Nusrat Jahan Chowdhury", "nusrat@example.com", "01815112233", "patient", hash_password("Patient@Med2026!"), now),
            ],
        )

    if cur.execute("SELECT COUNT(*) FROM services").fetchone()[0] == 0:
        cur.executemany(
            """INSERT INTO services (slug, title, subtitle, description, icon_name, sort_order, is_active)
               VALUES (?, ?, ?, ?, ?, ?, 1)""",
            [
                (
                    "appointment",
                    "Doctor Appointments",
                    "Specialist Matching & Priority Slots",
                    "Expert assistance in finding top specialist doctors in the required hospital department across India and securing priority appointments.",
                    "calendar",
                    1,
                ),
                (
                    "visa_processing",
                    "Visa Processing & Tickets",
                    "Indian MED Visa, VIL & Travel",
                    "Complete Indian Medical Visa processing, official hospital Visa Invitation Letters (VIL), and confirmed air and train ticketing.",
                    "passport",
                    2,
                ),
                (
                    "telemedicine",
                    "Telemedicine Consultations",
                    "Pre-Travel & Post-Return Video Care",
                    "Direct video conferencing with Indian specialist doctors before travelling from Bangladesh and seamless follow-up consultations after returning home.",
                    "video",
                    3,
                ),
                (
                    "treatment_plan",
                    "Treatment Plan & Cost Estimate",
                    "Transparent Clinical Roadmap",
                    "Clear pre-travel guidance on optimal medical procedures, expected hospital stay duration, and transparent treatment cost estimates.",
                    "clipboard",
                    4,
                ),
                (
                    "pickup_drop",
                    "Pickup, Drop & Interpreter",
                    "Arrival Logistics & Language Support",
                    "Reliable patient pickup and drop-off from Indian airports and railway stations, paired with dedicated Bengali-speaking medical interpreters.",
                    "car",
                    5,
                ),
                (
                    "accommodation",
                    "Hospital-Near Accommodation",
                    "Affordable Stay with Self-Cooking",
                    "Clean, safe, and budget-friendly guest houses and apartments with self-cooking kitchen facilities located right beside the hospital.",
                    "home",
                    6,
                ),
            ],
        )

    cur.executemany("INSERT OR IGNORE INTO site_settings (key, value) VALUES (?, ?)", list(SETTING_DEFAULTS.items()))
    if cur.execute("SELECT COUNT(*) FROM testimonials").fetchone()[0] == 0:
        cur.executemany(
            "INSERT INTO testimonials (patient_name, district, hospital_treated, quote, rating) VALUES (?, ?, ?, ?, ?)",
            [
                (
                    "Alhaj Rafiqul Alam",
                    "Uttara, Dhaka",
                    "Apollo Hospitals, Chennai",
                    "From issuing the Visa Invitation Letter for my father's cardiac bypass surgery to Chennai airport pickup and arranging an apartment with a kitchen right beside Apollo—Med Solutions handled everything seamlessly.",
                    5,
                ),
                (
                    "Nusrat Jahan Chowdhury",
                    "Sylhet",
                    "Manipal Hospital, Bangalore",
                    "Our pre-travel video consultation gave us an accurate cost estimate before leaving Bangladesh, and we easily completed our post-surgery follow-up online after returning home.",
                    5,
                ),
                (
                    "Md. Saiful Islam",
                    "Chattogram",
                    "CMC Vellore",
                    "We were worried about language barriers in Vellore, but Med Solutions provided a dedicated Bengali-speaking interpreter and priority doctor appointments that made our entire treatment stress-free.",
                    5,
                ),
            ],
        )

    if cur.execute("SELECT COUNT(*) FROM service_requests").fetchone()[0] == 0:
        now = datetime.utcnow().strftime("%Y-%m-%d %H:%M")
        cur.executemany(
            """INSERT INTO service_requests
               (tracking_code, user_id, patient_name, phone, email, service_type, selected_services,
                preferred_hospital, preferred_doctor, details, medical_report_name, status, visa_status, appointment_status, admin_notes, created_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
            [
                (
                    "MS-2026-DEMO1",
                    3,
                    "Md. Kamrul Islam",
                    "01712987654",
                    "patient@example.com",
                    "appointment",
                    json.dumps(["appointment", "visa_processing", "pickup_drop", "accommodation"]),
                    "Apollo Hospitals, Chennai",
                    "Dr. Refai Showkathali (Interventional Cardiology)",
                    "Coronary angiogram attached. Need urgent Medical Visa Invitation Letter for patient + 1 attendant.",
                    "angiogram_report_kamrul.pdf",
                    "confirmed",
                    "vil_issued",
                    "slot_confirmed",
                    "Official VIL issued by Apollo Chennai and emailed to patient; Chennai airport pickup & self-cooking apartment reserved.",
                    now,
                ),
                (
                    "MS-2026-8492A",
                    4,
                    "Nusrat Jahan Chowdhury",
                    "01815112233",
                    "nusrat@example.com",
                    "treatment_plan",
                    json.dumps(["treatment_plan", "telemedicine"]),
                    "Manipal Hospitals, Bangalore",
                    "Dr. Somashekhar S P (Surgical Oncology)",
                    "Biopsy & PET-CT scan review for estimated surgical cost & stay duration.",
                    "pet_ct_summary.pdf",
                    "processing",
                    "documents_received",
                    "requested",
                    "Medical reports forwarded to oncology board; written treatment plan and cost estimate expected within 24 hours.",
                    now,
                ),
            ],
        )

    if cur.execute("SELECT COUNT(*) FROM consultations").fetchone()[0] == 0:
        dt1 = (datetime.utcnow() + timedelta(days=2)).strftime("%Y-%m-%d 19:30")
        dt2 = (datetime.utcnow() + timedelta(days=4)).strftime("%Y-%m-%d 20:00")
        now = datetime.utcnow().strftime("%Y-%m-%d %H:%M")
        cur.executemany(
            """INSERT INTO consultations
               (user_id, patient_name, phone, email, consultation_stage, department, preferred_doctor, preferred_date, symptoms_notes, status, meeting_link, created_at)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
            [
                (
                    3,
                    "Md. Kamrul Islam",
                    "01712987654",
                    "patient@example.com",
                    "pre_travel",
                    "Cardiology & Cardiac Surgery",
                    "Dr. Devi Prasad Shetty Team",
                    dt1,
                    "Pre-travel evaluation of ECHO and Coronary Angiogram reports prior to flying to India.",
                    "scheduled",
                    "https://meet.google.com/med-soln-care",
                    now,
                ),
                (
                    4,
                    "Abdul Karim",
                    "01911445566",
                    "karim@example.com",
                    "post_return",
                    "Gastroenterology & Liver Transplant",
                    "Dr. Mohamed Rela Institute",
                    dt2,
                    "1-month post-operative liver function blood work review from Dhaka.",
                    "pending",
                    "",
                    now,
                ),
            ],
        )

    conn.commit()
    conn.close()


def e(val) -> str:
    return html.escape("" if val is None else str(val))


SERVICE_NAMES = {
    "appointment": "Doctor Appointments",
    "visa_processing": "Indian Visa, VIL & Ticket Processing",
    "telemedicine": "Telemedicine Video Consultation",
    "treatment_plan": "Treatment Plan & Cost Estimate",
    "pickup_drop": "Airport/Station Pickup, Drop & Interpreter",
    "accommodation": "Hospital-Near Accommodation (Self-Cooking)",
}


def render_badge(status: str) -> str:
    labels = {
        "pending": ("Pending", "bg-amber-50 text-amber-900 ring-amber-600/30", "bg-amber-600"),
        "processing": ("Processing", "bg-brand-50 text-brand-900 ring-brand-600/30", "bg-brand-600"),
        "confirmed": ("Confirmed", "bg-emerald-50 text-emerald-900 ring-emerald-600/30", "bg-emerald-600"),
        "scheduled": ("Scheduled", "bg-emerald-50 text-emerald-900 ring-emerald-600/30", "bg-emerald-600"),
        "completed": ("Completed", "bg-emerald-50 text-emerald-900 ring-emerald-600/30", "bg-emerald-600"),
        "cancelled": ("Cancelled", "bg-red-50 text-red-900 ring-red-600/30", "bg-red-600"),
        "approved": ("Visa Approved", "bg-emerald-50 text-emerald-900 ring-emerald-600/30", "bg-emerald-600"),
        "vil_issued": ("VIL Issued", "bg-brand-50 text-brand-900 ring-brand-600/30", "bg-brand-600"),
        "documents_received": ("Docs Received", "bg-amber-50 text-amber-900 ring-amber-600/30", "bg-amber-600"),
        "ivac_submitted": ("IVAC Submitted", "bg-brand-50 text-brand-900 ring-brand-600/30", "bg-brand-600"),
        "slot_confirmed": ("Slot Confirmed", "bg-emerald-50 text-emerald-900 ring-emerald-600/30", "bg-emerald-600"),
        "requested": ("Requested", "bg-amber-50 text-amber-900 ring-amber-600/30", "bg-amber-600"),
        "not_requested": ("Not Applicable", "bg-slate-100 text-slate-700 ring-slate-500/20", "bg-slate-400"),
    }
    label, cls, dot = labels.get(status, (status, "bg-slate-100 text-slate-700 ring-slate-400/20", "bg-slate-500"))
    return f"""<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {cls}">
        <span class="h-1.5 w-1.5 rounded-full {dot}" aria-hidden="true"></span>{e(label)}
    </span>"""


def svg_icon(name: str) -> str:
    icons = {
        "calendar": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>',
        "passport": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="10" r="3"/><path d="M8 17h8"/></svg>',
        "video": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4V8Z"/></svg>',
        "clipboard": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 2h6v4H9z"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 14h2l1.5-3 2 6 1.5-3H17"/></svg>',
        "car": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"/><circle cx="6.5" cy="16.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg>',
        "home": '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    }
    return icons.get(name, icons["home"])



DEMO_ROLE_SWITCHER = os.environ.get("DEMO_ROLE_SWITCHER", "0") == "1"  # off by default; set DEMO_ROLE_SWITCHER=1 to show it
DEMO_ACCOUNTS = {
    "admin": "admin@example.com",
    "staff": "staff@example.com",
    "patient": "patient@example.com",
}
SESSION_COOKIE = "ms_session"
SESSIONS = {}
SESSIONS_LOCK = threading.Lock()
LOGIN_FAILURES = {}
LOGIN_LOCK = threading.Lock()
MAX_LOGIN_ATTEMPTS = 5
LOGIN_WINDOW_SECONDS = 300
_ctx = threading.local()


def new_session_data(user_id=None) -> dict:
    return {"user_id": user_id, "csrf": secrets.token_urlsafe(32), "granted_codes": []}


def hash_password(password: str) -> str:
    salt = secrets.token_bytes(16)
    iterations = 200000
    digest = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, iterations)
    return f"pbkdf2_sha256${iterations}${salt.hex()}${digest.hex()}"


def verify_password(password: str, stored) -> bool:
    try:
        algo, iterations, salt_hex, digest_hex = stored.split("$")
        if algo != "pbkdf2_sha256":
            return False
        candidate = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), bytes.fromhex(salt_hex), int(iterations))
        return hmac.compare_digest(candidate.hex(), digest_hex)
    except (ValueError, TypeError, AttributeError):
        return False


def digits_only(value) -> str:
    return re.sub(r"\D+", "", str(value or ""))


def last10(value) -> str:
    d = digits_only(value)
    return d[-10:] if len(d) >= 10 else ""


def phones_match(stored, supplied) -> bool:
    a, b = last10(stored), last10(supplied)
    return bool(a) and a == b


def can_manage(user) -> bool:
    return user is not None and user["role"] in ("admin", "staff")


def owns_record(row, user) -> bool:
    if user is None:
        return False
    if row["user_id"] is not None and row["user_id"] == user["id"]:
        return True
    return phones_match(row["phone"], user["phone"])


def track_allowed(row, user, phone_input, session) -> bool:
    if user is not None and (can_manage(user) or owns_record(row, user)):
        return True
    if row["tracking_code"] in session.get("granted_codes", []):
        return True
    return phones_match(row["phone"], phone_input)


def current_user_id():
    user = getattr(_ctx, "user", None)
    return user["id"] if user is not None else None


def panel_for(user) -> str:
    """Each role lands in exactly one panel."""
    if user is None:
        return "/login"
    role = user["role"]
    if role == "admin":
        return "/admin"
    if role == "staff":
        return "/staff"
    return "/patient"


dashboard_path_for = panel_for


def landing_for(user, requested: str) -> str:
    """Honour a stored ?next= only if the role may open it; otherwise use the role's own panel."""
    path = urllib.parse.urlparse(requested or "").path
    if path.startswith("/admin") and user["role"] != "admin":
        return panel_for(user)
    if path.startswith("/staff") and user["role"] != "staff":
        return panel_for(user)
    if path.startswith("/patient") and user["role"] != "patient":
        return panel_for(user)
    return requested or panel_for(user)


def safe_next(value: str) -> str:
    if value and value.startswith("/") and not value.startswith("//") and "\\" not in value:
        return value
    return ""


def login_locked(email: str) -> bool:
    now = time.time()
    with LOGIN_LOCK:
        recent = [t for t in LOGIN_FAILURES.get(email, []) if now - t < LOGIN_WINDOW_SECONDS]
        LOGIN_FAILURES[email] = recent
        return len(recent) >= MAX_LOGIN_ATTEMPTS


def record_login_failure(email: str) -> None:
    with LOGIN_LOCK:
        LOGIN_FAILURES.setdefault(email, []).append(time.time())


def clear_login_failures(email: str) -> None:
    with LOGIN_LOCK:
        LOGIN_FAILURES.pop(email, None)


def _inject_csrf(html_text: str) -> str:
    session = getattr(_ctx, "session", None)
    if not session:
        return html_text
    token_field = '<input type="hidden" name="_csrf" value="' + session["csrf"] + '">'
    return re.sub(
        r'(<form\b[^>]*\bmethod="POST"[^>]*>)',
        lambda m: m.group(1) + token_field,
        html_text,
        flags=re.IGNORECASE,
    )


def render_forbidden(message: str = "This area is restricted to authorized Med Solutions staff and administrators.", title: str = "Restricted Area") -> bytes:
    body = f"""
    <section class="py-20">
        <div class="mx-auto max-w-lg px-4 text-center">
            <div class="med-card p-8">
                <p class="text-xs font-bold uppercase tracking-wider text-red-700">403 · Access Denied</p>
                <h1 class="mt-2 font-heading text-2xl font-extrabold text-slate-900">{e(title)}</h1>
                <p class="mt-3 text-sm text-slate-600">{e(message)}</p>
                <div class="mt-6 flex justify-center gap-3">
                    <a href="/" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Back to Home</a>
                    <a href="/login" class="rounded-xl bg-brand-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-900">Sign In</a>
                </div>
            </div>
        </div>
    </section>"""
    return layout("Access Denied | Med Solutions", body, "")


def render_login(email: str = "", next_url: str = "", error_msg: str = "") -> bytes:
    demo_box = ""
    if DEMO_ROLE_SWITCHER:
        demo_box = """
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-950">
                    <p class="font-bold">Preview demo accounts (disable DEMO_ROLE_SWITCHER outside preview)</p>
                    <ul class="mt-2 space-y-1 font-mono">
                        <li>admin@example.com / Admin@Med2026!</li>
                        <li>staff@example.com / Staff@Med2026!</li>
                        <li>patient@example.com / Patient@Med2026!</li>
                    </ul>
                </div>"""
    body = f"""
    <section class="py-12 sm:py-20">
        <div class="mx-auto max-w-md px-4 sm:px-6">
            <div class="med-card p-6 sm:p-8">
                <div class="text-center">
                    <h1 class="font-heading text-2xl font-extrabold text-slate-900">Sign In to Your Account</h1>
                    <p class="mt-1 text-sm text-slate-600">Access your patient dashboard or coordinator portal</p>
                </div>
                <form action="/login" method="POST" class="mt-6 space-y-5" novalidate>
                    <input type="hidden" name="next" value="{e(next_url)}">
                    <div>
                        <label for="login-email" class="block text-sm font-semibold text-slate-800">Email Address <span class="text-red-600">*</span></label>
                        <input id="login-email" name="email" type="email" autocomplete="email" value="{e(email)}" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    </div>
                    <div>
                        <label for="login-password" class="block text-sm font-semibold text-slate-800">Password <span class="text-red-600">*</span></label>
                        <input id="login-password" name="password" type="password" autocomplete="current-password" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    </div>
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-brand-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">Sign In Securely</button>
                </form>
                <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
                    Don’t have a patient account yet? <a href="/register" class="font-bold text-brand-800 underline hover:text-emerald-700">Create an account</a>
                </p>
                {demo_box}
            </div>
        </div>
    </section>"""
    return layout("Sign In | Med Solutions", body, "/login", error_msg=error_msg)


def render_register(name: str = "", phone: str = "", email: str = "", error_msg: str = "") -> bytes:
    field = "w-full mt-1.5 rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
    body = f"""
    <section class="py-12 sm:py-16">
        <div class="mx-auto max-w-lg px-4 sm:px-6">
            <div class="med-card p-6 sm:p-8">
                <div class="text-center">
                    <h1 class="font-heading text-2xl font-extrabold text-slate-900">Create Your Patient Account</h1>
                    <p class="mt-1 text-sm text-slate-600">Track visa updates, appointments, and video consultations in one secure portal</p>
                </div>
                <form action="/register" method="POST" class="mt-6 space-y-4" novalidate>
                    <div>
                        <label for="reg-name" class="block text-sm font-semibold text-slate-800">Full Name (As on Passport) <span class="text-red-600">*</span></label>
                        <input id="reg-name" name="name" type="text" value="{e(name)}" required class="{field}">
                    </div>
                    <div>
                        <label for="reg-phone" class="block text-sm font-semibold text-slate-800">Mobile / WhatsApp Number <span class="text-red-600">*</span></label>
                        <input id="reg-phone" name="phone" type="tel" value="{e(phone)}" placeholder="017XXXXXXXX" required class="{field}">
                    </div>
                    <div>
                        <label for="reg-email" class="block text-sm font-semibold text-slate-800">Email Address <span class="text-red-600">*</span></label>
                        <input id="reg-email" name="email" type="email" value="{e(email)}" required class="{field}">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="reg-password" class="block text-sm font-semibold text-slate-800">Password <span class="text-red-600">*</span></label>
                            <input id="reg-password" name="password" type="password" autocomplete="new-password" required class="{field}">
                        </div>
                        <div>
                            <label for="reg-password2" class="block text-sm font-semibold text-slate-800">Confirm Password <span class="text-red-600">*</span></label>
                            <input id="reg-password2" name="password_confirmation" type="password" autocomplete="new-password" required class="{field}">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">At least 8 characters, including letters and numbers.</p>
                    <button type="submit" class="w-full cursor-pointer rounded-xl btn-brand px-5 py-3 text-sm font-bold text-white shadow-sm transition ">Create Patient Account</button>
                </form>
                <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
                    Already have an account? <a href="/login" class="font-bold text-brand-800 underline hover:text-emerald-700">Sign In</a>
                </p>
            </div>
        </div>
    </section>"""
    return layout("Create Patient Account | Med Solutions", body, "/register", error_msg=error_msg)


def layout(title: str, body: str, active_path: str = "/", flash_msg: str = "", error_msg: str = "") -> bytes:
    flash_html = ""
    if flash_msg:
        flash_html = f"""
        <div role="status" aria-live="polite" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3.5 text-sm font-semibold text-emerald-950 shadow-sm">
                <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>{e(flash_msg)}</span>
            </div>
        </div>"""
    if error_msg:
        flash_html += f"""
        <div role="alert" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 rounded-xl border border-red-300 bg-red-50 px-4 py-3.5 text-sm font-semibold text-red-950 shadow-sm">
                <svg class="h-5 w-5 shrink-0 text-red-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <span>{e(error_msg)}</span>
            </div>
        </div>"""

    cfg = get_settings()
    brand_name = cfg["brand_name"]
    brand_tagline = cfg["brand_tagline"]
    helpline = cfg["helpline_phone"]
    support_email = cfg["support_email"]
    office_address = cfg["office_address"]
    footer_about = cfg["footer_about"]
    social_fb = cfg["social_facebook"]
    social_yt = cfg["social_youtube"]
    social_li = cfg["social_linkedin"]
    announce_html = ""
    if cfg["announcement"].strip():
        announce_text = e(cfg["announcement"])
        announce_html = (
            '<div class="bg-gradient-to-r from-brand-900 via-brand-700 to-accent-600 text-center text-xs font-semibold text-white sm:text-sm">'
            '<div class="mx-auto max-w-7xl px-4 py-2.5 sm:px-6 lg:px-8">' + announce_text + "</div></div>"
        )

    user = getattr(_ctx, "user", None)
    switcher_html = ""
    if DEMO_ROLE_SWITCHER:
        role_buttons = []
        for role_key, role_label, active_cls in (
            ("admin", "Admin", "bg-amber-500 text-slate-950"),
            ("staff", "Staff", "bg-brand-600 text-white"),
            ("patient", "Patient", "bg-emerald-700 text-white"),
        ):
            is_active = user is not None and user["role"] == role_key
            btn_cls = active_cls if is_active else "bg-slate-800 text-slate-300 hover:bg-slate-700"
            role_buttons.append(
                '<button type="submit" name="role" value="' + role_key + '" class="cursor-pointer rounded-md px-2.5 py-1 font-semibold transition ' + btn_cls + '">' + role_label + "</button>"
            )
        switcher_html = (
            '<form action="/demo/switch-role" method="POST" class="flex flex-wrap items-center gap-1.5">'
            '<span class="text-slate-400">Quick Role Switcher (Demo):</span>' + "".join(role_buttons) + "</form>"
        )
    if user is not None:
        account_html = (
            '<span class="text-slate-300">Signed in: <strong class="text-white">' + e(user["name"]) + '</strong> '
            '<span class="rounded bg-slate-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">' + e(user["role"]) + "</span></span>"
            '<form action="/logout" method="POST">'
            '<button type="submit" class="cursor-pointer rounded-md border border-slate-600 px-2.5 py-1 font-semibold text-slate-200 hover:bg-slate-800">Sign Out</button>'
            "</form>"
        )
        if can_manage(user):
            panel_label = "Admin Panel" if user["role"] == "admin" else "Staff Panel"
            acct_html = '<a href="' + panel_for(user) + '" class="hidden sm:inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">' + panel_label + "</a>"
        else:
            acct_html = '<a href="/patient" class="hidden sm:inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">My Portal</a>'
    else:
        account_html = '<a href="/login" class="cursor-pointer rounded-md px-2.5 py-1 font-semibold text-accent-300 hover:text-brand-200">Sign In</a>'
        acct_html = (
            '<a href="/login" class="hidden sm:inline-flex cursor-pointer items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100">Sign In</a>'
            '<a href="/register" class="hidden sm:inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-brand-700 px-3.5 py-2 text-xs font-bold text-brand-800 transition hover:bg-brand-50">Register</a>'
        )
    topbar_html = (
        '<div class="bg-slate-900 text-slate-100 text-xs"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">'
        '<div class="flex flex-wrap items-center gap-3">'
        '<span class="inline-flex items-center gap-1.5 font-semibold text-accent-300">24/7 Helpline: ' + e(helpline) + '</span>'
        '<span class="hidden sm:inline text-slate-500">|</span>'
        '<span class="hidden sm:inline text-slate-300">Bangladesh → India Medical Care</span></div>'
        '<div class="flex flex-wrap items-center gap-3">' + switcher_html + account_html + "</div>"
        "</div></div>"
    )

    def nav_cls(p: str) -> str:
        return "text-brand-800 font-bold" if active_path == p else "text-slate-700 hover:text-brand-800 font-semibold"

    page = f"""<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{e(title)}</title>
    <link rel="icon" type="image/jpeg" href="/images/med-solutions-logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {{
            theme: {{
                extend: {{
                    colors: {BRAND_PALETTE_JS},
                    fontFamily: {{
                        sans: ['Noto Sans', 'Figtree', 'sans-serif'],
                        heading: ['Figtree', 'Noto Sans', 'sans-serif'],
                    }}
                }}
            }}
        }};
    </script>
    <style>
        .med-card {{
            background: #FFFFFF;
            border: 1px solid #D9E2FA;
            border-radius: 1rem;
            box-shadow: 0 6px 20px -4px rgba(36, 48, 144, 0.08), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            transition: box-shadow 200ms ease, border-color 200ms ease;
        }}
        .med-card:hover {{
            border-color: #98AEE3;
            box-shadow: 0 12px 28px -6px rgba(36, 48, 144, 0.14), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
        }}
        .btn-brand {{
            background: linear-gradient(120deg, #1E2770 0%, #243090 45%, #2D6CAE 100%);
            color: #FFFFFF;
            box-shadow: 0 10px 24px -10px rgba(36, 48, 144, 0.6);
            transition: transform 200ms ease, box-shadow 200ms ease, filter 200ms ease;
        }}
        .btn-brand:hover {{
            transform: translateY(-1px);
            box-shadow: 0 14px 28px -10px rgba(36, 48, 144, 0.7);
            filter: brightness(1.06);
        }}
        .text-gradient {{
            background: linear-gradient(120deg, #243090 0%, #60A8D8 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }}
        body {{
            background-color: #F6F8FD;
            background-image:
                radial-gradient(900px 480px at 8% -8%, rgba(152, 174, 227, 0.28) 0%, transparent 60%),
                radial-gradient(800px 440px at 100% 0%, rgba(96, 168, 216, 0.22) 0%, transparent 55%),
                radial-gradient(700px 500px at 50% 110%, rgba(36, 48, 144, 0.07) 0%, transparent 60%);
        }}
        .med-card {{
            border-radius: 1.25rem;
            border-color: rgba(36, 48, 144, 0.10);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 30px -12px rgba(36, 48, 144, 0.14);
            transition: transform 250ms ease, box-shadow 250ms ease, border-color 250ms ease;
        }}
        .med-card:hover {{
            transform: translateY(-3px);
            border-color: rgba(36, 48, 144, 0.22);
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05), 0 20px 40px -14px rgba(36, 48, 144, 0.22);
        }}
        .eyebrow {{
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 9999px;
            border: 1px solid rgba(36, 48, 144, 0.14);
            background: rgba(255, 255, 255, 0.8);
            padding: 0.375rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1E2770;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }}
        @keyframes med-rise {{
            from {{ opacity: 0; transform: translateY(14px); }}
            to {{ opacity: 1; transform: none; }}
        }}
        .reveal {{
            animation: med-rise 600ms cubic-bezier(0.2, 0.7, 0.2, 1) both;
        }}
        @media (prefers-reduced-motion: reduce) {{
            .reveal {{ animation: none; }}
            .med-card:hover {{ transform: none; }}
        }}
        @media (prefers-reduced-motion: reduce) {{
            html {{ scroll-behavior: auto; }}
            *, *::before, *::after {{
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }}
        }}
    </style>
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased selection:bg-brand-100 selection:text-brand-950">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-800 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold text-white">
        Skip to main content
    </a>

    {topbar_html}

    <!-- Main Navbar -->
    <header class="sticky top-0 z-40 border-b border-brand-100/70 bg-white/75 shadow-sm shadow-brand-900/5 backdrop-blur-xl">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8" aria-label="Main Navigation">
            <a href="/" class="flex items-center gap-3 cursor-pointer rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700">
                <img src="/images/med-solutions-logo.jpeg" alt="{e(brand_name)} logo" class="h-12 w-auto sm:h-14">
                <div class="hidden sm:block leading-tight">
                    <span class="block font-heading text-lg font-extrabold tracking-tight text-brand-900">{e(brand_name)}</span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-accent-600">{e(brand_tagline)}</span>
                </div>
            </a>

            <div class="hidden lg:flex lg:items-center lg:gap-6 text-sm">
                <a href="/#about" class="cursor-pointer transition-colors {nav_cls('/about')}">About Us</a>
                <a href="/#services" class="cursor-pointer transition-colors {nav_cls('/services')}">Services</a>
                <a href="/#pre-travel-guide" class="cursor-pointer transition-colors {nav_cls('/guide')}">Pre-Travel Guide</a>
                <a href="/telemedicine" class="cursor-pointer transition-colors {nav_cls('/telemedicine')}">Telemedicine</a>
                <a href="/track" class="cursor-pointer transition-colors {nav_cls('/track')}">Track Status</a>
                <a href="/#contact" class="cursor-pointer transition-colors text-slate-700 hover:text-brand-800 font-semibold">Contact</a>
            </div>

            <div class="flex items-center gap-2.5">
                {acct_html}
                <a href="/service-request" class="cursor-pointer inline-flex items-center gap-2 rounded-xl btn-brand px-4 py-2.5 text-xs sm:text-sm font-bold text-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
                    <span>Request Assistance</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </nav>
    </header>

    {announce_html}
    <div id="frame-notice" class="hidden border-b border-amber-300 bg-amber-50 text-amber-950 text-sm">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
            <span class="font-semibold">You are viewing this page inside a preview frame. Browsers often block the sign-in cookie here, so sign-in can fail. Open the site in a new tab to sign in.</span>
            <a id="frame-open" href="/login" target="_blank" rel="noopener" class="btn-brand shrink-0 cursor-pointer rounded-lg px-3.5 py-1.5 text-xs font-bold text-white">Open in New Tab</a>
        </div>
    </div>
    {flash_html}

    <main id="main-content">
        {body}
    </main>

    <footer class="mt-20 bg-gradient-to-b from-slate-950 to-brand-950 text-slate-300">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="inline-block rounded-xl bg-white p-2">
                            <img src="/images/med-solutions-logo.jpeg" alt="{e(brand_name)} logo" class="h-16 w-auto">
                        </div>
                        <div>
                            <span class="block font-heading text-xl font-extrabold text-white">{e(brand_name)}</span>
                            <span class="block text-xs font-semibold uppercase tracking-widest text-accent-300">{e(brand_tagline)}</span>
                        </div>
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-slate-400">
                        {e(footer_about)}
                    </p>
                    <div class="mt-5 flex items-center gap-3">
                        <a href="{e(social_fb)}" target="_blank" rel="noopener" aria-label="Facebook Page" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
                        </a>
                        <a href="{e(social_yt)}" target="_blank" rel="noopener" aria-label="YouTube Channel" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.58 7.19a2.51 2.51 0 0 0-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42c-.86.23-1.54.91-1.77 1.77C2 8.75 2 12 2 12s0 3.25.42 4.81c.23.86.91 1.54 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42a2.51 2.51 0 0 0 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81zM10 15V9l5.2 3-5.2 3z"/></svg>
                        </a>
                        <a href="{e(social_li)}" target="_blank" rel="noopener" aria-label="LinkedIn Profile" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Contact Us</h3>
                    <p class="mt-3 text-sm text-slate-400">Office address: {e(office_address)}</p>
                    <p class="mt-2 text-sm text-accent-400 font-semibold">{e(helpline)} • {e(support_email)}</p>
                </div>
            </div>
            <div class="mt-10 border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
                &copy; 2026 Med Solutions. All rights reserved.
            </div>
        </div>
    </footer>
    <script>
        var onAuthPage = window.location.pathname.indexOf('/login') === 0 || window.location.pathname.indexOf('/register') === 0;
        if (window.top !== window.self && onAuthPage) {{
            document.getElementById('frame-notice').classList.remove('hidden');
            document.getElementById('frame-open').href = window.location.href;
        }}
    </script>
</body>
</html>"""
    return _inject_csrf(page).encode("utf-8")


def render_home(flash_msg: str = "", error_msg: str = "") -> bytes:
    cfg = get_settings()
    hero_badge = cfg["hero_badge"]
    hero_title = cfg["hero_title"]
    hero_highlight = cfg["hero_highlight"]
    helpline = cfg["helpline_phone"]
    support_email = cfg["support_email"]
    conn = get_db()
    services = conn.execute("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order").fetchall()
    testimonials = conn.execute("SELECT * FROM testimonials ORDER BY id DESC").fetchall()
    conn.close()

    cards_html = ""
    for idx, svc in enumerate(services):
        num = f"{idx + 1:02d}"
        link = "/telemedicine" if svc["slug"] == "telemedicine" else f"/service-request?service={e(svc['slug'])}"
        cards_html += f"""
        <article class="med-card reveal group flex flex-col justify-between p-6">
            <div>
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-200 transition-colors duration-200 group-hover:bg-brand-700 group-hover:text-white">
                        {svg_icon(svc["icon_name"])}
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Service #{num}</span>
                </div>
                <h3 class="font-heading text-xl font-bold text-slate-900">{e(svc["title"])}</h3>
                <p class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-brand-800">{e(svc["subtitle"])}</p>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{e(svc["description"])}</p>
            </div>
            <div class="mt-6 border-t border-slate-100 pt-4">
                <a href="{link}" class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-brand-800 transition-colors hover:text-emerald-700">
                    <span>Request This Service</span>
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </article>"""

    steps = [
        ("01", "Passport & Medical Records Audit", "Ensure both patient and attendant passports have at least 6 months of validity, and compile all prescriptions, scans, and lab reports into chronological PDFs."),
        ("02", "Specialist Match & Treatment Plan", "Receive a pre-travel evaluation from leading Indian specialists detailing recommended procedures, hospital stay duration, and transparent cost estimates."),
        ("03", "Visa Invitation Letter (VIL) & MED Visa", "Always obtain an Indian Medical Visa (MED) rather than a Tourist Visa for surgeries or inpatient care. We secure official hospital VILs and prepare your IVAC file."),
        ("04", "Forex Endorsement & Flight/Train Tickets", "Endorse foreign currency (USD / Dual-Currency Card) on your passport and book confirmed flight or train tickets aligned with your appointment slot."),
        ("05", "Pickup, Interpreter & Hospital-Near Stay", "Upon arrival in India, our local team handles airport/station pickup, Bengali-speaking interpreter support, self-cooking accommodation beside the hospital, and FRRO registration."),
    ]
    steps_html = "".join(
        f"""<div class="med-card flex flex-col p-6">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-800 font-heading text-base font-extrabold text-white">{s[0]}</span>
                <h3 class="mt-4 font-heading text-lg font-bold text-slate-900">{s[1]}</h3>
                <p class="mt-2.5 text-sm leading-relaxed text-slate-600">{s[2]}</p>
            </div>"""
        for s in steps
    )

    testi_html = "".join(
        f"""<blockquote class="med-card flex flex-col justify-between p-6">
                <div>
                    <div class="flex items-center gap-1 text-amber-500" aria-label="{t['rating']} out of 5 stars">
                        {''.join('<svg class="h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>' for _ in range(t['rating']))}
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-slate-700">“{e(t['quote'])}”</p>
                </div>
                <footer class="mt-6 border-t border-slate-100 pt-4">
                    <div class="font-bold text-slate-900">{e(t['patient_name'])}</div>
                    <div class="text-xs text-slate-500">{e(t['district'])} • {e(t['hospital_treated'])}</div>
                </footer>
            </blockquote>"""
        for t in testimonials
    )

    body = f"""
    <!-- HERO SECTION -->
    <section class="relative overflow-hidden py-14 sm:py-20">
        <div aria-hidden="true" class="pointer-events-none absolute -left-32 -top-40 h-[28rem] w-[28rem] rounded-full bg-brand-200/60 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-28 top-6 h-[24rem] w-[24rem] rounded-full bg-accent-200/70 blur-3xl"></div>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <div class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-4 py-1.5 text-xs font-bold text-brand-900 shadow-sm">
                        <span class="relative flex h-2.5 w-2.5" aria-hidden="true"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-accent-400 opacity-75"></span><span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-accent-500"></span></span>
                        <span>{e(hero_badge)}</span>
                    </div>

                    <h1 class="mt-6 font-heading text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        {e(hero_title)} <span class="text-gradient">{e(hero_highlight)}</span>
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg">
                        <strong>Med Solutions</strong> coordinates your entire cross-border treatment journey—from reviewing medical reports and securing priority appointments with India’s top specialists to official Visa Invitation Letters (VIL), telemedicine video consults, airport pickup, and affordable self-cooking accommodation right next to your hospital.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="/service-request" class="cursor-pointer inline-flex items-center gap-2.5 rounded-xl btn-brand px-6 py-3.5 text-base font-bold text-white shadow-md transition ">
                            <span>Get Free Treatment Plan & Appointment</span>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="/telemedicine" class="cursor-pointer inline-flex items-center gap-2.5 rounded-xl border-2 border-brand-800 bg-white px-6 py-3.5 text-base font-bold text-brand-900 transition hover:bg-brand-50">
                            <svg class="h-5 w-5 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4V8Z"/></svg>
                            <span>Book Video Consultation</span>
                        </a>
                    </div>

                    <!-- Live Tracking Widget -->
                    <form action="/track" method="GET" class="mt-8 max-w-xl rounded-2xl border border-brand-200 bg-white p-2 shadow-sm">
                        <label for="hero-track" class="sr-only">Track Request Status by Tracking Code</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input id="hero-track" name="tracking_code" type="text" value="MS-2026-DEMO1" placeholder="Enter Tracking Code (e.g. MS-2026-DEMO1)" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-900 focus:border-brand-700 focus:outline-none">
                            <button type="submit" class="cursor-pointer shrink-0 rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-900">
                                Track Live Status
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-5">
                    <div class="med-card p-6 sm:p-8">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-brand-800">Trusted Medical Assistance</p>
                                <h2 class="mt-1 font-heading text-xl font-bold text-slate-900">Why Choose Med Solutions?</h2>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </div>
                        </div>

                        <dl class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-xl bg-brand-50/70 p-4">
                                <dt class="text-xs font-medium text-slate-600">Partner Hospitals</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-brand-900">50+ Accredited</dd>
                            </div>
                            <div class="rounded-xl bg-emerald-50/70 p-4">
                                <dt class="text-xs font-medium text-slate-600">Patients Assisted</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-emerald-900">10,000+</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-600">VIL & Visa Turnaround</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-slate-900">24–48 Hours</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-600">Language Interpreters</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-slate-900">100% Dedicated</dd>
                            </div>
                        </dl>

                        <div class="mt-6 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Leading Indian Hospital Network</p>
                            <p class="mt-1.5 text-sm font-semibold text-slate-800">
                                Apollo • CMC Vellore • Manipal • Narayana Health • Fortis • Medanta • AIG • Tata Memorial
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ABOUT US -->
    <section class="relative z-10 -mt-10 pb-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-white/80 bg-brand-100/70 shadow-xl shadow-brand-900/10 lg:grid-cols-4">
                <div class="bg-white/95 p-6 sm:p-7"><div class="font-heading text-3xl font-extrabold text-gradient">{len(services)}</div><div class="mt-1 text-sm font-semibold text-slate-600">Core assistance services</div></div>
                <div class="bg-white/95 p-6 sm:p-7"><div class="font-heading text-3xl font-extrabold text-gradient">24/7</div><div class="mt-1 text-sm font-semibold text-slate-600">Helpline for patients</div></div>
                <div class="bg-white/95 p-6 sm:p-7"><div class="font-heading text-3xl font-extrabold text-gradient">BD &rarr; IN</div><div class="mt-1 text-sm font-semibold text-slate-600">Bangladesh to India care</div></div>
                <div class="bg-white/95 p-6 sm:p-7"><div class="font-heading text-3xl font-extrabold text-gradient">Live</div><div class="mt-1 text-sm font-semibold text-slate-600">Status tracking and secure report upload</div></div>
            </div>
        </div>
    </section>

    <section id="about" class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-800">About Med Solutions</span>
                    <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                        Making Cross-Border Healthcare Safe, Transparent & Stress-Free for Bangladeshi Patients
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Without verified medical guidance, families travelling abroad often face wrong department bookings, inflated costs, language barriers, and accommodation struggles. <strong>Med Solutions</strong> stands beside you from initial report evaluation in Dhaka through hospital discharge in India and post-return follow-up care.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm font-medium text-slate-700">
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Direct hospital counter billing — zero hidden middleman markups.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Pre-travel specialist video consultations and post-return follow-ups from home.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Airport/station pickup, Bengali-speaking interpreters, and self-cooking guest houses.</span>
                        </li>
                    </ul>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Right Specialist Match</h3>
                        <p class="mt-2 text-sm text-slate-600">Clinical assessment of your diagnostic reports to connect you with the top senior consultant for your condition.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Fast Visa Invitation (VIL)</h3>
                        <p class="mt-2 text-sm text-slate-600">High Commission–compliant Visa Invitation Letters obtained directly from accredited Indian hospitals.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Upfront Cost Clarity</h3>
                        <p class="mt-2 text-sm text-slate-600">Written pre-travel estimates detailing treatment procedures, expected stay duration, and hospital expenses.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Post-Return Continuity</h3>
                        <p class="mt-2 text-sm text-slate-600">Dedicated video conferencing with your treating Indian doctor for lab reviews after returning to Bangladesh.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6 CORE SERVICES -->
    <section id="services" class="bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Our 6 Core Services</span>
                <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-4xl">Complete Medical Tourism Assistance Under One Roof</h2>
                <p class="mt-3 text-base text-slate-600">Select any individual or combined assistance service and upload your medical reports online.</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {cards_html}
            </div>
        </div>
    </section>

    <!-- STEP BY STEP PRE-TRAVEL GUIDE -->
    <section id="pre-travel-guide" class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">Patient & Attendant Preparation Guide</span>
                <h2 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-4xl">
                    Essential Information Before Travelling to India for Medical Treatment
                </h2>
                <p class="mt-3 text-base text-slate-600">Follow these 5 structured milestones before departure for smooth visa processing, immigration clearance, and hospital admission.</p>
            </div>
            <div class="mt-12 grid gap-6 lg:grid-cols-5">
                {steps_html}
            </div>
            <div class="mt-10 rounded-2xl border border-brand-200 bg-brand-50/60 p-6 sm:p-8">
                <div class="grid items-center gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <h3 class="font-heading text-lg font-bold text-slate-900">Mandatory Pre-Travel Document Checklist:</h3>
                        <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                            <p>• Valid Passport (minimum 6 months validity) + previous passports</p>
                            <p>• Official Hospital Visa Invitation Letter (VIL) for High Commission</p>
                            <p>• Local doctor referral summary & chronological diagnostic reports</p>
                            <p>• Bank solvency / USD endorsement on passport & National ID (NID)</p>
                        </div>
                    </div>
                    <div class="lg:text-right">
                        <a href="/service-request?service=visa_processing" class="cursor-pointer inline-flex items-center justify-center rounded-xl bg-brand-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
                            Request Visa & VIL Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Verified Patient Stories</span>
                <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">Trusted by Patients & Families Across Bangladesh</h2>
            </div>
            <div class="grid gap-6 md:grid-cols-3">
                {testi_html}
            </div>
        </div>
    </section>

    <!-- CONTACT FORM -->
    <section id="contact" class="bg-white py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Speak With a Care Coordinator</span>
                    <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">Have Questions About Treatment in India? Contact Us</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Send us an inquiry regarding specialist appointments, medical visa documentation, or hospital cost estimates.</p>
                    <div class="mt-6 space-y-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-bold text-slate-900">24/7 Helpline & WhatsApp</div>
                            <div class="mt-1 font-semibold text-brand-800">{e(helpline)}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-bold text-slate-900">Email & Official Website</div>
                            <div class="mt-1 text-slate-600">{e(support_email)}</div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <form action="/contact" method="POST" class="med-card p-6 sm:p-8">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="c-name" class="block text-sm font-semibold text-slate-800">Full Name <span class="text-red-600">*</span></label>
                                <input id="c-name" name="name" type="text" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                            </div>
                            <div>
                                <label for="c-phone" class="block text-sm font-semibold text-slate-800">Phone / WhatsApp Number <span class="text-red-600">*</span></label>
                                <input id="c-phone" name="phone" type="tel" placeholder="017XXXXXXXX" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                            </div>
                            <div>
                                <label for="c-email" class="block text-sm font-semibold text-slate-800">Email Address</label>
                                <input id="c-email" name="email" type="email" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                            </div>
                            <div>
                                <label for="c-subject" class="block text-sm font-semibold text-slate-800">Subject</label>
                                <input id="c-subject" name="subject" type="text" placeholder="Appointment / Medical Visa Inquiry" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="c-msg" class="block text-sm font-semibold text-slate-800">Your Message <span class="text-red-600">*</span></label>
                                <textarea id="c-msg" name="message" rows="3" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="mt-5 cursor-pointer rounded-xl bg-brand-800 px-6 py-3 text-sm font-bold text-white hover:bg-brand-900">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    """
    return layout("Med Solutions | Your Right Decision Ensures Your Best Medical Treatment", body, "/", flash_msg, error_msg)


def render_service_form(preselected: str = "appointment", error_msg: str = "") -> bytes:
    hospitals = [
        "Apollo Hospitals (Chennai / Kolkata / New Delhi)",
        "Manipal Hospitals (Bangalore / Kolkata)",
        "Narayana Health / Rabindranath Tagore Hospital",
        "CMC Vellore (Christian Medical College)",
        "Fortis Memorial Research Institute (Gurgaon)",
        "Medanta - The Medicity (Gurgaon)",
        "Tata Memorial / HCG Cancer Centre",
        "AIG Hospitals (Hyderabad)",
    ]
    checkboxes = "".join(
        f"""<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:border-accent-500 hover:bg-brand-50/40">
                <input type="checkbox" name="selected_services" value="{k}" {'checked' if k == preselected else ''} class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-800">
                <span class="text-sm font-semibold text-slate-800">{e(v)}</span>
            </label>"""
        for k, v in SERVICE_NAMES.items()
    )
    hosp_opts = "".join(f'<option value="{e(h)}">{e(h)}</option>' for h in hospitals)

    body = f"""
    <section class="py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">Online Patient Assistance Booking</span>
                <h1 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">Medical Assistance & Appointment Request Form</h1>
                <p class="mt-2 text-sm text-slate-600">Submit your patient details and medical reports below. An instant Tracking Code will be generated upon submission.</p>
            </div>

            <form action="/service-request" method="POST" enctype="multipart/form-data" class="med-card p-6 sm:p-10">
                <div class="border-b border-slate-100 pb-6">
                    <h2 class="font-heading text-lg font-bold text-slate-900">1. Patient Personal & Contact Information</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="sr-name" class="block text-sm font-semibold text-slate-800">Patient Full Name (As on Passport) <span class="text-red-600">*</span></label>
                            <input id="sr-name" name="patient_name" type="text" required placeholder="e.g. Md. Kamrul Islam" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                        </div>
                        <div>
                            <label for="sr-phone" class="block text-sm font-semibold text-slate-800">Mobile / WhatsApp Number <span class="text-red-600">*</span></label>
                            <input id="sr-phone" name="phone" type="tel" required placeholder="017XXXXXXXX" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sr-email" class="block text-sm font-semibold text-slate-800">Email Address (For VIL & Appointment Confirmation)</label>
                            <input id="sr-email" name="email" type="email" placeholder="patient@example.com" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none">
                        </div>
                    </div>
                </div>

                <fieldset class="mt-6 border-b border-slate-100 pb-6">
                    <legend class="font-heading text-lg font-bold text-slate-900">2. Select Required Assistance Services <span class="text-red-600">*</span></legend>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">{checkboxes}</div>
                </fieldset>

                <div class="mt-6 space-y-5">
                    <h2 class="font-heading text-lg font-bold text-slate-900">3. Preferred Hospital, Specialist & Medical Reports</h2>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="sr-hosp" class="block text-sm font-semibold text-slate-800">Preferred Hospital in India</label>
                            <select id="sr-hosp" name="preferred_hospital" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm">
                                <option value="">Recommend the Best Hospital Based on My Reports</option>
                                {hosp_opts}
                            </select>
                        </div>
                        <div>
                            <label for="sr-doc" class="block text-sm font-semibold text-slate-800">Preferred Specialist Doctor / Department</label>
                            <input id="sr-doc" name="preferred_doctor" type="text" placeholder="e.g. Dr. Devi Shetty / Cardiology" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="sr-file" class="block text-sm font-semibold text-slate-800">Upload Medical Reports & Prescriptions (PDF, JPG, PNG — Max 10 MB)</label>
                        <input id="sr-file" name="medical_report" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 w-full rounded-xl border border-dashed border-accent-400 bg-brand-50/40 p-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-brand-800 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white">
                    </div>

                    <div>
                        <label for="sr-details" class="block text-sm font-semibold text-slate-800">Clinical Condition & Special Requirements</label>
                        <textarea id="sr-details" name="details" rows="3" placeholder="Share patient age, current symptoms, number of accompanying attendants, or self-cooking accommodation preferences..." class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="cursor-pointer rounded-xl btn-brand px-7 py-3.5 text-sm font-bold text-white shadow-md ">
                        Submit Service Request
                    </button>
                </div>
            </form>
        </div>
    </section>
    """
    return layout("Service Request Form | Med Solutions", body, "/service-request", error_msg=error_msg)


def render_track(code: str = "", flash_msg: str = "", req=None, denied: bool = False, phone: str = "") -> bytes:
    code_clean = code.strip().upper()

    result_html = ""
    if code_clean and denied:
        result_html = f'<div role="alert" class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Please enter the registered phone number for tracking code <strong>{e(code_clean)}</strong>, or <a href="/login" class="font-bold underline">sign in</a> to view it.</div>'
    elif code_clean and not req:
        result_html = f'<div role="alert" class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">No service request found for tracking code <strong>{e(code_clean)}</strong>. Try demo code <code>MS-2026-DEMO1</code> or <code>MS-2026-8492A</code>.</div>'
    elif req:
        step_map = {"pending": 1, "processing": 2, "confirmed": 3, "completed": 4}
        cur_step = step_map.get(req["status"], 1)
        steps_ui = ""
        for s_num, s_lbl in [(1, "Pending"), (2, "Processing"), (3, "Confirmed"), (4, "Completed")]:
            cls = "border-emerald-600 bg-emerald-50 text-emerald-950" if cur_step >= s_num else "border-slate-200 bg-slate-50 text-slate-400"
            steps_ui += f'<li class="rounded-xl border p-3.5 text-center text-xs font-bold {cls}"><span class="block text-[11px] uppercase">Step {s_num}</span><span class="mt-1 block">{s_lbl}</span></li>'

        try:
            svcs = ", ".join(SERVICE_NAMES.get(s, s) for s in json.loads(req["selected_services"] or "[]"))
        except Exception:
            svcs = req["service_type"]

        result_html = f"""
        <div class="mt-8 border-t border-slate-100 pt-7">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tracking Code</span>
                    <h2 class="font-heading text-xl font-extrabold text-brand-900">{e(req["tracking_code"])}</h2>
                    <p class="text-sm text-slate-600">Patient: <strong>{e(req["patient_name"])}</strong> ({e(req["phone"])})</p>
                </div>
                {render_badge(req["status"])}
            </div>
            <ol class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">{steps_ui}</ol>
            <dl class="mt-6 grid gap-4 rounded-xl bg-slate-50 p-5 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Selected Assistance Services</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{e(svcs)}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Preferred Hospital & Specialist</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{e(req["preferred_hospital"] or "Under Clinical Review")} {("— " + e(req["preferred_doctor"])) if req["preferred_doctor"] else ""}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Visa & Appointment Progress</dt>
                    <dd class="mt-1.5 flex flex-wrap gap-2">{render_badge(req["visa_status"])} {render_badge(req["appointment_status"])}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Attached Medical Report</dt>
                    <dd class="mt-1 font-medium text-brand-900">{e(req["medical_report_name"] or "None attached")}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">Care Coordinator Note</dt>
                    <dd class="mt-1 text-slate-800">{e(req["admin_notes"] or "Our care coordinator is reviewing your submitted documents.")}</dd>
                </div>
            </dl>
        </div>"""

    body = f"""
    <section class="py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="med-card p-6 sm:p-8">
                <h1 class="font-heading text-2xl font-extrabold text-slate-900">Track Live Request Status</h1>
                <p class="mt-1 text-sm text-slate-600">Enter your Tracking Code and the registered phone number used for the request.</p>
                <form action="/track" method="GET" class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <input type="tel" name="phone" value="{e(phone)}" placeholder="Registered phone number" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    <input type="text" name="tracking_code" value="{e(code_clean)}" placeholder="MS-2026-DEMO1" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold uppercase focus:border-brand-700 focus:outline-none">
                    <button type="submit" class="cursor-pointer shrink-0 rounded-xl bg-brand-800 px-6 py-3 text-sm font-bold text-white hover:bg-brand-900">Track Status</button>
                </form>
                {result_html}
            </div>
        </div>
    </section>"""
    return layout("Track Request Status | Med Solutions", body, "/track", flash_msg=flash_msg)


def render_telemedicine(flash_msg: str = "", error_msg: str = "") -> bytes:
    depts = [
        "Cardiology & Cardiac Surgery",
        "Medical & Surgical Oncology (Cancer Care)",
        "Neurology & Neurosurgery",
        "Gastroenterology & Liver Transplant",
        "Nephrology & Kidney Transplant",
        "Orthopedics, Joint & Spine Surgery",
        "Gynecology, Fertility & IVF",
    ]
    dept_opts = "".join(f'<option value="{e(d)}">{e(d)}</option>' for d in depts)
    default_dt = (datetime.utcnow() + timedelta(days=2)).strftime("%Y-%m-%dT19:00")

    body = f"""
    <section class="py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 text-center">
                <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">Pre-Travel & Post-Return Video Consultation</span>
                <h1 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">Book a Telemedicine Session with Specialists in India</h1>
                <p class="mt-2 text-sm text-slate-600">Schedule a video conference with Indian doctors before travelling to discuss treatment options, or book a post-return follow-up session from Bangladesh.</p>
            </div>

            <form action="/telemedicine" method="POST" class="med-card p-6 sm:p-10">
                <fieldset class="mb-6">
                    <legend class="text-sm font-bold text-slate-900">Select Consultation Stage <span class="text-red-600">*</span></legend>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-brand-50/40">
                            <input type="radio" name="consultation_stage" value="pre_travel" checked class="mt-1">
                            <div>
                                <span class="block text-sm font-bold text-slate-900">Pre-Travel Evaluation</span>
                                <span class="block text-xs text-slate-600">Discuss reports, treatment plan & estimated budget before travelling to India</span>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 hover:bg-brand-50/40">
                            <input type="radio" name="consultation_stage" value="post_return" class="mt-1">
                            <div>
                                <span class="block text-sm font-bold text-slate-900">Post-Return Follow-Up</span>
                                <span class="block text-xs text-slate-600">Review recovery, lab tests & medication adjustments after returning home</span>
                            </div>
                        </label>
                    </div>
                </fieldset>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="tm-name" class="block text-sm font-semibold text-slate-800">Patient Full Name <span class="text-red-600">*</span></label>
                        <input id="tm-name" name="patient_name" type="text" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="tm-phone" class="block text-sm font-semibold text-slate-800">Mobile / WhatsApp Number <span class="text-red-600">*</span></label>
                        <input id="tm-phone" name="phone" type="tel" required placeholder="017XXXXXXXX" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="tm-dept" class="block text-sm font-semibold text-slate-800">Medical Department <span class="text-red-600">*</span></label>
                        <select id="tm-dept" name="department" class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm">{dept_opts}</select>
                    </div>
                    <div>
                        <label for="tm-doc" class="block text-sm font-semibold text-slate-800">Preferred Specialist / Hospital (Optional)</label>
                        <input id="tm-doc" name="preferred_doctor" type="text" placeholder="e.g. Dr. Devi Shetty / Apollo Team" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="tm-date" class="block text-sm font-semibold text-slate-800">Preferred Date & Time <span class="text-red-600">*</span></label>
                        <input id="tm-date" name="preferred_date" type="datetime-local" value="{default_dt}" required class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label for="tm-email" class="block text-sm font-semibold text-slate-800">Email Address (For Video Meeting Link)</label>
                        <input id="tm-email" name="email" type="email" placeholder="patient@example.com" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="tm-notes" class="block text-sm font-semibold text-slate-800">Symptoms Summary / Questions for the Doctor</label>
                        <textarea id="tm-notes" name="symptoms_notes" rows="3" class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                    </div>
                </div>

                <div class="mt-7 flex justify-end">
                    <button type="submit" class="cursor-pointer rounded-xl btn-brand px-7 py-3.5 text-sm font-bold text-white shadow-md ">
                        Schedule Video Consultation
                    </button>
                </div>
            </form>
        </div>
    </section>"""
    return layout("Telemedicine Booking | Med Solutions", body, "/telemedicine", flash_msg, error_msg)


def render_patient_portal(flash_msg: str = "") -> bytes:
    user = _ctx.user
    conn = get_db()
    all_reqs = conn.execute("SELECT * FROM service_requests ORDER BY id DESC").fetchall()
    all_consults = conn.execute("SELECT * FROM consultations ORDER BY id DESC").fetchall()
    conn.close()
    reqs = [r for r in all_reqs if owns_record(r, user)]
    consults = [c for c in all_consults if owns_record(c, user)]
    visa_open = sum(1 for r in reqs if r['visa_status'] not in ('not_requested', 'approved'))

    rows_html = ""
    for r in reqs:
        rows_html += f"""
        <tr>
            <td class="px-6 py-4 font-mono font-bold text-brand-900">
                <a href="/track?tracking_code={e(r['tracking_code'])}" class="underline hover:text-emerald-700">{e(r['tracking_code'])}</a>
            </td>
            <td class="px-6 py-4">
                <div class="font-bold text-slate-900">{e(r['patient_name'])}</div>
                <div class="text-xs text-slate-500">{e(r['preferred_hospital'] or 'Under Clinical Review')}</div>
            </td>
            <td class="px-6 py-4">{render_badge(r['visa_status'])}</td>
            <td class="px-6 py-4">{render_badge(r['appointment_status'])}</td>
            <td class="px-6 py-4">{render_badge(r['status'])}</td>
        </tr>"""

    consult_html = ""
    for c in consults:
        stage = "Post-Return Follow-Up" if c["consultation_stage"] == "post_return" else "Pre-Travel Specialist Consultation"
        join_btn = (
            f'<a href="{e(c["meeting_link"])}" target="_blank" rel="noopener" class="cursor-pointer rounded-xl bg-brand-800 px-4 py-2 text-xs font-bold text-white hover:bg-brand-900">Join Video Call</a>'
            if c["meeting_link"]
            else '<span class="text-xs text-slate-400">Link Pending</span>'
        )
        consult_html += f"""
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
            <div>
                <span class="text-xs font-bold text-brand-800">{stage}</span>
                <h3 class="font-bold text-slate-900">{e(c['patient_name'])} — {e(c['department'])}</h3>
                <p class="text-xs text-slate-500">Scheduled: {e(c['preferred_date'])} {('• ' + e(c['preferred_doctor'])) if c['preferred_doctor'] else ''}</p>
            </div>
            <div class="flex items-center gap-3">
                {render_badge(c['status'])}
                {join_btn}
            </div>
        </div>"""

    body = f"""
    <section class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-brand-950 via-brand-800 to-accent-600 p-6 text-white shadow-xl sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-5">
                    <div>
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold tracking-wide">Patient Portal</span>
                        <h1 class="mt-3 font-heading text-2xl font-extrabold sm:text-3xl">Welcome, {e(user['name'])}</h1>
                        <p class="mt-2 max-w-xl text-sm text-brand-100">Track every request, visa step and video consultation in one place.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="/service-request" class="cursor-pointer rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-brand-900 shadow-sm hover:bg-brand-50">+ New Service Request</a>
                        <a href="/telemedicine" class="cursor-pointer rounded-xl border border-white/30 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10">Book Telemedicine</a>
                    </div>
                </div>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="med-card p-5"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">My service requests</div><div class="mt-2 font-heading text-3xl font-extrabold text-slate-900">{len(reqs)}</div></div>
                <div class="med-card p-5"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Visa files in progress</div><div class="mt-2 font-heading text-3xl font-extrabold text-brand-800">{visa_open}</div></div>
                <div class="med-card p-5"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">Video consultations</div><div class="mt-2 font-heading text-3xl font-extrabold text-accent-700">{len(consults)}</div></div>
            </div>

            <div class="med-card mt-8 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-heading text-lg font-bold text-slate-900">Service Requests & Live Tracking</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                            <tr>
                                <th class="px-6 py-3.5">Tracking Code</th>
                                <th class="px-6 py-3.5">Patient & Hospital</th>
                                <th class="px-6 py-3.5">Visa Processing</th>
                                <th class="px-6 py-3.5">Doctor Appointment</th>
                                <th class="px-6 py-3.5">Overall Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">{rows_html}</tbody>
                    </table>
                </div>
            </div>

            <div class="med-card mt-8 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-heading text-lg font-bold text-slate-900">Telemedicine Video Consultations</h2></div>
                <div class="divide-y divide-slate-100 bg-white">{consult_html}</div>
            </div>
        </div>
    </section>"""
    return layout("Patient Portal Dashboard | Med Solutions", body, "/patient", flash_msg=flash_msg)


TESTIMONIAL_TEXT_FIELDS = ("patient_name", "district", "hospital_treated", "quote")


def testimonial_fields(form_data) -> dict:
    try:
        rating = min(5, max(1, int(form_data.get("rating", "5"))))
    except ValueError:
        rating = 5
    return {
        "patient_name": form_data.get("patient_name", "").strip()[:120],
        "district": form_data.get("district", "").strip()[:80],
        "hospital_treated": form_data.get("hospital_treated", "").strip()[:160],
        "quote": form_data.get("quote", "").strip()[:1000],
        "rating": rating,
    }


def admin_section(anchor: str, title: str, subtitle: str, inner: str) -> str:
    return (
        '<section id="' + anchor + '" class="scroll-mt-28">'
        '<div class="mb-4"><h2 class="font-heading text-xl font-extrabold text-slate-900">' + title + "</h2>"
        '<p class="mt-1 text-sm text-slate-500">' + subtitle + "</p></div>"
        + inner + "</section>"
    )


def render_admin_dashboard(flash_msg: str = "", panel: str = "admin") -> bytes:
    is_staff = panel == "staff"
    is_admin = not is_staff
    current_name = _ctx.user["name"] if getattr(_ctx, "user", None) is not None else ""
    role_label = ("STAFF" if is_staff else "ADMIN") + " (" + e(current_name) + ")"
    panel_title = "Care Coordinator Panel" if is_staff else "Admin Control Panel"
    panel_intro = (
        "Handle patient requests, visa files, appointments and telemedicine links."
        if is_staff
        else "Control the whole website: brand, homepage text, contact details, services, testimonials, users and every patient request."
    )
    cfg = get_settings()
    conn = get_db()
    reqs = conn.execute("SELECT * FROM service_requests ORDER BY id DESC").fetchall()
    consults = conn.execute("SELECT * FROM consultations ORDER BY id DESC").fetchall()
    services = conn.execute("SELECT * FROM services ORDER BY sort_order").fetchall()
    users = conn.execute("SELECT * FROM users ORDER BY id").fetchall()
    contacts = conn.execute("SELECT * FROM contacts ORDER BY id DESC").fetchall()
    testimonials = conn.execute("SELECT * FROM testimonials ORDER BY id DESC").fetchall()
    conn.close()

    total_req = len(reqs)
    pending_req = sum(1 for r in reqs if r["status"] == "pending")
    active_visas = sum(1 for r in reqs if r["visa_status"] in ("documents_received", "vil_issued", "ivac_submitted"))
    upcoming_tm = sum(1 for c in consults if c["status"] in ("pending", "scheduled"))
    active_services = sum(1 for s in services if s["is_active"])

    def sel_opts(options, current):
        out = ""
        for o in options:
            sel = " selected" if o == current else ""
            out += '<option value="' + str(o) + '"' + sel + ">" + str(o).replace("_", " ").title() + "</option>"
        return out

    input_cls = "mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-200"
    select_cls = "mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold"
    label_cls = "block text-xs font-bold uppercase tracking-wide text-slate-600"

    # 1. Patient requests
    req_cards = ""
    for r in reqs:
        req_cards += (
            '<form action="/admin/update-request" method="POST" class="med-card p-5">'
            '<input type="hidden" name="id" value="' + str(r["id"]) + '">'
            '<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">'
            "<div>"
            '<span class="font-mono text-sm font-extrabold text-brand-900">' + e(r["tracking_code"]) + "</span>"
            '<span class="mx-2 text-slate-300">|</span>'
            '<strong class="text-slate-900">' + e(r["patient_name"]) + "</strong>"
            '<span class="text-xs text-slate-500"> (' + e(r["phone"]) + ") • " + e(r["preferred_hospital"] or "Hospital TBD") + "</span>"
            "</div>" + render_badge(r["status"]) + "</div>"
            '<div class="mt-4 grid gap-3 sm:grid-cols-3">'
            '<div><label class="' + label_cls + '">Overall Status</label><select name="status" class="' + select_cls + '">'
            + sel_opts(["pending", "processing", "confirmed", "completed", "cancelled"], r["status"]) + "</select></div>"
            '<div><label class="' + label_cls + '">Visa Processing Status</label><select name="visa_status" class="' + select_cls + '">'
            + sel_opts(["not_requested", "documents_received", "vil_issued", "ivac_submitted", "approved"], r["visa_status"]) + "</select></div>"
            '<div><label class="' + label_cls + '">Doctor Appointment</label><select name="appointment_status" class="' + select_cls + '">'
            + sel_opts(["not_requested", "requested", "slot_confirmed", "completed"], r["appointment_status"]) + "</select></div>"
            "</div>"
            '<div class="mt-3 flex flex-col gap-3 sm:flex-row">'
            '<input type="text" name="admin_notes" value="' + e(r["admin_notes"] or "") + '" placeholder="Coordinator note for patient (e.g. VIL emailed, airport pickup scheduled)..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">'
            '<button type="submit" class="btn-brand shrink-0 cursor-pointer rounded-lg px-5 py-2 text-xs font-bold text-white">Update Status</button>'
            "</div></form>"
        )
    if not req_cards:
        req_cards = '<p class="med-card p-6 text-sm text-slate-500">No patient requests yet.</p>'

    # 2. Telemedicine
    tm_cards = ""
    for c in consults:
        stage = "Post-Return Follow-Up" if c["consultation_stage"] == "post_return" else "Pre-Travel Consultation"
        tm_cards += (
            '<form action="/admin/update-consultation" method="POST" class="med-card p-5">'
            '<input type="hidden" name="id" value="' + str(c["id"]) + '">'
            '<div class="grid items-end gap-4 sm:grid-cols-12">'
            '<div class="sm:col-span-4">'
            '<span class="text-xs font-bold text-brand-800">' + stage + "</span>"
            '<h3 class="font-bold text-slate-900">' + e(c["patient_name"]) + " (" + e(c["phone"]) + ")</h3>"
            '<p class="text-xs text-slate-500">' + e(c["department"]) + " • " + e(c["preferred_date"]) + "</p></div>"
            '<div class="sm:col-span-3"><label class="' + label_cls + '">Status</label><select name="status" class="' + select_cls + '">'
            + sel_opts(["pending", "scheduled", "completed", "cancelled"], c["status"]) + "</select></div>"
            '<div class="sm:col-span-3"><label class="' + label_cls + '">Video Meeting Link (Meet/Zoom)</label>'
            '<input type="url" name="meeting_link" value="' + e(c["meeting_link"] or "") + '" placeholder="https://meet.google.com/..." class="' + input_cls.replace("text-sm", "text-xs") + '"></div>'
            '<div class="sm:col-span-2"><button type="submit" class="btn-brand w-full cursor-pointer rounded-lg px-4 py-2 text-xs font-bold text-white">Save Link</button></div>'
            "</div></form>"
        )
    if not tm_cards:
        tm_cards = '<p class="med-card p-6 text-sm text-slate-500">No telemedicine bookings yet.</p>'

    # 3. Website settings (admin only)
    setting_groups = [
        ("Brand", [
            ("brand_name", "Brand name", "text"),
            ("brand_tagline", "Tagline under the name", "text"),
            ("announcement", "Top announcement banner (leave empty to hide)", "text"),
        ]),
        ("Homepage hero", [
            ("hero_badge", "Small badge text", "text"),
            ("hero_title", "Headline - first part", "text"),
            ("hero_highlight", "Headline - highlighted part", "text"),
        ]),
        ("Contact & social links", [
            ("helpline_phone", "24/7 helpline phone", "text"),
            ("support_email", "Support email", "email"),
            ("office_address", "Office address", "text"),
            ("social_facebook", "Facebook URL", "url"),
            ("social_youtube", "YouTube URL", "url"),
            ("social_linkedin", "LinkedIn URL", "url"),
        ]),
        ("Footer", [
            ("footer_about", "Footer description", "textarea"),
        ]),
    ]
    settings_html = ""
    for group_title, fields in setting_groups:
        inner = ""
        for key, label, kind in fields:
            value = e(cfg.get(key, ""))
            if kind == "textarea":
                control = '<textarea name="' + key + '" rows="3" class="' + input_cls + '">' + value + "</textarea>"
                span = "sm:col-span-2"
            else:
                control = '<input type="' + kind + '" name="' + key + '" value="' + value + '" class="' + input_cls + '">'
                span = "sm:col-span-2" if key in ("announcement", "office_address") else ""
            inner += '<div class="' + span + '"><label class="' + label_cls + '">' + e(label) + "</label>" + control + "</div>"
        settings_html += (
            '<div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">'
            '<h3 class="font-heading text-sm font-extrabold uppercase tracking-wider text-brand-900">' + group_title + "</h3>"
            '<div class="mt-4 grid gap-4 sm:grid-cols-2">' + inner + "</div></div>"
        )
    settings_form = (
        '<form action="/admin/update-settings" method="POST" class="med-card space-y-5 p-6">'
        + settings_html
        + '<div class="flex flex-wrap items-center justify-between gap-3">'
        '<p class="text-xs text-slate-500">Changes appear on the live website immediately after saving.</p>'
        '<button type="submit" class="btn-brand cursor-pointer rounded-xl px-6 py-2.5 text-sm font-bold text-white">Save Website Settings</button>'
        "</div></form>"
    )

    # 4. Services (admin only)
    cms_cards = ""
    for s in services:
        cms_cards += (
            '<form action="/admin/update-service" method="POST" class="med-card space-y-3 p-5">'
            '<input type="hidden" name="id" value="' + str(s["id"]) + '">'
            '<div class="flex items-center justify-between">'
            '<span class="text-xs font-bold uppercase text-brand-800">' + e(s["slug"]) + "</span>"
            '<label class="flex items-center gap-1.5 text-xs font-semibold"><input type="checkbox" name="is_active" value="1" '
            + ("checked" if s["is_active"] else "") + "> Active on Homepage</label></div>"
            '<input type="text" name="title" value="' + e(s["title"]) + '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">'
            '<textarea name="description" rows="2" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">' + e(s["description"]) + "</textarea>"
            '<button type="submit" class="cursor-pointer rounded-lg bg-slate-900 px-4 py-1.5 text-xs font-bold text-white hover:bg-slate-800">Save Service</button>'
            "</form>"
        )
    services_section = admin_section(
        "services", "Services",
        "Edit the six core services shown on the homepage. Turn a service off to hide it from visitors.",
        '<div class="grid gap-4 md:grid-cols-2">' + cms_cards + "</div>",
    )

    # 5. Testimonials (admin only)
    def testimonial_form(action: str, t, button_html: str) -> str:
        values = t or {"patient_name": "", "district": "", "hospital_treated": "", "quote": "", "rating": 5}
        rating_opts = "".join(
            '<option value="' + str(n) + '"' + (" selected" if int(values["rating"]) == n else "") + ">" + str(n) + " star" + ("s" if n != 1 else "") + "</option>"
            for n in range(1, 6)
        )
        hidden = '<input type="hidden" name="id" value="' + str(t["id"]) + '">' if t else ""
        return (
            '<form action="' + action + '" method="POST" class="med-card space-y-3 p-5">' + hidden
            + '<div class="grid gap-3 sm:grid-cols-2">'
            '<div><label class="' + label_cls + '">Patient name</label><input type="text" name="patient_name" required value="' + e(values["patient_name"]) + '" class="' + input_cls.replace("text-sm", "text-xs") + '"></div>'
            '<div><label class="' + label_cls + '">District</label><input type="text" name="district" required value="' + e(values["district"]) + '" class="' + input_cls.replace("text-sm", "text-xs") + '"></div>'
            '<div><label class="' + label_cls + '">Hospital treated</label><input type="text" name="hospital_treated" required value="' + e(values["hospital_treated"]) + '" class="' + input_cls.replace("text-sm", "text-xs") + '"></div>'
            '<div><label class="' + label_cls + '">Rating</label><select name="rating" class="' + select_cls + '">' + rating_opts + "</select></div>"
            "</div>"
            '<div><label class="' + label_cls + '">Quote</label><textarea name="quote" required rows="3" class="' + input_cls.replace("text-sm", "text-xs") + '">' + e(values["quote"]) + "</textarea></div>"
            + button_html + "</form>"
        )

    testimonial_cards = ""
    for t in testimonials:
        delete_btn = (
            '<div class="flex flex-wrap gap-2">'
            '<button type="submit" class="btn-brand cursor-pointer rounded-lg px-4 py-2 text-xs font-bold text-white">Save Testimonial</button>'
            '<button type="submit" formaction="/admin/delete-testimonial" onclick="return confirm(\'Delete this testimonial?\')" '
            'class="cursor-pointer rounded-lg border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-50">Delete</button>'
            "</div>"
        )
        testimonial_cards += testimonial_form("/admin/update-testimonial", t, delete_btn)
    add_btn = '<button type="submit" class="btn-brand cursor-pointer rounded-lg px-5 py-2 text-xs font-bold text-white">Add Testimonial</button>'
    testimonials_section = admin_section(
        "testimonials", "Testimonials",
        "Add, edit or remove patient stories shown in the homepage 'Trusted by Patients' section.",
        '<div class="grid gap-4 lg:grid-cols-2">' + testimonial_form("/admin/add-testimonial", None, add_btn) + testimonial_cards + "</div>",
    )

    # 6. Users & roles (admin only)
    user_rows = ""
    for u in users:
        user_rows += (
            "<tr>"
            '<td class="px-5 py-3 font-semibold text-slate-900">' + e(u["name"]) + "</td>"
            '<td class="px-5 py-3 text-xs text-slate-600">' + e(u["email"]) + " • " + e(u["phone"]) + "</td>"
            '<td class="px-5 py-3"><span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold uppercase text-brand-900">' + e(u["role"]) + "</span></td>"
            '<td class="px-5 py-3"><form action="/admin/update-role" method="POST" class="flex items-center gap-2">'
            '<input type="hidden" name="id" value="' + str(u["id"]) + '">'
            '<select name="role" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs">' + sel_opts(["patient", "staff", "admin"], u["role"]) + "</select>"
            '<button type="submit" class="btn-brand cursor-pointer rounded-lg px-3 py-1 text-xs font-bold text-white">Save Role</button>'
            "</form></td></tr>"
        )
    users_section = admin_section(
        "users", "Users & Roles",
        "Choose who is an admin, staff member or patient. Each role signs in to its own panel.",
        '<div class="med-card overflow-hidden"><div class="overflow-x-auto">'
        '<table class="min-w-full divide-y divide-slate-200 text-left text-sm">'
        '<thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600"><tr>'
        '<th class="px-5 py-3">Full name</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Update role</th>'
        "</tr></thead><tbody class=\"divide-y divide-slate-100\">" + user_rows + "</tbody></table></div></div>",
    )

    # 7. Inquiries (both panels)
    contact_rows = "".join(
        '<div class="border-b border-slate-100 py-3 last:border-0"><div class="text-sm font-bold text-slate-900">'
        + e(c["name"]) + " (" + e(c["phone"]) + ')</div><div class="text-xs text-slate-600">' + e(c["message"]) + "</div></div>"
        for c in contacts
    ) or '<p class="text-xs text-slate-500">No contact inquiries received yet.</p>'
    inquiries_section = admin_section(
        "inquiries", "Contact Inquiries",
        "Messages sent through the homepage contact form.",
        '<div class="med-card p-5">' + contact_rows + "</div>",
    )

    kpi_cards = [
        ("Total service requests", total_req, "text-slate-900"),
        ("Pending requests", pending_req, "text-accent-700"),
        ("Active visa files", active_visas, "text-brand-800"),
        ("Telemedicine queue", upcoming_tm, "text-emerald-700"),
    ]
    if is_admin:
        kpi_cards.append(("Active services on homepage", active_services, "text-brand-800"))
    kpi_html = "".join(
        '<div class="med-card p-5"><div class="text-xs font-bold uppercase tracking-wide text-slate-500">' + label
        + '</div><div class="mt-2 font-heading text-3xl font-extrabold ' + color + '">' + str(value) + "</div></div>"
        for label, value, color in kpi_cards
    )

    nav_items = [("overview", "Overview"), ("requests", "Patient Requests"), ("telemedicine", "Telemedicine")]
    if is_admin:
        nav_items += [("website", "Website Settings"), ("services", "Services"), ("testimonials", "Testimonials"), ("users", "Users & Roles")]
    nav_items += [("inquiries", "Contact Inquiries")]
    nav_links = "".join(
        '<a href="#' + key + '" class="block rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-brand-50 hover:text-brand-900">' + label + "</a>"
        for key, label in nav_items
    )

    requests_section = admin_section("requests", "1. Patient Requests, Visa & Appointments", "Update each patient's overall status, visa processing and appointment progress.", '<div class="space-y-4">' + req_cards + "</div>")
    telemedicine_section = admin_section("telemedicine", "2. Telemedicine Consultations", "Confirm consultation status and add the video meeting link.", '<div class="space-y-4">' + tm_cards + "</div>")

    website_section = ""
    if is_admin:
        website_section = admin_section(
            "website", "Website Settings",
            "Brand, homepage text, banner, contact details and social links. Changes go live on the website immediately.",
            settings_form,
        )

    body = (
        '<section class="bg-gradient-to-br from-brand-950 via-brand-800 to-accent-600 text-white">'
        '<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">'
        '<span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold tracking-wide">RBAC Role: ' + role_label + "</span>"
        '<h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight">' + panel_title + "</h1>"
        '<p class="mt-2 max-w-2xl text-sm text-brand-100">' + panel_intro + "</p>"
        '<div class="mt-5 flex flex-wrap gap-3">'
        '<a href="/" class="cursor-pointer rounded-xl bg-white px-4 py-2 text-xs font-bold text-brand-900 shadow-sm hover:bg-brand-50">View Live Website</a>'
        '<a href="/service-request" class="cursor-pointer rounded-xl border border-white/30 px-4 py-2 text-xs font-bold text-white hover:bg-white/10">New Service Request</a>'
        "</div></div></section>"
        '<section class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">'
        '<div class="grid gap-6 lg:grid-cols-12">'
        '<aside class="lg:col-span-3"><nav class="med-card sticky top-24 p-3" aria-label="Panel sections">'
        '<p class="px-3.5 pb-2 pt-1 text-[11px] font-bold uppercase tracking-widest text-slate-400">Navigate</p>'
        + nav_links + "</nav></aside>"
        '<div class="space-y-12 lg:col-span-9">'
        '<section id="overview" class="scroll-mt-28"><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-' + ("5" if is_admin else "4") + '">' + kpi_html + "</div></section>"
        + requests_section + telemedicine_section + website_section + (services_section + testimonials_section + users_section if is_admin else "") + inquiries_section
        + "</div></div></div></section>"
    )
    title = "Staff Panel | Med Solutions" if is_staff else "Admin Dashboard | Med Solutions"
    return layout(title, body, "/staff" if is_staff else "/admin", flash_msg=flash_msg)



BRAND_PALETTE = {
    "brand": {
        "50": "#F1F4FC", "100": "#E2E8F8", "200": "#C5D2F0", "300": "#98AEE3", "400": "#5F7FD0",
        "500": "#3A5BBE", "600": "#2D48A6", "700": "#263B8C", "800": "#243090", "900": "#1E2770", "950": "#151B4D",
    },
    "accent": {
        "50": "#EEF8FD", "100": "#D8F0FC", "200": "#B4E4FC", "300": "#8FCBEA", "400": "#60A8D8",
        "500": "#3F90C6", "600": "#2D76AA", "700": "#235E8A", "800": "#1D4D72", "900": "#183F5D", "950": "#0E2739",
    },
}
BRAND_PALETTE_JS = json.dumps(BRAND_PALETTE)


class MedSolutionsHandler(BaseHTTPRequestHandler):
    _token = ""
    _secure = False
    _had_cookie = False

    def log_message(self, format, *args) -> None:
        # Diagnostic: record whether the browser sent the session cookie (value is never logged).
        cookie_header = self.headers.get("Cookie", "") or ""
        has_cookie = "yes" if SESSION_COOKIE + "=" in cookie_header else "no"
        sys.stderr.write("%s - - [%s] %s cookie=%s\n" % (self.address_string(), self.log_date_time_string(), format % args, has_cookie))

    def _send_cookie(self) -> None:
        # Served inside a cross-site iframe in Arena, so SameSite=Lax cookies are dropped on POST.
        # SameSite=None requires Secure; Partitioned keeps it working under third-party cookie blocking.
        flags = "; Path=/; HttpOnly; Secure; SameSite=None; Partitioned"
        self.send_header("Set-Cookie", f"{SESSION_COOKIE}={self._token}{flags}")

    def _begin(self) -> None:
        token = None
        try:
            jar = SimpleCookie(self.headers.get("Cookie", ""))
            if SESSION_COOKIE in jar:
                token = jar[SESSION_COOKIE].value
        except Exception:
            token = None
        self._had_cookie = bool(token)
        with SESSIONS_LOCK:
            data = SESSIONS.get(token) if token else None
            if data is None:
                token = secrets.token_urlsafe(32)
                data = new_session_data()
                SESSIONS[token] = data
        self._token = token
        self._secure = self.headers.get("X-Forwarded-Proto", "").lower() == "https"
        _ctx.session = data
        _ctx.user = self._load_user(data["user_id"])

    def _load_user(self, user_id):
        if not user_id:
            return None
        conn = get_db()
        row = conn.execute("SELECT * FROM users WHERE id = ?", (user_id,)).fetchone()
        conn.close()
        return row

    def _rotate_session(self, user_id) -> None:
        with SESSIONS_LOCK:
            SESSIONS.pop(self._token, None)
            token = secrets.token_urlsafe(32)
            data = new_session_data(user_id)
            SESSIONS[token] = data
        self._token = token
        _ctx.session = data
        _ctx.user = self._load_user(user_id)

    def _handle_login(self, form_data) -> None:
        email = form_data.get("email", "").strip().lower()
        password = form_data.get("password", "")
        next_url = safe_next(form_data.get("next", ""))
        if login_locked(email):
            self._send_html(render_login(email=email, next_url=next_url,
                            error_msg="Too many failed sign-in attempts. Please wait 5 minutes and try again."), status=429)
            return
        conn = get_db()
        row = conn.execute("SELECT * FROM users WHERE LOWER(email) = ?", (email,)).fetchone()
        conn.close()
        if row is None or not verify_password(password, row["password_hash"]):
            record_login_failure(email)
            self._send_html(render_login(email=email, next_url=next_url,
                            error_msg="The provided credentials do not match our records."), status=422)
            return
        clear_login_failures(email)
        self._rotate_session(row["id"])
        self._redirect(landing_for(row, next_url))

    def _handle_register(self, form_data) -> None:
        name = form_data.get("name", "").strip()
        phone = form_data.get("phone", "").strip()
        email = form_data.get("email", "").strip().lower()
        password = form_data.get("password", "")
        confirm = form_data.get("password_confirmation", "")
        errors = []
        if len(name) < 2:
            errors.append("Please enter your full name (at least 2 characters).")
        if not re.fullmatch(r"(\+?880|0)?1[3-9]\d{8}", re.sub(r"[\s-]", "", phone)):
            errors.append("Please enter a valid Bangladeshi mobile number (e.g. 017XXXXXXXX).")
        if not re.fullmatch(r"[^@\s]+@[^@\s]+\.[^@\s]+", email):
            errors.append("Please enter a valid email address.")
        if len(password) < 8 or not re.search(r"[A-Za-z]", password) or not re.search(r"\d", password):
            errors.append("Password must be at least 8 characters and include letters and numbers.")
        if password != confirm:
            errors.append("Password confirmation does not match.")
        if not errors:
            conn = get_db()
            exists = conn.execute("SELECT 1 FROM users WHERE LOWER(email) = ?", (email,)).fetchone()
            conn.close()
            if exists:
                errors.append("An account with this email already exists.")
        if errors:
            self._send_html(render_register(name=name, phone=phone, email=email, error_msg=" ".join(errors)), status=422)
            return
        now = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
        conn = get_db()
        cur = conn.execute(
            "INSERT INTO users (name, email, phone, role, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?)",
            (name, email, phone, "patient", hash_password(password), now),
        )
        user_id = cur.lastrowid
        conn.commit()
        conn.close()
        self._rotate_session(user_id)
        msg = urllib.parse.quote("Welcome to Med Solutions! Your patient account has been created.")
        self._redirect(f"/patient?flash={msg}")

    def _handle_logout(self) -> None:
        self._rotate_session(None)
        msg = urllib.parse.quote("You have been signed out securely.")
        self._redirect(f"/?flash={msg}")

    def _handle_demo_switch(self, form_data) -> None:
        if not DEMO_ROLE_SWITCHER:
            self._send_html(render_forbidden("Demo role switching is disabled on this server."), status=404)
            return
        email = DEMO_ACCOUNTS.get(form_data.get("role", ""))
        if not email:
            self._redirect("/")
            return
        conn = get_db()
        row = conn.execute("SELECT * FROM users WHERE email = ?", (email,)).fetchone()
        conn.close()
        if row is None:
            self._send_html(render_forbidden("Demo account not found. Restart the preview to re-seed it."), status=404)
            return
        self._rotate_session(row["id"])
        msg = urllib.parse.quote(f"Demo mode: now signed in as {row['name']} ({row['role']}).")
        self._redirect(f"{panel_for(row)}?flash={msg}")

    def _send_html(self, content: bytes, status: int = 200, head_only: bool = False) -> None:
        self.send_response(status)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(content)))
        self.send_header("Cache-Control", "no-store")
        self._send_cookie()
        self.end_headers()
        if not head_only:
            self.wfile.write(content)

    def _send_file(self, file_path: str, content_type: str, head_only: bool = False) -> None:
        with open(file_path, "rb") as fh:
            data = fh.read()
        self.send_response(200)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(data)))
        self.send_header("Cache-Control", "public, max-age=3600")
        self.end_headers()
        if not head_only:
            self.wfile.write(data)

    def _redirect(self, location: str) -> None:
        self.send_response(303)
        self.send_header("Location", location)
        self._send_cookie()
        self.end_headers()

    def do_HEAD(self) -> None:
        self._handle_get(head_only=True)

    def do_GET(self) -> None:
        self._handle_get(head_only=False)

    def _handle_get(self, head_only: bool = False) -> None:
        self._begin()
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        qs = urllib.parse.parse_qs(parsed.query)
        flash = qs.get("flash", [""])[0]

        if path == "/" or path == "":
            self._send_html(render_home(flash_msg=flash), head_only=head_only)
        elif path == "/service-request":
            svc = qs.get("service", ["appointment"])[0]
            self._send_html(render_service_form(preselected=svc), head_only=head_only)
        elif path == "/track":
            code = qs.get("tracking_code", [""])[0].strip().upper()
            phone = qs.get("phone", [""])[0]
            row = None
            allowed = False
            denied = False
            if code:
                conn = get_db()
                row = conn.execute("SELECT * FROM service_requests WHERE UPPER(tracking_code) = ?", (code,)).fetchone()
                conn.close()
                if row is not None:
                    allowed = track_allowed(row, _ctx.user, phone, _ctx.session)
                    denied = not allowed
            self._send_html(
                render_track(code=code, flash_msg=flash, req=row if allowed else None, denied=denied, phone=phone),
                head_only=head_only,
            )
        elif path == "/telemedicine":
            self._send_html(render_telemedicine(flash_msg=flash), head_only=head_only)
        elif path == "/login":
            if _ctx.user is not None:
                self._redirect(panel_for(_ctx.user))
            else:
                self._send_html(render_login(next_url=safe_next(qs.get("next", [""])[0])), head_only=head_only)
        elif path == "/register":
            if _ctx.user is not None:
                self._redirect(panel_for(_ctx.user))
            else:
                self._send_html(render_register(), head_only=head_only)
        elif path == "/patient":
            if _ctx.user is None:
                self._redirect("/login?next=/patient")
            else:
                self._send_html(render_patient_portal(flash_msg=flash), head_only=head_only)
        elif path in ("/admin", "/staff"):
            wanted = "admin" if path == "/admin" else "staff"
            if _ctx.user is None:
                self._redirect(f"/login?next={path}")
            elif _ctx.user["role"] != wanted:
                msg = urllib.parse.quote("That panel is not available for your account. Showing your own panel.")
                self._redirect(f"{panel_for(_ctx.user)}?flash={msg}")
            else:
                self._send_html(render_admin_dashboard(flash_msg=flash, panel=wanted), head_only=head_only)
        elif path == "/images/med-solutions-logo.jpeg":
            self._send_file(LOGO_PATH, "image/jpeg", head_only=head_only)
        elif path == "/up":
            self._send_html(b"OK", head_only=head_only)
        else:
            self._send_html(render_home(), status=200, head_only=head_only)

    def do_POST(self) -> None:
        self._begin()
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        ctype = self.headers.get("Content-Type", "")
        length = int(self.headers.get("Content-Length", "0"))
        body_bytes = self.rfile.read(min(length, 15 * 1024 * 1024))

        form_data = {}
        multi_services = []
        uploaded_filename = None
        uploaded_saved_path = None

        if "multipart/form-data" in ctype:
            header_bytes = f"Content-Type: {ctype}\r\nMIME-Version: 1.0\r\n\r\n".encode("utf-8")
            msg = BytesParser(policy=email.policy.default).parsebytes(header_bytes + body_bytes)
            for part in msg.iter_parts():
                cd = part.get("Content-Disposition", "")
                if "form-data" not in cd:
                    continue
                name = part.get_param("name", header="Content-Disposition")
                filename = part.get_filename()
                payload = part.get_payload(decode=True) or b""
                if filename:
                    raw_name = os.path.basename(filename)
                    if raw_name and payload:
                        safe_name = re.sub(r"[^a-zA-Z0-9._-]", "_", raw_name)
                        uploaded_filename = raw_name
                        uploaded_saved_path = os.path.join(UPLOAD_DIR, f"{int(datetime.utcnow().timestamp())}_{safe_name}")
                        with open(uploaded_saved_path, "wb") as f:
                            f.write(payload)
                elif name:
                    val = payload.decode("utf-8", errors="replace")
                    if name in ("selected_services", "selected_services[]"):
                        multi_services.append(val)
                    form_data[name] = val
        else:
            raw = body_bytes.decode("utf-8", errors="replace")
            parsed_body = urllib.parse.parse_qs(raw, keep_blank_values=True)
            for k, v in parsed_body.items():
                form_data[k] = v[0]
            multi_services = parsed_body.get("selected_services", [])

        if form_data.get("_csrf", "") != _ctx.session["csrf"]:
            if not self._had_cookie:
                self._send_html(render_forbidden(
                    "Your browser did not send your session cookie, so the form could not be verified. "
                    "Open this site in a new browser tab (not inside a frame), allow cookies for it, then try again.",
                    title="Browser Blocked Cookies"), status=403)
            else:
                self._send_html(render_forbidden("Your form session has expired. Please go back, refresh the page and try again.", title="Session Expired"), status=403)
            return

        if path == "/login":
            self._handle_login(form_data)
            return
        if path == "/register":
            self._handle_register(form_data)
            return
        if path == "/logout":
            self._handle_logout()
            return
        if path == "/demo/switch-role":
            self._handle_demo_switch(form_data)
            return
        if path.startswith("/admin/"):
            if _ctx.user is None:
                self._redirect("/login?next=/admin")
                return
            if not can_manage(_ctx.user):
                self._send_html(render_forbidden(), status=403)
                return
            admin_only = path in ("/admin/update-role", "/admin/update-service", "/admin/update-settings", "/admin/add-testimonial", "/admin/update-testimonial", "/admin/delete-testimonial")
            if admin_only and _ctx.user["role"] != "admin":
                self._send_html(render_forbidden("Only administrators can change user roles or website content."), status=403)
                return

        now = datetime.utcnow().strftime("%Y-%m-%d %H:%M")

        if path == "/service-request":
            name = form_data.get("patient_name", "").strip()
            phone = form_data.get("phone", "").strip()
            if not name or not phone or not multi_services:
                self._send_html(render_service_form(error_msg="Please enter the patient's full name, phone number, and select at least one assistance service."), status=422)
                return

            code = "MS-2026-" + "".join(random.choices(string.ascii_uppercase + string.digits, k=5))
            has_visa = "visa_processing" in multi_services
            has_appt = "appointment" in multi_services

            conn = get_db()
            conn.execute(
                """INSERT INTO service_requests
                   (tracking_code, user_id, patient_name, phone, email, service_type, selected_services, preferred_hospital,
                    preferred_doctor, details, medical_report_path, medical_report_name, status, visa_status, appointment_status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)""",
                (
                    code,
                    current_user_id(),
                    name,
                    phone,
                    form_data.get("email", "").strip(),
                    multi_services[0],
                    json.dumps(multi_services),
                    form_data.get("preferred_hospital", "").strip(),
                    form_data.get("preferred_doctor", "").strip(),
                    form_data.get("details", "").strip(),
                    uploaded_saved_path,
                    uploaded_filename,
                    "documents_received" if has_visa else "not_requested",
                    "requested" if has_appt else "not_requested",
                    now,
                ),
            )
            conn.commit()
            conn.close()
            _ctx.session["granted_codes"].append(code)
            msg = urllib.parse.quote(f"Your service request has been submitted! Your Tracking Code is: {code}")
            self._redirect(f"/track?tracking_code={code}&flash={msg}")

        elif path == "/telemedicine":
            name = form_data.get("patient_name", "").strip()
            phone = form_data.get("phone", "").strip()
            dt = form_data.get("preferred_date", "").strip()
            if not name or not phone or not dt:
                self._send_html(render_telemedicine(error_msg="Please provide the patient's full name, phone number, and preferred date."), status=422)
                return

            conn = get_db()
            conn.execute(
                """INSERT INTO consultations
                   (user_id, patient_name, phone, email, consultation_stage, department, preferred_doctor, preferred_date, symptoms_notes, status, created_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)""",
                (
                    current_user_id(),
                    name,
                    phone,
                    form_data.get("email", "").strip(),
                    form_data.get("consultation_stage", "pre_travel"),
                    form_data.get("department", ""),
                    form_data.get("preferred_doctor", "").strip(),
                    dt.replace("T", " "),
                    form_data.get("symptoms_notes", "").strip(),
                    now,
                ),
            )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Your telemedicine video consultation request has been submitted successfully!")
            self._redirect(f"/telemedicine?flash={msg}")

        elif path == "/contact":
            name = form_data.get("name", "").strip()
            phone = form_data.get("phone", "").strip()
            message = form_data.get("message", "").strip()
            if name and phone and message:
                conn = get_db()
                conn.execute(
                    "INSERT INTO contacts (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                    (name, form_data.get("email", ""), phone, form_data.get("subject", ""), message, now),
                )
                conn.commit()
                conn.close()
            msg = urllib.parse.quote("Thank you! Your message has been received. Our care coordinator will reach out shortly.")
            self._redirect(f"/?flash={msg}")

        elif path == "/admin/update-request":
            conn = get_db()
            conn.execute(
                """UPDATE service_requests
                   SET status = ?, visa_status = ?, appointment_status = ?, admin_notes = ?
                   WHERE id = ?""",
                (
                    form_data.get("status", "pending"),
                    form_data.get("visa_status", "not_requested"),
                    form_data.get("appointment_status", "not_requested"),
                    form_data.get("admin_notes", "").strip(),
                    int(form_data.get("id", "0")),
                ),
            )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Patient service request status updated successfully.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}")

        elif path == "/admin/update-consultation":
            conn = get_db()
            conn.execute(
                "UPDATE consultations SET status = ?, meeting_link = ? WHERE id = ?",
                (
                    form_data.get("status", "pending"),
                    form_data.get("meeting_link", "").strip(),
                    int(form_data.get("id", "0")),
                ),
            )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Telemedicine meeting link and status saved.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}")

        elif path == "/admin/update-service":
            conn = get_db()
            conn.execute(
                "UPDATE services SET title = ?, description = ?, is_active = ? WHERE id = ?",
                (
                    form_data.get("title", "").strip(),
                    form_data.get("description", "").strip(),
                    1 if form_data.get("is_active") == "1" else 0,
                    int(form_data.get("id", "0")),
                ),
            )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Service content updated successfully.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}")

        elif path == "/admin/update-settings":
            conn = get_db()
            for key, default in SETTING_DEFAULTS.items():
                if key not in form_data:
                    continue
                value = form_data.get(key, "").strip()[:500]
                if key.startswith("social_") and value and not value.startswith(("http://", "https://")):
                    continue
                if key in ("brand_name", "helpline_phone", "support_email", "hero_title", "hero_highlight") and not value:
                    value = default
                conn.execute(
                    "INSERT INTO site_settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value",
                    (key, value),
                )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Website settings saved. Changes are now live on the website.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}#website")

        elif path in ("/admin/add-testimonial", "/admin/update-testimonial"):
            fields = testimonial_fields(form_data)
            if not all(fields[k] for k in TESTIMONIAL_TEXT_FIELDS):
                msg = urllib.parse.quote("Please fill in the patient name, district, hospital and quote.")
            else:
                conn = get_db()
                if path == "/admin/add-testimonial":
                    conn.execute(
                        "INSERT INTO testimonials (patient_name, district, hospital_treated, quote, rating) VALUES (?, ?, ?, ?, ?)",
                        (fields["patient_name"], fields["district"], fields["hospital_treated"], fields["quote"], fields["rating"]),
                    )
                    msg = urllib.parse.quote("New testimonial added to the homepage.")
                else:
                    conn.execute(
                        "UPDATE testimonials SET patient_name = ?, district = ?, hospital_treated = ?, quote = ?, rating = ? WHERE id = ?",
                        (fields["patient_name"], fields["district"], fields["hospital_treated"], fields["quote"], fields["rating"], int(form_data.get("id", "0"))),
                    )
                    msg = urllib.parse.quote("Testimonial updated.")
                conn.commit()
                conn.close()
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}#testimonials")

        elif path == "/admin/delete-testimonial":
            conn = get_db()
            conn.execute("DELETE FROM testimonials WHERE id = ?", (int(form_data.get("id", "0")),))
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("Testimonial removed from the homepage.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}#testimonials")

        elif path == "/admin/update-role":
            conn = get_db()
            conn.execute(
                "UPDATE users SET role = ? WHERE id = ?",
                (form_data.get("role", "patient"), int(form_data.get("id", "0"))),
            )
            conn.commit()
            conn.close()
            msg = urllib.parse.quote("User role updated successfully.")
            self._redirect(f"{panel_for(_ctx.user)}?flash={msg}")

        else:
            self._redirect("/")


if __name__ == "__main__":
    init_db()
    port = int(os.environ.get("PORT", "8000"))
    server = ThreadingHTTPServer(("0.0.0.0", port), MedSolutionsHandler)
    print(f"Med Solutions Interactive Preview (English) listening on http://0.0.0.0:{port}", flush=True)
    server.serve_forever()
