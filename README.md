# Ergovision

**Real-time posture monitoring for desk work and study sessions.**

Ergovision is a web application that uses your webcam to detect sitting posture in real time, gives gentle alerts when you slouch, and logs session metrics so you can track progress over time. Video is processed for analysis only — **no recordings are stored**.

---

## What Ergovision Does

- **Live posture monitoring** — Uses pose estimation to track how you sit during a session.
- **Calibration** — Learns your “good” sitting baseline in a few seconds.
- **Smart alerts** — Notifications when posture drifts, without constant nagging.
- **Session dashboard** — View efficiency score, slouch time, alerts, and history.
- **Trends** — Filter data over 3, 7, or 30 days to spot patterns.
- **Privacy-first** — Webcam frames are not saved; only telemetry (scores, durations, alerts) is stored.

---

## How It Works

1. **Sign in** and complete onboarding.
2. **Open the Monitor** and allow camera access.
3. **Calibrate** while sitting the way you want to sit.
4. **Work as usual** — Ergovision checks posture continuously.
5. **Review your dashboard** — Session chunks (~30 seconds each) roll up into daily stats and trends.

---

## Privacy & Data

- **No video storage** — Frames are processed to extract body landmarks; visual data is not kept on the server.
- **Telemetry only** — Scores, slouch duration, session length, and alert counts are saved to your account.
- **Your data, your account** — Session history is tied to your login; admins can export anonymized research data where applicable.

See the in-app **Privacy Policy** and **Terms of Service** for full details.

---

## Tech Overview

| Layer | Technology |
|--------|------------|
| Frontend | Vue 3, Inertia.js, Tailwind CSS |
| Backend | Laravel 12, PHP 8.2+ |
| Database | SQLite (dev) / MySQL or PostgreSQL (production) |
| AI service | Python (Flask) + MediaPipe for posture inference |
| Auth | Email/password, Google sign-in (optional) |

---

## Requirements (Self-Hosted / Development)

| Tool | Version |
|------|---------|
| PHP | 8.2 or newer |
| Composer | 2.x |
| Node.js | 20.19+ (see `.nvmrc`) |
| npm | 10+ |
| Python | 3.9+ (for local AI microservice) |
| Database | SQLite, MySQL, or PostgreSQL |

---

## Quick Start (Developers)

### 1. Clone and install

```bash
git clone <repository-url>
cd AI-Posture-Corrector

composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database

```bash
# SQLite (default in .env.example)
touch database/database.sqlite
php artisan migrate
```

For MySQL/PostgreSQL, set `DB_*` in `.env`, then run `php artisan migrate`.

### 3. Frontend

```bash
npm install
npm run dev
```

Production build:

```bash
npm run build
```

### 4. Run the app

```bash
php artisan serve
```

Visit `http://localhost:8000`.

### 5. AI microservice (optional, local)

```bash
cd python
pip install -r requirements.txt   # if present
python server.py
```

Point the frontend at your AI endpoint via `.env`:

```env
VITE_AI_ENDPOINT=http://127.0.0.1:5000/predict
```

---

## Environment Variables (Common)

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Public URL of the Laravel app |
| `APP_NAME` | Application name (shown in UI) |
| `DB_*` | Database connection |
| `VITE_APP_NAME` | Frontend display name |
| `VITE_AI_ENDPOINT` | URL of the posture AI `/predict` endpoint |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Google OAuth (optional) |

---

## Project Structure

```
app/                 Laravel application (API, auth, dashboard)
resources/js/        Vue pages and components (Inertia)
python/              AI microservice (MediaPipe / inference)
database/migrations/ Schema for users, posture chunks, feedback
public/              Web root, built assets, static files
```

---

## Support & Contact

For questions about deployment, accounts, or research participation, contact the **Ergovision team** at FAITH Colleges.

---

## License

Proprietary — developed as part of ergonomic research at FAITH Colleges.  
Unauthorized redistribution or commercial use without permission is not allowed.

---

*Ergovision — sit better, track progress, keep your data private.*
