/**
 * Movify – Frontend Logic
 * Dynamic credit calculation, form submission, polling.
 */

document.addEventListener('DOMContentLoaded', () => {
    const form         = document.getElementById('generate-form');
    const costPreview  = document.getElementById('cost-preview');
    const btnGenerate  = document.getElementById('btn-generate');
    const processing   = document.getElementById('processing');
    const processingTx = document.getElementById('processing-text');
    const progressBar  = document.getElementById('progress-bar');
    const creditBadge  = document.getElementById('credit-balance');

    if (!form) return; // not on dashboard

    // ── Credit Calculation (mirrors PHP: base_cost_per_second × duration) ──
    // Read data-cost from <option> so adding new models requires no JS change
    const MODEL_BASE = { wan_fast: 5, ltx_video: 4, kling_turbo: 8 };

    function calcCost() {
        const modelSel   = form.querySelector('#model');
        const model      = modelSel.value;
        const duration   = parseInt(form.querySelector('input[name="duration"]:checked')?.value || 4);

        // Prefer data-cost attribute, fall back to lookup table
        const selectedOpt = modelSel.options[modelSel.selectedIndex];
        const baseCost    = parseInt(selectedOpt?.dataset.cost) || MODEL_BASE[model] || 5;

        const cost = baseCost * duration;

        if (costPreview) costPreview.textContent = cost;
        return cost;
    }

    // ── FPS Section toggle based on model ─────────────────────────────
    const fpsSection = document.getElementById('fps-section');
    const fpsSelect  = document.getElementById('fps');

    function updateFpsSection() {
        const modelSel   = form.querySelector('#model');
        const selectedOpt = modelSel.options[modelSel.selectedIndex];
        const fpsData     = selectedOpt?.dataset.fps || '';
        const fpsDefault  = selectedOpt?.dataset.fpsDefault || '';

        if (!fpsData || !fpsSection || !fpsSelect) {
            if (fpsSection) fpsSection.classList.add('hidden');
            return;
        }

        const fpsValues = fpsData.split(',').map(Number).filter(Boolean);
        fpsSelect.innerHTML = '';
        fpsValues.forEach(fps => {
            const opt = document.createElement('option');
            opt.value = fps;
            opt.textContent = fps + ' FPS' + (fps == fpsDefault ? ' (implicit)' : '');
            if (fps == fpsDefault) opt.selected = true;
            fpsSelect.appendChild(opt);
        });

        fpsSection.classList.remove('hidden');
        updateFpsDurationHint();
    }

    // ── FPS duration hint ────────────────────────────────────────────
    const fpsDurationHint = document.getElementById('fps-duration-hint');
    const NUM_FRAMES = 81; // Wan default frame count

    function updateFpsDurationHint() {
        if (!fpsDurationHint || !fpsSelect || !fpsSection || fpsSection.classList.contains('hidden')) {
            if (fpsDurationHint) fpsDurationHint.textContent = '';
            return;
        }
        const fps = parseInt(fpsSelect.value) || 16;
        const realDuration = (NUM_FRAMES / fps).toFixed(1);
        fpsDurationHint.textContent = `Durată reală estimată: ~${realDuration}s (${NUM_FRAMES} cadre la ${fps} FPS)`;
    }

    // Recalculate on every control change
    ['#model', '#resolution', '#format'].forEach(sel => {
        const el = form.querySelector(sel);
        if (el) el.addEventListener('change', calcCost);
    });

    form.querySelector('#model')?.addEventListener('change', updateFpsSection);
    fpsSelect?.addEventListener('change', updateFpsDurationHint);

    form.querySelectorAll('input[name="duration"]').forEach(r => {
        r.addEventListener('change', calcCost);
    });

    calcCost(); // initial
    updateFpsSection(); // initial FPS state

    // ── Form Submission ─────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fd = new FormData(form);
        fd.append('duration', form.querySelector('input[name="duration"]:checked')?.value || '4');

        btnGenerate.disabled = true;
        processing.classList.remove('hidden');
        progressBar.style.width = '5%';
        processingTx.textContent = 'Se trimite cererea...';

        try {
            const res  = await fetch('generate_video.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.ok) {
                alert(data.error || 'Eroare la generare.');
                btnGenerate.disabled = false;
                processing.classList.add('hidden');
                return;
            }

            // Update credits badge
            if (creditBadge && data.credits !== undefined) {
                creditBadge.textContent = data.credits;
            }

            processingTx.textContent = 'Video-ul este în curs de generare...';
            progressBar.style.width = '15%';

            // Start polling
            pollStatus(data.video_id);

        } catch (err) {
            console.error(err);
            alert('Eroare de rețea. Reîncearcă.');
            btnGenerate.disabled = false;
            processing.classList.add('hidden');
        }
    });

    // ── Polling ─────────────────────────────────────────────────────
    let pollCount = 0;

    function pollStatus(videoId) {
        const interval = setInterval(async () => {
            pollCount++;

            // Simulate progress bar
            const fakeProgress = Math.min(15 + pollCount * 3, 90);
            progressBar.style.width = fakeProgress + '%';

            try {
                const res  = await fetch(`check_status.php?video_id=${videoId}`);
                const data = await res.json();

                if (data.status === 'completed') {
                    clearInterval(interval);
                    progressBar.style.width = '100%';
                    processingTx.textContent = 'Video generat cu succes!';

                    if (data.credits !== undefined && creditBadge) {
                        creditBadge.textContent = data.credits;
                    }

                    // Reload gallery after a brief pause
                    setTimeout(() => location.reload(), 1500);
                }

                if (data.status === 'failed') {
                    clearInterval(interval);
                    processingTx.textContent = data.error || 'Generarea a eșuat.';
                    progressBar.style.width = '0%';
                    progressBar.classList.replace('bg-primary-500', 'bg-red-500');

                    if (data.credits !== undefined && creditBadge) {
                        creditBadge.textContent = data.credits;
                    }

                    setTimeout(() => {
                        processing.classList.add('hidden');
                        btnGenerate.disabled = false;
                        progressBar.classList.replace('bg-red-500', 'bg-primary-500');
                        pollCount = 0;
                    }, 3000);
                }

                // Update progress text if API provides it
                if (data.progress !== null && data.progress !== undefined) {
                    processingTx.textContent = `Progres: ${Math.round(data.progress * 100)}%`;
                }

            } catch (err) {
                console.error('Poll error:', err);
            }
        }, 5000); // poll every 5 seconds
    }
});
