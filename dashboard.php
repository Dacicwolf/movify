<?php
/**
 * Movify – Main Dashboard
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/credits_helper.php';

require_login();

$user = current_user($pdo);
if (!$user) {
    logout_user();
    redirect('login.php');
}

// ── Fetch user's videos (latest first) ──────────────────────────────
$stmt = $pdo->prepare(
    'SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([$user['id']]);
$videos = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Top Bar -->
<nav class="bg-dark-800 border-b border-gray-700 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="dashboard.php" class="text-xl font-bold bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">
            Movify
        </a>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 bg-dark-900 px-4 py-2 rounded-lg border border-gray-700">
                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                </svg>
                <span id="credit-balance" class="font-semibold text-yellow-300"><?= (int)$user['credits'] ?></span>
                <span class="text-gray-400 text-sm">credite</span>
            </div>
            <span class="text-gray-400 text-sm hidden sm:inline"><?= h($user['email']) ?></span>
            <a href="logout.php" class="text-gray-400 hover:text-red-400 transition text-sm">Ieșire</a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-3 gap-8">

        <!-- ════════════════ LEFT: Controls ════════════════ -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-dark-800 rounded-2xl p-6 border border-gray-700">
                <h2 class="text-lg font-semibold mb-5">Generează Video</h2>

                <form id="generate-form" class="space-y-4" enctype="multipart/form-data">

                    <!-- Prompt -->
                    <div>
                        <label for="prompt" class="block text-sm font-medium text-gray-300 mb-1">Prompt (text)</label>
                        <textarea id="prompt" name="prompt" rows="3"
                                  class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none text-white placeholder-gray-500 resize-none"
                                  placeholder="Descrie video-ul dorit..."></textarea>
                    </div>

                    <!-- Image upload -->
                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-300 mb-1">Imagine (opțional)</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                               class="w-full text-sm text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary-600 file:text-white file:cursor-pointer hover:file:bg-primary-700">
                    </div>

                    <!-- Model -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-300 mb-1">Alege Modelul AI</label>
                        <select id="model" name="model"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="wan_fast" data-cost="5">Wan 2.6 Fast (Eco - Mișcări Fluide) – 5 cr/s</option>
                            <option value="ltx_video" data-cost="4">LTX Video 2.0 (Ultra-Rapid) – 4 cr/s</option>
                            <option value="kling_turbo" data-cost="8">Kling 2.6 Turbo (Pro - Realism Uman) – 8 cr/s</option>
                        </select>
                    </div>

                    <!-- Format -->
                    <div>
                        <label for="format" class="block text-sm font-medium text-gray-300 mb-1">Format</label>
                        <select id="format" name="format"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="movie">Movie (16:9)</option>
                            <option value="portrait">Portrait (9:16)</option>
                        </select>
                    </div>

                    <!-- Resolution -->
                    <div>
                        <label for="resolution" class="block text-sm font-medium text-gray-300 mb-1">Rezoluție</label>
                        <select id="resolution" name="resolution"
                                class="w-full px-4 py-3 rounded-lg bg-dark-900 border border-gray-600 focus:border-primary-500 outline-none text-white">
                            <option value="720p">720p (x1)</option>
                            <option value="1080p">1080p (x1.5)</option>
                            <option value="4k">4K (x2)</option>
                        </select>
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Durată</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="4" checked class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">4s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="6" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">6s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="8" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">8s</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="duration" value="10" class="peer hidden">
                                <div class="text-center py-2 rounded-lg border border-gray-600 peer-checked:border-primary-500 peer-checked:bg-primary-600/20 text-sm transition">10s</div>
                            </label>
                        </div>
                    </div>

                    <!-- Cost preview -->
                    <div class="flex items-center justify-between p-3 rounded-lg bg-dark-900 border border-gray-700">
                        <span class="text-gray-400 text-sm">Cost estimat:</span>
                        <span id="cost-preview" class="text-yellow-300 font-bold text-lg">4</span>
                        <span class="text-gray-400 text-sm">credite</span>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="btn-generate"
                            class="w-full py-3 rounded-xl bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-semibold text-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Generează Video
                    </button>
                </form>

                <!-- Processing overlay -->
                <div id="processing" class="hidden mt-4 p-4 rounded-lg bg-dark-900 border border-gray-700 text-center">
                    <div class="inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p class="text-gray-300 text-sm" id="processing-text">Se generează video-ul...</p>
                    <div class="w-full bg-gray-700 rounded-full h-2 mt-3">
                        <div id="progress-bar" class="bg-primary-500 h-2 rounded-full transition-all duration-500" style="width: 5%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════ RIGHT: Gallery ════════════════ -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-semibold mb-4">Galeria mea</h2>

            <?php if (empty($videos)): ?>
                <div class="bg-dark-800 rounded-2xl p-12 border border-gray-700 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9A2.25 2.25 0 0 0 13.5 5.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"/>
                    </svg>
                    <p class="text-gray-400">Nu ai generat niciun video încă.</p>
                    <p class="text-gray-500 text-sm mt-1">Completează formularul din stânga pentru a începe.</p>
                </div>
            <?php else: ?>
                <div id="video-grid" class="grid sm:grid-cols-2 gap-4">
                    <?php foreach ($videos as $v): ?>
                        <div class="bg-dark-800 rounded-xl overflow-hidden border border-gray-700 hover:border-gray-600 transition group">
                            <?php if ($v['status'] === 'completed' && $v['video_url']): ?>
                                <video class="w-full aspect-video bg-black" controls preload="metadata">
                                    <source src="<?= h($v['video_url']) ?>" type="video/mp4">
                                </video>
                            <?php elseif ($v['status'] === 'processing'): ?>
                                <div class="w-full aspect-video bg-dark-900 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="inline-block w-8 h-8 border-4 border-primary-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                        <p class="text-gray-400 text-sm">Se procesează...</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-full aspect-video bg-dark-900 flex items-center justify-center">
                                    <p class="text-red-400 text-sm">Generare eșuată</p>
                                </div>
                            <?php endif; ?>

                            <div class="p-3 space-y-2">
                                <?php if ($v['prompt']): ?>
                                    <p class="text-sm text-gray-300 truncate" title="<?= h($v['prompt']) ?>">
                                        <?= h($v['prompt']) ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="text-xs px-2 py-0.5 rounded bg-primary-600/20 text-primary-300"><?= h(ucfirst($v['model_used'])) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300"><?= h($v['resolution']) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300"><?= (int)$v['duration'] ?>s</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-yellow-600/20 text-yellow-300"><?= (int)$v['credits_deducted'] ?> credite</span>
                                </div>
                                <?php if ($v['status'] === 'completed' && $v['video_url']): ?>
                                    <a href="<?= h($v['video_url']) ?>" download
                                       class="inline-flex items-center gap-1 text-xs text-primary-400 hover:text-primary-300 transition mt-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                        </svg>
                                        Download
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
