<?php
/**
 * Movify – Global Configuration
 *
 * Database credentials, session settings, and app constants.
 * For production, load sensitive values from environment variables.
 */

// ── Error Reporting (disable display in production) ─────────────────
ini_set('display_errors', (getenv('APP_ENV') === 'production') ? 0 : 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ── Session ─────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', (getenv('APP_ENV') === 'production') ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ── Database ────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ai_video_generator');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection error. Please try again later.');
}

// ── App Constants ───────────────────────────────────────────────────
define('APP_NAME', 'Movify');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50 MB

// ── AI API ──────────────────────────────────────────────────────────
define('FAL_AI_API_KEY', getenv('FAL_AI_API_KEY') ?: '');
define('FAL_AI_BASE_URL', 'https://queue.fal.run');

// ── AI Model Configuration ──────────────────────────────────────────
// Each model: api_endpoint (Fal.ai queue URL), base_credit_cost (per second)
$MODELS_CONFIG = [
    'wan_fast' => [
        'name'             => 'Wan 2.1 Fast',
        'api_endpoint'     => 'https://queue.fal.run/fal-ai/wan-t2v',
        'api_endpoint_i2v' => 'https://queue.fal.run/fal-ai/wan-i2v',
        'base_credit_cost' => 5,
        'fps_options'      => [8, 12, 16, 20, 24],
        'fps_default'      => 16,
    ],
    'ltx_video' => [
        'name'             => 'LTX Video',
        'api_endpoint'     => 'https://queue.fal.run/fal-ai/ltx-video',
        'api_endpoint_i2v' => 'https://queue.fal.run/fal-ai/ltx-video',
        'base_credit_cost' => 4,
        'fps_options'      => [],
        'fps_default'      => null,
    ],
    'kling_turbo' => [
        'name'             => 'Kling 1.6 Standard',
        'api_endpoint'     => 'https://queue.fal.run/fal-ai/kling-video/v1.6/standard/text-to-video',
        'api_endpoint_i2v' => 'https://queue.fal.run/fal-ai/kling-video/v1.6/standard/image-to-video',
        'base_credit_cost' => 8,
        'fps_options'      => [],
        'fps_default'      => null,
    ],
];

// ── SMTP (for PHPMailer) ────────────────────────────────────────────
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@movify.app');
define('SMTP_FROM_NAME', 'Movify');

// ── CSRF helper ─────────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
