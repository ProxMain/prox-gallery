# Prox Gallery CLI Manual

This manual documents all WP-CLI commands currently implemented by Prox Gallery.

## Prerequisites

- WordPress is installed and accessible by WP-CLI.
- The plugin is active.
- Commands are run from a context where `wp` can bootstrap WordPress (for example your WordPress root).

Quick checks:

```bash
wp --info
wp plugin is-active prox-gallery
```

## Command Namespace

All plugin commands use the `prox` namespace.

```bash
wp prox
```

If you want details directly from WP-CLI:

```bash
wp help prox
wp help prox media
wp help prox media track
```

## Media Commands (`prox media`)

These commands are always available when the plugin is active and WP-CLI is running.

### 1) List tracked images

```bash
wp prox media list-tracked
```

What it does:

- Prints a table of tracked media items.
- If no tracked items exist, prints: `Success: No tracked images found.`

Output columns:

- `id`
- `title`
- `mime_type`
- `width`
- `height`
- `file_size`
- `camera`
- `iso`
- `uploaded_at`
- `uploaded_by`
- `url`

### 2) Track a single image attachment

```bash
wp prox media track <id>
```

Arguments:

- `<id>` (required): WordPress attachment post ID.

Examples:

```bash
wp prox media track 123
```

Behavior:

- Fails if ID is missing/invalid.
- Fails if attachment does not exist or is not an image.
- On success prints: `Success: Tracked image attachment ID <id>.`

### 3) Validate tracked rows

```bash
wp prox media validate
```

What it does:

- Removes stale tracked rows that no longer reference valid attachment posts.

Success message format:

- `Success: Validation complete. Removed <removed> stale tracked rows. Remaining <remaining>.`

## Development Seed Commands (`prox seed`)

`prox seed` is available only when development seed module is enabled.

Enable it in plugin bootstrap/config:

```php
define('PROX_GALLERY_ENABLE_DEV_SEED_MODULE', true);
```

Then use:

```bash
wp prox seed import-random [--images=<n>] [--galleries=<n>] [--max-categories=<n>] [--max-galleries=<n>] [--clear-existing]
```

### Import random demo data

```bash
wp prox seed import-random
```

Options:

- `--images=<n>`: number of images to generate.
  - Default: `100`
  - Runtime clamp: min `1`, max `500`
- `--galleries=<n>`: number of galleries to create.
  - Default: `6`
  - Runtime clamp: min `1`, max `100`
- `--max-categories=<n>`: maximum category assignments per image.
  - Default: `3`
  - Runtime clamp: min `0`, max `20`
- `--max-galleries=<n>`: maximum gallery assignments per image.
  - Default: `3`
  - Runtime clamp: min `1`, max `20`
- `--clear-existing`: clears existing gallery option data and tracked queue before seeding.

Examples:

```bash
wp prox seed import-random --images=50 --galleries=8
wp prox seed import-random --images=120 --max-categories=5 --max-galleries=4 --clear-existing
```

Success output:

- Summary line with counts for created/tracked/failed images, galleries, and assignments.
- If galleries were created, an additional table is printed with columns: `id`, `name`, `template`.

## Troubleshooting

### `Error: 'prox' is not a registered wp command`

- Ensure plugin is active.
- Ensure WP-CLI is loading the same WordPress install you expect.

### `Error: 'seed' is not a registered subcommand of 'prox'`

- Development seed module is not enabled.
- Define `PROX_GALLERY_ENABLE_DEV_SEED_MODULE` as `true` and reload WP-CLI context.

### Track command fails for an existing attachment

- Confirm attachment is an image MIME type.
- Re-check ID with:

```bash
wp post get <id> --field=post_type
wp post get <id> --field=post_mime_type
```

## Quick Reference

```bash
# Media
wp prox media list-tracked
wp prox media track <id>
wp prox media validate

# Seed (dev module only)
wp prox seed import-random
wp prox seed import-random --images=100 --galleries=6 --max-categories=3 --max-galleries=3 --clear-existing
```
