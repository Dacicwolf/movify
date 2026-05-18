<?php
/**
 * Movify – Landing Page
 * Redirects authenticated users to the dashboard.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$pageTitle = 'Bine ai venit';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-lg text-center space-y-8">
        <!-- Logo / Brand -->
        <div>
            <h1 class="text-5xl font-bold bg-gradient-to-r from-primary-400 to-purple-400 bg-clip-text text-transparent">
                Movify
            </h1>
            <p class="mt-3 text-gray-400 text-lg">Generator video AI – din text și imagine</p>
        </div>

        <!-- Hero illustration placeholder -->
        <div class="mx-auto w-64 h-40 rounded-2xl bg-dark-800 flex items-center justify-center border border-gray-700">
            <svg class="w-16 h-16 text-primary-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 10.5l4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9A2.25 2.25 0 0 0 13.5 5.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25z"/>
            </svg>
        </div>

        <!-- CTA buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="login.php"
               class="px-8 py-3 rounded-xl bg-primary-600 hover:bg-primary-700 text-white font-semibold transition">
                Autentificare
            </a>
            <a href="register.php"
               class="px-8 py-3 rounded-xl border border-gray-600 hover:border-primary-500 text-gray-300 hover:text-white font-semibold transition">
                Creează cont
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
