#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
PACKAGE_NAME="wc-colibrix-gateway-payment"
BUILD_DIR="${ROOT_DIR}/build"
ARCHIVE_PATH="${BUILD_DIR}/${PACKAGE_NAME}.zip"
TEMP_PATH="${ARCHIVE_PATH}.tmp"

cleanup() {
  rm -f "${TEMP_PATH}"
}
trap cleanup EXIT

TOP_LEVEL="$(git -C "${ROOT_DIR}" rev-parse --show-toplevel 2>/dev/null || true)"

if [[ -z "${TOP_LEVEL}" || "$(cd "${TOP_LEVEL}" && pwd -P)" != "${ROOT_DIR}" ]]; then
  printf '%s is not the top level of a git checkout\n' "${ROOT_DIR}" >&2
  exit 1
fi

mkdir -p "${BUILD_DIR}"
rm -f "${ARCHIVE_PATH}"

git -C "${ROOT_DIR}" archive --worktree-attributes --format=zip \
  --prefix="${PACKAGE_NAME}/" --output="${TEMP_PATH}" HEAD

mv "${TEMP_PATH}" "${ARCHIVE_PATH}"

printf 'Built %s\n' "${ARCHIVE_PATH}"
