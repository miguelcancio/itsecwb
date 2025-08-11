Dorm Reservation (PHP + Supabase)

Overview
- Roles: admin, manager, customer
- Secure auth, RBAC, CSRF, validation, logging

Environment
- Copy .env.example to .env and set:
  - SUPABASE_URL
  - SUPABASE_KEY
  - APP_DEBUG=false

Supabase Tables (suggested)
- users: id (uuid), email (text, unique), password_hash (text), role (text), failed_attempts (int4), locked_until (timestamptz), last_login_at (timestamptz), last_login_ip (text), password_changed_at (timestamptz), is_disabled (bool), created_at (timestamptz), updated_at (timestamptz)
- password_history: id (uuid), user_id (uuid, fk), password_hash (text), changed_at (timestamptz)
- reservations: id (uuid), user_id (uuid, fk), room (text), date_from (date), date_to (date), status (text), created_at (timestamptz), updated_at (timestamptz)

Install
1) composer install
2) Ensure PHP >= 8.0 with curl and openssl
3) Serve: php -S localhost:8000 -t public

Security Notes
- Passwords hashed server-side using password_hash
- Password policy: min 12 chars with upper/lower/digit/special
- Login lockout: 5 attempts, 15 minutes
- CSRF tokens on all state-changing forms
- Logs restricted to admin in logs/app.log


