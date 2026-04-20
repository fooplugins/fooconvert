#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
PACKAGE_JSON="${ROOT_DIR}/package.json"
SVN_ROOT="${SVN_ROOT:-${HOME}/SVN/fooconvert}"
SVN_TAGS_DIR="${SVN_ROOT}/tags"
SVN_USERNAME="${WPORG_SVN_USERNAME:-${SVN_USERNAME:-bradvin}}"

if ! command -v node >/dev/null 2>&1; then
	echo "Error: node is required to read the plugin version from package.json." >&2
	exit 1
fi

if ! command -v unzip >/dev/null 2>&1; then
	echo "Error: unzip is required." >&2
	exit 1
fi

if ! command -v svn >/dev/null 2>&1; then
	echo "Error: svn is required." >&2
	exit 1
fi

VERSION="${1:-$(node -p "require('${PACKAGE_JSON//\'/\\\'}').version")}"
ZIP_PATH="${DIST_DIR}/fooconvert-free.v${VERSION}.zip"
TAG_DIR="${SVN_TAGS_DIR}/${VERSION}"
SVN_ARGS=()

if [ -n "${SVN_USERNAME}" ]; then
	SVN_ARGS+=( "--username" "${SVN_USERNAME}" )
fi

if [ ! -d "${DIST_DIR}" ]; then
	echo "Error: ${DIST_DIR} does not exist." >&2
	exit 1
fi

if [ ! -d "${SVN_ROOT}/.svn" ]; then
	echo "Error: ${SVN_ROOT} is not an SVN working copy." >&2
	exit 1
fi

if [ ! -d "${SVN_TAGS_DIR}" ]; then
	echo "Error: ${SVN_TAGS_DIR} does not exist." >&2
	exit 1
fi

if [ -e "${TAG_DIR}" ]; then
	echo "Error: ${TAG_DIR} already exists. Refusing to overwrite an existing tag." >&2
	exit 1
fi

if [ ! -f "${ZIP_PATH}" ]; then
	LEGACY_FREE_ZIP_PATH="${DIST_DIR}/fooconvert-free.${VERSION}.zip"
	LEGACY_ZIP_PATH="${DIST_DIR}/fooconvert.v${VERSION}.zip"
	MATCHING_ZIPS=""
	MATCH_COUNT=0

	if [ -f "${LEGACY_FREE_ZIP_PATH}" ]; then
		ZIP_PATH="${LEGACY_FREE_ZIP_PATH}"
	elif [ -f "${LEGACY_ZIP_PATH}" ]; then
		ZIP_PATH="${LEGACY_ZIP_PATH}"
	else
		MATCHING_ZIPS="$(find "${DIST_DIR}" -maxdepth 1 -type f \( -name "fooconvert-free.v${VERSION}.zip" -o -name "fooconvert-free.${VERSION}.zip" -o -name "*${VERSION}*.zip" \) | sort || true)"
		MATCH_COUNT="$(printf '%s\n' "${MATCHING_ZIPS}" | sed '/^$/d' | wc -l | tr -d ' ')"
	fi

	if [ -n "${MATCHING_ZIPS:-}" ] && [ "${MATCH_COUNT}" -eq 1 ]; then
		ZIP_PATH="$(printf '%s\n' "${MATCHING_ZIPS}" | sed '/^$/d')"
	elif [ ! -f "${ZIP_PATH}" ]; then
		echo "Error: expected a single free zip for version ${VERSION} in ${DIST_DIR}." >&2
		if [ "${MATCH_COUNT}" -gt 1 ]; then
			echo "Matching zips:" >&2
			printf '%s\n' "${MATCHING_ZIPS}" | sed '/^$/d' >&2
		else
			echo "Place the downloaded Freemius free zip in ${DIST_DIR} first." >&2
		fi
		exit 1
	fi
fi

TEMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/fooconvert-wporg.XXXXXX")"
trap 'rm -rf "${TEMP_DIR}"' EXIT

unzip -q "${ZIP_PATH}" -d "${TEMP_DIR}"
rm -rf "${TEMP_DIR}/__MACOSX"

TOP_LEVEL_ENTRY_COUNT="$(find "${TEMP_DIR}" -mindepth 1 -maxdepth 1 | wc -l | tr -d ' ')"
TOP_LEVEL_DIR_COUNT="$(find "${TEMP_DIR}" -mindepth 1 -maxdepth 1 -type d | wc -l | tr -d ' ')"

if [ "${TOP_LEVEL_ENTRY_COUNT}" -eq 1 ] && [ "${TOP_LEVEL_DIR_COUNT}" -eq 1 ]; then
	SOURCE_DIR="$(find "${TEMP_DIR}" -mindepth 1 -maxdepth 1 -type d | head -n 1)"
else
	SOURCE_DIR="${TEMP_DIR}"
fi

if [ -d "${SOURCE_DIR}/pro" ]; then
	echo "Error: ${ZIP_PATH} still contains a /pro directory. Use the Freemius free zip." >&2
	exit 1
fi

mkdir -p "${TAG_DIR}"
cp -R "${SOURCE_DIR}/." "${TAG_DIR}/"

find "${TAG_DIR}" -type f -name '.DS_Store' -delete

svn add --parents --force "${TAG_DIR}" >/dev/null
svn commit "${SVN_ARGS[@]}" "${TAG_DIR}" -m "Adding new version tag ${VERSION}"
