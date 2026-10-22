# Staging acceptance

Run on a backed-up, disposable WordPress installation matching production's PHP version, theme and plugin set. Local isolated tests do not cover these integrations.

1. Install the candidate ZIP and activate it without PHP warnings. Confirm admin assets load and the surrounding WordPress admin styling remains intact.
2. Open Prox Gallery as an administrator. Create, rename, change layout and delete a gallery. Creating must not publish a page or alter a menu. Confirm Back/Forward navigation works.
3. Add existing and new images through the real WordPress media picker. Update title, alt text, caption and description; return between sections and confirm current values.
4. Select, deselect all and reorder gallery images. Confirm the public grid/masonry order matches the saved order.
5. Explicitly publish a gallery page. Verify page status, shortcode and navigation link. Change its template and inspect the page; the existing page provisioner embeds a template in the shortcode, so review whether that should continue to override later gallery settings.
6. Open multiple public galleries on one page. Lightbox navigation must stay within its collection. Test Escape, Tab, focus restoration, alt text and reduced-motion preferences.
7. Run the real PHPUnit suite, including `OpenAiAttachmentAuthorizationTest`. An author must not generate or apply against another author's attachment; an authorised owner/editor should retain intended access. Check invalid nonce and insufficient capability responses separately.
8. Check that no saved API key appears in settings responses. Save another setting with the key field blank and verify the existing key remains configured. Do not perform a paid Generate action without the account owner's approval.
9. Revoke `prox_gallery_use_openai` from a test role, reload and verify that revocation persists after initial capability migration.
10. Record an image view, then a gallery visit, then another image view. Confirm existing image totals remain. Untrusted country headers must not change geolocation.
11. Exercise ordinary workloads and concurrent mutations. Verify exact counts after concurrent gallery creation, media updates and tracking. Verify libraries beyond 500 attachments retain their oldest records. Atomic option mutations and removal of index eviction are included in rc.2; full-collection reads remain a scaling limitation.

12. Configure user/site AI quotas, including zero. Run concurrent generations with intercepted transport and verify excess requests return HTTP 429 before reaching the provider. Verify day rollover and timeout accounting. See `tests/wordpress-verification/README.md` for the disposable TasteWP fixture.

The release candidate has not been installed or tested on the user's live WordPress site. No production content or menus were changed by this work.
