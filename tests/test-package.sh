#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PACKAGE_NAME="wc-colibrix-gateway-payment"
ARCHIVE_PATH="${ROOT_DIR}/build/${PACKAGE_NAME}.zip"
EXTRACT_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${PACKAGE_NAME}-package.XXXXXX")"

cleanup() {
  rm -rf "${EXTRACT_DIR}"
}
trap cleanup EXIT

"${ROOT_DIR}/bin/build-package.sh"

if [[ ! -f "${ARCHIVE_PATH}" ]]; then
  printf 'Archive is missing: %s\n' "${ARCHIVE_PATH}" >&2
  exit 1
fi

TOP_LEVEL_ENTRIES="$(
  unzip -Z1 "${ARCHIVE_PATH}" \
    | sed '/^$/d' \
    | cut -d/ -f1 \
    | sort -u
)"

if [[ "${TOP_LEVEL_ENTRIES}" != "${PACKAGE_NAME}" ]]; then
  printf 'Archive must contain exactly one top-level %s directory.\n' "${PACKAGE_NAME}" >&2
  exit 1
fi

unzip -q "${ARCHIVE_PATH}" -d "${EXTRACT_DIR}"
PLUGIN_DIR="${EXTRACT_DIR}/${PACKAGE_NAME}"

ALLOWED_ENTRIES=(
  "LICENSE"
  "README.md"
  "assets"
  "includes"
  "wc-colibrix-gateway-payment.php"
)

FAILED=0

while IFS= read -r -d '' entry; do
  name="$(basename "${entry}")"
  allowed=0
  for candidate in "${ALLOWED_ENTRIES[@]}"; do
    if [[ "${name}" == "${candidate}" ]]; then
      allowed=1
    fi
  done

  if [[ "${allowed}" -eq 0 ]]; then
    printf 'Unexpected path in package: %s\n' "${name}" >&2
    FAILED=1
  fi
done < <(find "${PLUGIN_DIR}" -mindepth 1 -maxdepth 1 -print0)

REQUIRED_FILES=(
  "LICENSE"
  "README.md"
  "assets/js/frontend/blocks.asset.php"
  "assets/js/frontend/blocks.js"
  "includes/blocks/class-wc-colibrix-gateway-blocks-support.php"
  "includes/class-wc-colibrix-gateway-plugin.php"
  "includes/gateways/abstract-wc-colibrix-gateway.php"
  "includes/gateways/class-wc-colibrix-gateway-apm.php"
  "includes/gateways/class-wc-colibrix-gateway-card.php"
  "wc-colibrix-gateway-payment.php"
)

for path in "${REQUIRED_FILES[@]}"; do
  if [[ ! -f "${PLUGIN_DIR}/${path}" ]]; then
    printf 'Required file missing from package: %s\n' "${path}" >&2
    FAILED=1
  fi
done

if [[ "${FAILED}" -ne 0 ]]; then
  exit 1
fi

while IFS= read -r -d '' php_file; do
  if ! php -l "${php_file}" >/dev/null; then
    printf 'PHP syntax error in package: %s\n' "${php_file#"${PLUGIN_DIR}/"}" >&2
    exit 1
  fi
done < <(find "${PLUGIN_DIR}" -type f -name '*.php' -print0)

printf 'Package contract OK: %s\n' "${ARCHIVE_PATH}"
