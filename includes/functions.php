<?php
/**
 * Movify – General Helpers
 */

function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function url(string $path): string
{
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

function redirect(string $url): void
{
    if (!preg_match('#^https?://#i', $url)) {
        $url = url($url);
    }
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

function get_param(string $key, string $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

/**
 * Upload a local file to Fal.ai CDN and return the public URL.
 */
function upload_to_fal_cdn(string $localPath, string $contentType): ?string
{
    $fileName = basename($localPath);

    // Step 1: Initiate upload
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://rest.alpha.fal.ai/storage/upload/initiate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'file_name'    => $fileName,
            'content_type' => $contentType,
        ]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Key ' . FAL_AI_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $initResp = curl_exec($ch);
    $initCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($initCode !== 200 || !$initResp) {
        error_log("Fal CDN initiate failed [{$initCode}]: {$initResp}");
        return null;
    }

    $initData  = json_decode($initResp, true);
    $fileUrl   = $initData['file_url'] ?? null;
    $uploadUrl = $initData['upload_url'] ?? null;

    if (!$fileUrl || !$uploadUrl) {
        error_log("Fal CDN initiate missing URLs: {$initResp}");
        return null;
    }

    // Step 2: PUT the file data to the signed upload URL
    $fileData = file_get_contents($localPath);
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL            => $uploadUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $fileData,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: ' . $contentType,
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $uploadResp = curl_exec($ch2);
    $uploadCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($uploadCode < 200 || $uploadCode >= 300) {
        error_log("Fal CDN upload failed [{$uploadCode}]: {$uploadResp}");
        return null;
    }

    return $fileUrl;
}
