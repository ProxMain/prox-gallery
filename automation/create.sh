#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_DIR="${ROOT_DIR}/release"
TMP_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

VERSION="$(sed -n 's/^[[:space:]]*\* Version:[[:space:]]*\(.*\)$/\1/p' "${ROOT_DIR}/prox-gallery.php" | head -n 1 | xargs)"

if [[ -z "${VERSION}" ]]; then
  echo "Could not detect plugin version from prox-gallery.php" >&2
  exit 1
fi

PACKAGE_NAME="prox-gallery-${VERSION}"
PACKAGE_ROOT="${TMP_DIR}/prox-gallery"
ZIP_PATH="${RELEASE_DIR}/${PACKAGE_NAME}.zip"
TMP_ZIP_PATH="${TMP_DIR}/${PACKAGE_NAME}.zip"

mkdir -p "${RELEASE_DIR}" "${PACKAGE_ROOT}"

rsync -a "${ROOT_DIR}/" "${PACKAGE_ROOT}/" \
  --exclude '.git/' \
  --exclude '.idea/' \
  --exclude 'node_modules/' \
  --exclude 'release/' \
  --exclude 'releases/' \
  --exclude '.wp-env/' \
  --exclude 'tests/' \
  --exclude '.phpunit.result.cache' \
  --exclude '.env' \
  --exclude '*.log'

(
  cd "${TMP_DIR}"
  zip -rq "${TMP_ZIP_PATH}" "prox-gallery"
)

if [[ ! -s "${TMP_ZIP_PATH}" ]]; then
  echo "Failed: generated archive is empty: ${TMP_ZIP_PATH}" >&2
  exit 1
fi

mv -f "${TMP_ZIP_PATH}" "${ZIP_PATH}"

if [[ ! -s "${ZIP_PATH}" ]]; then
  echo "Failed: generated archive is empty after move: ${ZIP_PATH}" >&2
  exit 1
fi

echo "Created: ${ZIP_PATH}"
