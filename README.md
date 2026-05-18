# Movify – AI Video Generator

Multi-user web application for generating videos from text prompts and images using AI models (Wan 2.6 Fast, LTX Video 2.0, Kling 2.6 Turbo) via Fal.ai.

## Tech Stack

- **Server**: LiteSpeed Web Server (LSWS) / Apache
- **Backend**: PHP 8.x (PDO + MySQL)
- **Frontend**: HTML5, Tailwind CSS (CDN), Vanilla JavaScript
- **Database**: MySQL 8.x
- **AI Integration**: Fal.ai API (Luma, Runway, Stable Video models)

## Features

- User registration with 6-digit email verification code
- Login / logout with secure session management
- Password reset via token link (15 min expiry)
- Credit-based system (100 free credits on signup)
- Dynamic credit cost calculator (model × resolution × duration)
- Async video generation with real-time polling
- Personal video gallery with download
- CSRF protection, prepared statements, XSS escaping
- LiteSpeed-optimized `.htaccess` with compression & caching

## Setup

### 1. Database

```sql
mysql -u root -p < database/schema.sql
```

### 2. Configuration

Copy and edit the environment variables used in `config.php`:

| Variable | Description |
|---|---|
| `DB_HOST` | MySQL host (default: `localhost`) |
| `DB_NAME` | Database name (default: `ai_video_generator`) |
| `DB_USER` | MySQL user |
| `DB_PASS` | MySQL password |
| `APP_URL` | Public URL of the app |
| `FAL_AI_API_KEY` | Fal.ai API key |
| `SMTP_HOST` | SMTP server |
| `SMTP_PORT` | SMTP port |
| `SMTP_USER` | SMTP username |
| `SMTP_PASS` | SMTP password |
| `SMTP_FROM` | Sender email address |

### 3. Deploy

Upload all files to your LiteSpeed web server document root. Ensure:
- `mod_rewrite` is enabled
- `fpm-php` is active
- `uploads/` directory is writable by the web server

## Credit System

| Model | Key | Cost per second |
|---|---|---|
| Wan 2.6 Fast (Eco) | `wan_fast` | 5 credits/s |
| LTX Video 2.0 (Ultra-Rapid) | `ltx_video` | 4 credits/s |
| Kling 2.6 Turbo (Pro) | `kling_turbo` | 8 credits/s |

**Formula:** `Cost = base_credit_cost × duration (seconds)`

**Example:** Kling Turbo (8 cr/s) × 10s = **80 credits**

## File Structure

```
movify/
├── config.php              # DB connection, constants, CSRF
├── database/schema.sql     # MySQL schema
├── includes/
│   ├── auth.php            # Registration, login, verification, reset
│   ├── credits_helper.php  # Credit calculation & management
│   ├── functions.php       # General helpers (h, redirect, flash)
│   ├── header.php          # HTML head + Tailwind config
│   └── footer.php          # Footer + app.js include
├── assets/
│   ├── css/style.css       # Custom styles
│   └── js/app.js           # Client-side credit calc + polling
├── index.php               # Landing page
├── register.php            # Signup form
├── verify.php              # Email verification
├── login.php               # Login form
├── forgot_password.php     # Request password reset
├── reset_password.php      # Set new password via token
├── logout.php              # Destroy session
├── dashboard.php           # Main UI: controls + gallery
├── generate_video.php      # API: submit video job to Fal.ai
├── check_status.php        # API: poll Fal.ai job status
├── uploads/                # User image uploads
├── .htaccess               # LiteSpeed/Apache config
└── .gitignore
```

## License

Proprietary – All rights reserved.
