=== Prox Gallery ===
Contributors: marcel_santing
Tags: image gallery, photo gallery, lightbox, masonry gallery, media manager
Requires at least: 6.9.1
Tested up to: 6.9.1
Requires PHP: 8.1
Stable tag: 0.1.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress image gallery and media manager plugin with responsive masonry/grid layouts, fullscreen lightbox, image organization, and optional AI-generated image titles and descriptions.

== Description ==

Prox Gallery is an all-in-one WordPress image gallery plugin and media manager built for fast image organization and frontend display.

Create responsive photo galleries, assign images to custom galleries, and display them in configurable grid or masonry layouts with a fullscreen lightbox experience.

Gallery and media features:

- Automatic image tracking from WordPress Media Library uploads
- Built-in media manager with image metadata editing
- Gallery manager: create, rename, delete, and assign images to galleries
- Frontend image gallery templates: Basic Grid and Masonry
- Responsive gallery settings: columns, hover zoom, lightbox transitions, and full-width mode
- Image categories for better media organization and filtering
- Fullscreen lightbox with image title and optional info panel
- Optional OpenAI workflow with preview + explicit apply

AI image metadata features (admin only):

- Generate a short image title (max 4 words)
- Generate image description/story text
- Factual, Technical, and Creative prompt templates
- Custom prompt templates and language presets
- Per-image conscious generation action (no background auto-generation)

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it via the WordPress Plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `Prox Gallery` in wp-admin.
4. Configure template settings in the `Settings` section.
5. (Optional) Configure OpenAI in `Settings > OpenAI`.

== Frequently Asked Questions ==

= Does this plugin require OpenAI? =

No. OpenAI is optional. Core gallery and media functionality works without it.

= Who can use OpenAI generation actions? =

OpenAI generation actions require the custom capability `prox_gallery_use_openai` (granted by default to Administrators, Editors and Authors). OpenAI settings management is restricted to administrators.

= Does AI content apply automatically? =

No. Generation is a manual per-image action. Users must explicitly preview and apply.

= Where is generated AI content stored? =

- Short title is saved to the attachment title (`post_title`)
- Description/story is saved to attachment content (`post_content`)

= Does the plugin send image data to external services? =

Only when a user explicitly runs an OpenAI generation action for an image. In that case, image bytes and prompt context are sent to OpenAI API for processing.

= Is Libsodium required? =

Yes. Prox Gallery requires the PHP Libsodium extension to encrypt and decrypt the OpenAI API key at rest.

= Is there WP-CLI support? =

Yes. See the CLI manual in `docs/cli.md`.

== Screenshots ==

1. Gallery dashboard with image tracking and gallery overview.
2. Media manager in list and grid view with metadata editing modal.
3. Gallery management screen for creating galleries and assigning images.
4. Gallery template settings for responsive Basic Grid and Masonry layouts.
5. OpenAI settings and per-image AI title/description preview and apply flow.
6. Frontend fullscreen lightbox with bottom title bar and info panel.

== Changelog ==

= 0.1.2 =

- Architecture refactor release covering the reviewed backend and frontend responsibilities
- Frontend admin app split into clearer feature containers, reusable components, and shared async hooks
- Gallery settings normalization and gallery access moved behind service/repository boundaries
- Admin capability checks and admin AJAX config assembly centralized
- Template settings updates no longer overwrite unrelated shared plugin options
- Added release changelog file and feature backlog document

= 0.1.1 =

- Security hardening: generic AJAX error responses in production-style responses
- Security hardening: frontend tracking endpoint nonce validation, input validation, and basic rate limiting
- Security hardening: OpenAI generation/apply/config actions moved to custom capability `prox_gallery_use_openai`
- Security hardening: OpenAI API key encrypted at rest with Libsodium
- Frontend UX: lightbox title and info control moved to styled bottom center bar with responsive scaling improvements
- Documentation updates for OpenAI capability model, privacy behavior, and Libsodium requirement

= 0.1.0 =

- Initial public release
- Modular plugin architecture with dedicated modules/controllers/services
- Media upload tracking and media manager tooling
- Gallery CRUD and image-to-gallery assignment
- Frontend templates: Basic Grid and Masonry
- Template customization controls and gallery-level overrides
- Frontend tracking events for gallery/image views
- OpenAI integration for per-image short title + description generation
- WordPress admin React app integration for management workflows

== Upgrade Notice ==

= 0.1.2 =

Architecture cleanup release with safer shared settings writes and a more maintainable admin frontend.

= 0.1.1 =

Security and capability hardening release with improved lightbox UX.

= 0.1.0 =

Initial release.
