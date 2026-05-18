<?php
/**
 * Movify – Credit Calculation & Management
 *
 * Formula: Cost = ModelBase × ResolutionMultiplier × DurationMultiplier  (rounded up)
 */

require_once __DIR__ . '/../config.php';

// ── Base credits per model ──────────────────────────────────────────
function model_base_cost(string $model): int
{
    $costs = [
        'runway'       => 5,
        'luma'         => 4,
        'stable_video' => 3,
    ];
    return $costs[strtolower($model)] ?? 4;
}

// ── Resolution multiplier ───────────────────────────────────────────
function resolution_multiplier(string $resolution): float
{
    $map = [
        '720p'  => 1.0,
        '1080p' => 1.5,
        '4k'    => 2.0,
    ];
    return $map[strtolower($resolution)] ?? 1.0;
}

// ── Duration multiplier ─────────────────────────────────────────────
function duration_multiplier(int $seconds): float
{
    $map = [
        4  => 1.0,
        6  => 1.3,
        8  => 1.6,
        10 => 2.0,
    ];
    return $map[$seconds] ?? 1.0;
}

// ── Calculate total cost (rounded up) ───────────────────────────────
function calculate_credits(string $model, string $resolution, int $duration): int
{
    $cost = model_base_cost($model)
          * resolution_multiplier($resolution)
          * duration_multiplier($duration);

    return (int)ceil($cost);
}

// ── Check if user can afford ────────────────────────────────────────
function can_afford(PDO $pdo, int $userId, int $cost): bool
{
    $stmt = $pdo->prepare('SELECT credits FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row && (int)$row['credits'] >= $cost;
}

// ── Deduct credits ──────────────────────────────────────────────────
function deduct_credits(PDO $pdo, int $userId, int $amount): bool
{
    $stmt = $pdo->prepare(
        'UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?'
    );
    $stmt->execute([$amount, $userId, $amount]);
    return $stmt->rowCount() > 0;
}

// ── Refund credits (e.g. on generation failure) ─────────────────────
function refund_credits(PDO $pdo, int $userId, int $amount): void
{
    $pdo->prepare('UPDATE users SET credits = credits + ? WHERE id = ?')
        ->execute([$amount, $userId]);
}

// ── Get current balance ─────────────────────────────────────────────
function get_credits(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT credits FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['credits'] : 0;
}
