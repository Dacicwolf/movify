<?php
/**
 * Movify – Polling Endpoint
 *
 * GET ?video_id=123
 * Checks the Fal.ai queue status for a given video job.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits_helper.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    json_response(['ok' => false, 'error' => 'Neautorizat.'], 401);
}

$userId  = (int)$_SESSION['user_id'];
$videoId = (int)get_param('video_id');

if (!$videoId) {
    json_response(['ok' => false, 'error' => 'video_id lipsă.'], 400);
}

// ── Fetch video record ──────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT id, queue_id, status, video_url, model_used, credits_deducted FROM videos WHERE id = ? AND user_id = ?'
);
$stmt->execute([$videoId, $userId]);
$video = $stmt->fetch();

if (!$video) {
    json_response(['ok' => false, 'error' => 'Video negăsit.'], 404);
}

// Already completed or failed
if ($video['status'] !== 'processing') {
    json_response([
        'ok'        => true,
        'status'    => $video['status'],
        'video_url' => $video['video_url'],
    ]);
}

// ── Resolve Fal.ai status endpoint from model config ────────────────
$modelConfig = get_model_config($video['model_used']);
$apiEndpoint = $modelConfig['api_endpoint'] ?? 'https://queue.fal.run/fal-ai/wan/v2.1/text-to-video';
$statusUrl   = $apiEndpoint . '/requests/' . $video['queue_id'] . '/status';

// ── Poll Fal.ai ─────────────────────────────────────────────────────
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $statusUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Key ' . FAL_AI_API_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 15,
]);

$apiResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400) {
    json_response([
        'ok'     => true,
        'status' => 'processing',
        'detail' => 'Încă se procesează...',
    ]);
}

$data   = json_decode($apiResponse, true);
$status = strtolower($data['status'] ?? 'IN_QUEUE');

// ── Handle completed ────────────────────────────────────────────────
if ($status === 'completed' || $status === 'succeeded') {
    $videoUrl = $data['video']['url']
             ?? $data['result']['video']['url']
             ?? $data['output']['video']['url']
             ?? '';

    if ($videoUrl) {
        $pdo->prepare('UPDATE videos SET status = ?, video_url = ? WHERE id = ?')
            ->execute(['completed', $videoUrl, $videoId]);

        json_response([
            'ok'        => true,
            'status'    => 'completed',
            'video_url' => $videoUrl,
            'credits'   => get_credits($pdo, $userId),
        ]);
    }
}

// ── Handle failed ───────────────────────────────────────────────────
if ($status === 'failed' || $status === 'error') {
    $pdo->prepare('UPDATE videos SET status = ? WHERE id = ?')
        ->execute(['failed', $videoId]);

    refund_credits($pdo, $userId, (int)$video['credits_deducted']);

    json_response([
        'ok'      => true,
        'status'  => 'failed',
        'error'   => $data['error'] ?? 'Generarea a eșuat. Creditele au fost returnate.',
        'credits' => get_credits($pdo, $userId),
    ]);
}

// ── Still processing ────────────────────────────────────────────────
$progress = $data['progress'] ?? null;

json_response([
    'ok'       => true,
    'status'   => 'processing',
    'progress' => $progress,
    'detail'   => 'Video-ul este în curs de generare...',
]);
