<?php
/**
 * Movify – Video Generation Endpoint (AJAX)
 *
 * Accepts POST with: prompt, model, resolution, duration, format, image (file).
 * Submits a job to Fal.ai queue and returns the queue_id for polling.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits_helper.php';

header('Content-Type: application/json');

// ── Auth guard ──────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Neautorizat.'], 401);
}

if (!is_post()) {
    json_response(['ok' => false, 'error' => 'Metodă nepermisă.'], 405);
}

$userId = (int)$_SESSION['user_id'];

// ── Collect & validate params ───────────────────────────────────────
$prompt     = post('prompt');
$model      = post('model');
$resolution = post('resolution');
$duration   = (int)post('duration');
$format     = post('format');

$allowedModels      = array_keys($MODELS_CONFIG);
$allowedResolutions = ['720p', '1080p', '4k'];
$allowedDurations   = [4, 6, 8, 10];
$allowedFormats     = ['movie', 'portrait'];

if (!$prompt && empty($_FILES['image'])) {
    json_response(['ok' => false, 'error' => 'Furnizează un prompt sau o imagine.'], 400);
}
if (!in_array($model, $allowedModels, true)) {
    json_response(['ok' => false, 'error' => 'Model invalid.'], 400);
}
if (!in_array($resolution, $allowedResolutions, true)) {
    json_response(['ok' => false, 'error' => 'Rezoluție invalidă.'], 400);
}
if (!in_array($duration, $allowedDurations, true)) {
    json_response(['ok' => false, 'error' => 'Durată invalidă.'], 400);
}
if (!in_array($format, $allowedFormats, true)) {
    json_response(['ok' => false, 'error' => 'Format invalid.'], 400);
}

// ── Resolve model config ────────────────────────────────────────────
$modelConfig = get_model_config($model);
if (!$modelConfig) {
    json_response(['ok' => false, 'error' => 'Configurație model lipsă.'], 400);
}

// ── Credit check (base_cost_per_second × duration) ──────────────────
$cost = calculate_credits($model, $duration);

if (!can_afford($pdo, $userId, $cost)) {
    json_response(['ok' => false, 'error' => 'Credite insuficiente. Cost: ' . $cost], 400);
}

// ── Handle image upload ─────────────────────────────────────────────
$imagePath = null;
$imageUrl  = null;

if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        json_response(['ok' => false, 'error' => 'Imaginea depășește limita de 50 MB.'], 400);
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        json_response(['ok' => false, 'error' => 'Tip de fișier neacceptat (JPEG, PNG, WebP).'], 400);
    }

    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename  = uniqid('img_', true) . '.' . $ext;
    $imagePath = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $imagePath)) {
        json_response(['ok' => false, 'error' => 'Eroare la salvarea imaginii.'], 500);
    }

    $imageUrl = APP_URL . '/uploads/' . $filename;
}

// ── Deduct credits ──────────────────────────────────────────────────
if (!deduct_credits($pdo, $userId, $cost)) {
    json_response(['ok' => false, 'error' => 'Credite insuficiente (concurrency).'], 400);
}

// ── Resolve Fal.ai endpoint (text-to-video or image-to-video) ───────
$hasImage = !empty($imageUrl);
$endpoint = $hasImage
    ? ($modelConfig['api_endpoint_i2v'] ?? $modelConfig['api_endpoint'])
    : $modelConfig['api_endpoint'];

// ── Build API payload ───────────────────────────────────────────────
$payload = [
    'prompt'       => $prompt,
    'duration'     => $duration,
    'aspect_ratio' => ($format === 'movie') ? '16:9' : '9:16',
];

if ($hasImage) {
    $payload['image_url'] = $imageUrl;
}

// ── Submit to Fal.ai queue ──────────────────────────────────────────
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Authorization: Key ' . FAL_AI_API_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$apiResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError   = curl_error($ch);
curl_close($ch);

if ($curlError || $httpCode >= 400) {
    refund_credits($pdo, $userId, $cost);
    error_log("Fal.ai error [{$httpCode}]: {$curlError} – {$apiResponse}");
    json_response(['ok' => false, 'error' => 'Eroare la generarea video. Creditele au fost returnate.'], 502);
}

$apiData = json_decode($apiResponse, true);
$queueId = $apiData['request_id'] ?? $apiData['id'] ?? null;

if (!$queueId) {
    refund_credits($pdo, $userId, $cost);
    json_response(['ok' => false, 'error' => 'Răspuns API neașteptat.'], 502);
}

// ── Save video record (status = processing) ─────────────────────────
$stmt = $pdo->prepare(
    'INSERT INTO videos (user_id, prompt, image_path, model_used, resolution, duration, format, video_url, credits_deducted, status, queue_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $userId,
    $prompt,
    $imagePath,
    $model,
    $resolution,
    $duration,
    $format,
    '',         // video_url filled after completion
    $cost,
    'processing',
    $queueId,
]);

$videoId = (int)$pdo->lastInsertId();

json_response([
    'ok'        => true,
    'video_id'  => $videoId,
    'queue_id'  => $queueId,
    'cost'      => $cost,
    'credits'   => get_credits($pdo, $userId),
    'message'   => 'Video-ul este în curs de generare...',
]);
