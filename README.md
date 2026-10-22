# Prox Gallery 0.2.0-rc.1

A WordPress gallery plugin with a redesigned image workspace. This is a release candidate: the isolated checks pass, but a complete WordPress integration run is still required before production use.

## Develop

Use Node 24 LTS, PHP 8.1 or later with Sodium, Composer and Python 3 for packaging.

```sh
composer install
npm ci
npm run dev
```

Open `http://localhost:5173/dev/index.html` for an isolated design preview. It uses the real React app and in-memory sample data. Publishing, uploading and AI requests do not run against WordPress. Sample photographs are hotlinked from Unsplash; no sample data or image host is included in the production entrypoint.

For WordPress, build with `npm run build`, put the plugin in `wp-content/plugins/prox-gallery`, and activate it. The plugin expects the Composer autoloader and `assets/admin/build/manifest.json`. Retain the repository's WordPress requirement, 6.9.1. Theme compatibility has not been certified in this release candidate.

```sh
npm test
npm run typecheck
npm run build
composer lint:php
composer test:unit
npm audit
composer audit
```

The PHP unit suite uses explicit WordPress boundary doubles. For the real WordPress suite, provide `WP_TESTS_DIR` pointing to the WordPress test library and a disposable test database, then run `composer test`. `tests/wp-tests-config.php` documents the database environment variables. Do not point the integration suite at a production database.

## Preview and package

```sh
node scripts/build-preview.mjs
```

This creates `dev/prox-gallery-preview.html`, a standalone interactive preview that can be opened directly in a browser. Its sample changes last until reload. The generated file is ignored by Git.

For an installable plugin ZIP, first build the admin assets and prepare a **separate** Composer vendor directory using the checked-in `composer.json` and `composer.lock` with `composer install --no-dev --optimize-autoloader`. Then:

```sh
python scripts/package-plugin.py /absolute/output/prox-gallery.zip --vendor-dir /absolute/release/vendor
```

The packager checks that the supplied vendor directory excludes development packages. It includes runtime assets and Composer dependencies, and excludes development previews and tests. Source builds are reproducible from both checked-in lockfiles.

## Behaviour changes

- Creating a gallery saves the collection. Publishing its public WordPress page and menu link is a separate, explicitly confirmed action.
- Deleting a gallery keeps source images and existing WordPress pages. Deleting a media image through its WordPress link permanently affects every place using that image; the UI says so.
- The saved OpenAI key is never returned by settings endpoints. A blank key field preserves the current key.
- AI generate/apply requires both the existing action capability and permission to edit the individual attachment.
- Default AI capabilities are initialised once; later role revocations are respected. Existing default roles remain administrator, editor and author.
- Country statistics use server-provided `GEOIP_COUNTRY_CODE` or an explicit trusted `prox_gallery/tracking/country_code` integration. Unverified country headers now resolve to Unknown.
- Listing media does not write to the queue, and reads current attachment titles after edits from other screens.

See [the architecture notes](docs/architecture.md), [security assessment](docs/security-assessment.md) and [staging checks](docs/staging-checks.md).
