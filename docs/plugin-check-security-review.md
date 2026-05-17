# Plugin Check Security Review Options

Date: 2026-05-17
Command: `npm run plugin-check`

This document tracks Plugin Check security findings and the options for items that were not changed automatically because they need workflow or product review. Low-risk mechanical changes were committed separately.

## Current Security Result

- Security errors: none.
- Security warnings remaining: 19.
- Remaining security warnings are limited to Pro experiment request handling:
  - `pro/includes/Experiments/Admin/Init.php`
  - `pro/includes/Experiments/Experiment.php`
- The recent free-plugin request handling, nonce, and targeted PHPCS cleanup no longer appears in Plugin Check security output.

## Already Addressed

- `includes/Blocks/SplitLayout.php`: documented the existing KSES output path with a targeted PHPCS ignore.
- `includes/Admin/Views/popup-stats.php`: changed the fallback redirect from `wp_redirect()` to `wp_safe_redirect()`.
- `includes/Admin/Views/popup-stats.php`: sanitized the read-only `post_id` request value with `wp_unslash()` and `absint()`.
- `includes/Event.php`: added existence checks before reading `$_SERVER` values.
- `includes/functions.php`: sanitized popup type and popup request helper values.
- `includes/functions.php`: documented read-only `popup_type`, popup stats `page`/`post_id`, and preview `fooconvert_popup_preview` request checks with targeted PHPCS ignores.
- `includes/PostType.php`: changed the popup listing filter query parameter from `fooconvert_popup_type` to `popup_type` and reused `fooconvert_get_requested_popup_type()`.
- `includes/Admin/LeadsTable.php`: documented read-only list-table sort, order, search, and date filter request values with targeted PHPCS ignores.
- `includes/Admin/LeadsTable.php`: verified the standard `bulk-leads` list-table nonce before delete/export bulk actions read selected lead IDs or mutate/export data.
- `includes/Admin/Views/leads.php`: extracted the read-only `date_range` request value once, sanitized it, and documented the PHPCS nonce exception.
- `includes/AI/PopupBuilder/Config.php`: sanitized the AI builder admin `page` value and documented the read-only page check with targeted PHPCS ignores.
- `includes/Ajax.php`: documented the raw JSON decode line with a targeted PHPCS ignore after Ajax nonce verification and before per-field validation/sanitization.
- `includes/Admin/FooFields/SettingsPage.php`: sanitized the Settings API `option_page` value and documented the page-detection PHPCS nonce exception.
- `includes/AI/PopupBuilder/RestController.php`: documented the streaming `set_time_limit( 0 )` timeout extension with a targeted PHPCS ignore.
- `includes/Init.php`: documented the `load_plugin_textdomain()` call with a targeted PHPCS ignore for bundled translation loading.
- `pro/includes/functions.php`: sanitized shared cookie reads, gated integration `error_log()` output behind FooConvert debug mode, and documented the debug-only log with a targeted PHPCS ignore.
- `build/run-plugin-check.mjs`: excluded local-only repository files from the project Plugin Check wrapper.

## Remaining Security Warnings

### 1. Pro experiment list filters and request-driven admin state

File: `pro/includes/Experiments/Admin/Init.php`

Warnings:
- Line 217: `WordPress.Security.NonceVerification.Recommended` for `$_GET['fc_run_state']` in the experiment list view link current-state check.
- Line 217: `WordPress.Security.NonceVerification.Recommended` for the sanitized `$_GET['fc_run_state']` value used in the same read-only list view comparison.
- Line 242: `WordPress.Security.NonceVerification.Recommended` for `$_GET['fc_run_state']` in experiment list query filtering.
- Line 246: `WordPress.Security.NonceVerification.Recommended` for the sanitized `$_GET['fc_run_state']` value used to filter the experiment list query.
- Line 926: `WordPress.Security.NonceVerification.Recommended` for `$_REQUEST['post']` in popup deletion blocking.
- Line 930: `WordPress.Security.NonceVerification.Recommended` for reading `$_REQUEST['post']` in popup deletion blocking.
- Line 930: `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` for passing `$_REQUEST['post']` through `wp_unslash()` before array/scalar `absint()` handling.
- Line 960: `WordPress.Security.NonceVerification.Recommended` for `$_GET['post']` in popup edit notice rendering.
- Line 964: `WordPress.Security.NonceVerification.Recommended` for the sanitized `$_GET['post']` value used in popup edit notice rendering.
- Line 1000: `WordPress.Security.NonceVerification.Recommended` for `$_GET['fc_experiment_error']` in experiment notice rendering.
- Line 1009: `WordPress.Security.NonceVerification.Recommended` for the sanitized `$_GET['fc_experiment_error']` value used in experiment notice rendering.
- Line 1371: `WordPress.Security.NonceVerification.Missing` for `$_POST['fc_experiment_action']` in create-request detection.
- Line 1371: `WordPress.Security.NonceVerification.Missing` for the sanitized `$_POST['fc_experiment_action']` value used in create-request detection.

Options:
- Classify each request read as read-only filtering/navigation, passive protection, or state-changing form handling.
- Add targeted PHPCS suppressions for read-only list filters and notices after confirming these inputs cannot mutate state.
- For popup deletion blocking, verify whether this runs inside a core post deletion request that has already passed WordPress nonce/capability checks; if so, add a targeted suppression near the guarded read. If not, add explicit nonce/capability verification before reading `post`.
- For `fc_experiment_action`, verify the experiment creation form nonce and capability path before suppressing or adding an explicit nonce check here.
- Introduce a small experiment admin request helper only if it reduces repeated sanitization and keeps nonce handling obvious.

Recommendation:
Do not blanket-suppress this file. Handle the read-only list and notice reads separately from create/delete request paths so nonce requirements stay clear.

### 2. Pro experiment configuration POST handling

File: `pro/includes/Experiments/Experiment.php`

Warnings:
- Line 398: `WordPress.Security.NonceVerification.Missing` for `$_POST[ FOOCONVERT_CPT_EXPERIMENT . '-configuration' ]`.
- Line 399: `WordPress.Security.NonceVerification.Missing` for reading the experiment configuration POST value.
- Line 399: `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` for passing the experiment configuration array through `wp_unslash()` before structured state sanitization.
- Line 423: `WordPress.Security.NonceVerification.Missing` for `$_POST['fooconvert_experiment_variant_weights']`.
- Line 424: `WordPress.Security.NonceVerification.Missing` for reading variant weight POST values.
- Line 424: `WordPress.Security.ValidatedSanitizedInput.InputNotSanitized` for passing variant weight input through `wp_unslash()` before `sanitize_weights()`.

Options:
- Confirm whether this method only runs during a WordPress post save path that has already passed core nonce and capability checks, then add targeted PHPCS suppressions where structured arrays are unslashed before field-level sanitization.
- Add explicit experiment-specific nonce verification before reading the configuration and weight POST arrays if the core save nonce is not sufficient or not always present.
- Keep the existing structured sanitizers, but document the raw array handoff where Plugin Check cannot infer downstream validation.
- Refactor experiment configuration saves into an explicit form handler if future changes need clearer ownership of nonce, capability, and schema validation.

Recommendation:
Review the experiment save lifecycle before changing behavior. The likely low-risk path is to verify the upstream save nonce/capability path, then add targeted suppressions for array handoff lines that are sanitized by experiment-specific validators.

## Non-security Findings

The remaining Plugin Check output also includes performance and naming warnings, including `post__not_in`/`exclude` query usage, missing resource versions, global naming conventions, and Freemius constants. These are outside the security-focused cleanup and should be reviewed separately.
