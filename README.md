# Movify – AI Video Generator

Multi-user web application for generating videos from text prompts and images using AI models (Luma Dream Machine, Runway Gen-3, Stable Video).

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

| Model | Base Cost |
|---|---|
| Runway Gen-3 | 5 credits |
| Luma Dream Machine | 4 credits |
| Stable Video | 3 credits |

**Multipliers:**
- Resolution: 720p (×1), 1080p (×1.5), 4K (×2)
- Duration: 4s (×1), 6s (×1.3), 8s (×1.6), 10s (×2)

**Formula:** `Cost = Base × Resolution × Duration` (rounded up)

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
