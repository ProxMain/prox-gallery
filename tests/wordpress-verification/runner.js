(() => {
  const config = JSON.parse(document.getElementById('pg-verify-config').textContent);
  const button = document.getElementById('pg-run');
  const progress = document.getElementById('pg-progress');
  const results = document.getElementById('pg-results');
  const checks = [];
  const request = async (action, payload = {}, nonce = config.actions[action]) => {
    const response = await fetch(config.url, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action, _ajax_nonce: nonce, ...payload }) });
    let json;
    try { json = await response.json(); }
    catch { throw new Error(`Non-JSON response: HTTP ${response.status}`); }
    return { status: response.status, ...json };
  };
  const fixture = async (operation, payload = {}) => {
    const response = await request('pg_verify', { operation, ...payload }, config.nonce);
    if (!response.success) throw new Error(response.data?.message || 'Fixture operation failed');
    return response.data;
  };
  const check = (name, passed) => checks.push({ name, passed });
  const show = (extra = {}) => { results.textContent = JSON.stringify({ checks, ...extra }, null, 2); };
  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      for (let batch = 0; batch < 12; batch++) {
        progress.textContent = `Preparing real WordPress media: batch ${batch + 1}/12`;
        const seeded = await fixture('seed');
        if (seeded.seeded === 600) break;
      }
      progress.textContent = 'Running WordPress integration checks…';
      const synchronous = await fixture('checks');
      checks.push(...synchronous.checks);
      show(synchronous);
      const invalidNonce = await request('prox_gallery_openai_generate_story', { attachment_id: 1 }, 'invalid');
      check('Real AJAX rejects an invalid nonce with HTTP 403', invalidNonce.status === 403);
      const missing = await request('prox_gallery_openai_generate_story', { attachment_id: 999999 });
      check('Real AJAX rejects a non-editable attachment with HTTP 403', missing.status === 403);
      progress.textContent = 'Running concurrent HTTP requests in batches of 12…';
      const state = await fixture('concurrency_start');
      const galleries = await Promise.all(Array.from({ length: 12 }, () => fixture('concurrent_gallery')));
      check('All concurrent gallery requests return successfully', galleries.length === 12);
      await Promise.all(Array.from({ length: 12 }, (_, index) => fixture('concurrent_media', { index })));
      for (const event_type of ['gallery_visit', 'image_view']) {
        const tracked = await Promise.all(Array.from({ length: 12 }, () => request('prox_gallery_track_event', { event_type, gallery_id: state.gallery, image_id: state.image })));
        check(`All concurrent ${event_type} requests return successfully`, tracked.every(row => row.success));
      }
      const generated = await Promise.all(Array.from({ length: 12 }, () => request('prox_gallery_openai_generate_story', { attachment_id: state.image })));
      check('Concurrent AI AJAX responses are 3 successes and 9 HTTP 429 quota rejections', generated.filter(row => row.success).length === 3 && generated.filter(row => row.status === 429).length === 9);
      const complete = await fixture('concurrency_finish');
      checks.push(...complete.checks);
      const report = { environment: synchronous.environment, page: synchronous.page, ...complete, checks, completed_at: new Date().toISOString() };
      show(report);
      progress.textContent = checks.every(row => row.passed) ? `PASS · ${checks.length} WordPress checks` : `FAIL · ${checks.filter(row => !row.passed).length} failed checks`;
    } catch (error) {
      progress.textContent = `ERROR · ${error.message}`;
      show({ error: error.message });
    }
  });
})();
