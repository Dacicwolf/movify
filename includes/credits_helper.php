<?php
/**
 * Movify – Credit Calculation & Management
 *
 * Formula: Cost = base_credit_cost (per second) × duration (seconds)
 * The base_credit_cost comes from $MODELS_CONFIG in config.php.
 */

require_once __DIR__ . '/../config.php';

// ── Get base cost per second from model config ──────────────────────
function model_base_cost(string $model): int
{
    global $MODELS_CONFIG;
    return $MODELS_CONFIG[$model]['base_credit_cost'] ?? 5;
}

// ── Calculate total cost: base_cost_per_second × duration ───────────
function calculate_credits(string $model, int $duration): int
{
    return model_base_cost($model) * $duration;
}

// ── Get model config (endpoint, name, cost) ─────────────────────────
function get_model_config(string $model): ?array
{
    global $MODELS_CONFIG;
    return $MODELS_CONFIG[$model] ?? null;
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
