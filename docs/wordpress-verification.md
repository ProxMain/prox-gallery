# WordPress verification — 22 October 2026

Candidate: **0.2.0-rc.2**. Tested on a free temporary TasteWP site created without a TasteWP account. The generated WordPress administrator was used only to install and exercise the plugin. Test contact: `marcel@prox-web.nl`.

| Component | Verified environment |
| --- | --- |
| WordPress | 7.1.3 |
| PHP | 8.5.12 |
| Database | MySQL 8.0.46 |
| Theme | Twenty Twenty-Three |
| Temporary public page | https://waitinglinen.s3-tastewp.com/gallery-verification/ |

## Results

All **25 real WordPress checks passed** on the final installable ZIP. The separate administrator-only fixture lives in `tests/wordpress-verification/`; it is excluded from the production package. The final machine-readable report is delivered as `wordpress-verification.json`. Its completion timestamp is `2026-10-22T14:19:26.339Z`.

- All 600 fixture attachments remain indexed and appear through the real media listing service. Media reads leave storage unchanged. Cleanup removes a known stale row and reports the committed counts.
- A forced competing SQL write, differing only in letter case, proves that the binary compare-and-swap retries against the new value. Core option-cache invalidation also passed.
- Twelve concurrent HTTP gallery creates retain every row with unique IDs. Twelve concurrent media updates retain all changed rows. Twelve concurrent public visits and twelve concurrent image views produce the exact expected increments.
- With temporary quotas of 3 per user and site, 12 simultaneous AI AJAX requests produce exactly 3 successful responses and 9 HTTP 429 rejections. Only 3 requests reach the intercepted provider transport; both stored counters equal 3.
- UTC rollover, timeout accounting and zero-limit disabling passed. Invalid nonces and a non-editable attachment return HTTP 403 through the real AJAX boundary. WordPress's real capability mapper denies an author changing an administrator's attachment. API-key encryption and response redaction passed.

The AI transport was intercepted using WordPress's `pre_http_request` filter, with a deliberately fake key. **No paid provider calls were made.** This verifies plugin dispatch and quota enforcement, not live provider availability or output quality.

## Manual browser checks

- Production ZIP installation, activation and replacement update succeeded; the admin application loaded within WordPress without captured JavaScript errors.
- A JPEG was uploaded through the native WordPress media picker, bringing the library to **601** images. Its edited title, alternative text and description were saved and the image was found through search.
- A gallery was created and renamed through the new interface. The uploaded image was added to an existing gallery, which then showed seven assignments on both the admin screen and public page. Explicit page publication completed successfully.
- The public lightbox opened the seventh image, wrapped to `1 / 7`, closed with Escape, restored focus to the original image link and restored scrolling.
- The editor displayed actual usage. A site quota of zero was saved through Settings; the editor then showed `0 of 0` and disabled Generate. Defaults were restored to 20 per user and 100 per site.

The requested mobile viewport override did not consistently apply to the hidden WordPress tab: it reported a width of 781 px instead of 390 px. Therefore this run does **not** claim a verified 390 px WordPress layout. The earlier standalone preview's responsive checks remain separate evidence.

## Local checks and limits

Local verification passed: 24 PHP tests / 51 assertions, 15 UI tests, TypeScript checking, production asset build, portable-preview build, and syntax validation of 117 PHP files. CI additionally runs the isolated suite and dependency audits on PHP 8.1 and 8.4 with Node 24.

The separate WordPress PHPUnit suite was not run as a full suite: it requires its own test library and disposable database. Browser/database verification above exercises real WordPress through a separate fixture. Persistent Redis/Memcached drop-ins, multisite, other themes and hosts, very large libraries, sustained load and live OpenAI behavior were not certified. Collections still use complete option payloads and complete list responses; removing eviction is not a claim of unlimited scalability. Public tracking remains a best-effort metric and throttle.

After testing, the fake provider key was cleared, default quotas were restored and the verification helper was deactivated. Prox Gallery remains active on the temporary site, which expires automatically. No production site was changed.
