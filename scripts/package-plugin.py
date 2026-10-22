"""Package built runtime files with an explicitly supplied production vendor directory."""
import argparse
import json
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

parser = argparse.ArgumentParser()
parser.add_argument('destination', type=Path)
parser.add_argument('--vendor-dir', required=True, type=Path)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
vendor = args.vendor_dir.resolve()
installed = json.loads((vendor / 'composer/installed.json').read_text(encoding='utf-8'))
if not isinstance(installed, dict) or installed.get('dev', True):
    raise SystemExit('Use a separate vendor directory installed with composer install --no-dev.')
if not (vendor / 'autoload_packages.php').is_file():
    raise SystemExit('Missing Jetpack runtime autoloader.')
if not (root / 'assets/admin/build/manifest.json').is_file():
    raise SystemExit('Run npm run build before packaging.')

destination = args.destination.resolve()
destination.parent.mkdir(parents=True, exist_ok=True)
with ZipFile(destination, 'w', ZIP_DEFLATED) as archive:
    for name in ['prox-gallery.php', 'readme.txt', 'README.md', 'CHANGELOG.md', 'composer.json', 'composer.lock']:
        archive.write(root / name, 'prox-gallery/' + name)
    for directory in ['src', 'assets/frontend', 'assets/editor', 'assets/admin/build', 'docs']:
        for path in sorted((root / directory).rglob('*')):
            if path.is_file():
                archive.write(path, 'prox-gallery/' + path.relative_to(root).as_posix())
    for path in sorted(vendor.rglob('*')):
        if path.is_file():
            archive.write(path, 'prox-gallery/vendor/' + path.relative_to(vendor).as_posix())
print(destination)
