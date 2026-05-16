# Plugin Check Security Review Options

Date: 2026-05-16
Command: `npm run plugin-check`

This document tracks Plugin Check findings that were not changed automatically because they need workflow or product review. Low-risk mechanical changes were committed separately.

## Already Addressed

- `includes/Blocks/SplitLayout.php`: documented the existing KSES output path with a targeted PHPCS ignore.
- `includes/Admin/Views/popup-stats.php`: changed the fallback redirect from `wp_redirect()` to `wp_safe_redirect()`.
- `includes/Admin/Views/popup-stats.php`: sanitized the read-only `post_id` request value with `wp_unslash()` and `absint()`.
- `includes/Event.php`: added existence checks before reading `$_SERVER` values.
- `includes/functions.php`: sanitized popup type and popup request helper values.
- `includes/PostType.php`: sanitized popup type list-filter values.
- `includes/AI/PopupBuilder/Config.php`: sanitized the AI builder admin page value.
- `pro/includes/functions.php`: sanitized shared cookie reads, gated integration `error_log()` output behind FooConvert debug mode, and documented the debug-only log with a targeted PHPCS ignore.
- `build/run-plugin-check.mjs`: excluded local-only repository files from the project Plugin Check wrapper.

## Review Items

### 1. Read-only GET filters and page checks

Files:
- `includes/functions.php`
- `includes/PostType.php`
- `includes/Admin/LeadsTable.php`
- `includes/Admin/Views/leads.php`
- `includes/AI/PopupBuilder/Config.php`

Plugin Check reports nonce warnings for read-only GET inputs used for page checks, list-table filtering, sorting, searching, and pagination.

Options:
- Add nonces to the filter forms and generated filter links. This is the strictest option, but it changes URLs and requires preserving nonce parameters across sorting, filtering, and pagination.
- Keep sanitized read-only GET handling and add targeted PHPCS ignores with comments explaining that these are not state-changing requests.
- Introduce shared request helper methods for sanitized admin GET values, then suppress only the nonce warnings at the call sites.

Recommendation:
Use targeted suppressions for read-only filters after confirming no state changes happen from these inputs. Add nonces only to flows that mutate data.

### 2. Leads bulk delete and export

File:
- `includes/Admin/LeadsTable.php`

Finding:
`process_bulk_action()` reads `$_REQUEST['leads']` and can delete or export selected leads.

Options:
- Verify the `WP_List_Table` bulk action nonce before delete and export actions, and keep the current table flow.
- Split destructive/export actions into dedicated `admin-post.php` handlers with explicit action-specific nonces and capability checks.
- Keep export on the list-table action but require a separate export nonce if browser downloads need different handling from delete.

Recommendation:
Add nonce and capability verification before delete/export. This is likely the highest-value remaining security fix, but it needs a quick review of the list table form markup and current nonce names.

### 3. Experiment admin request handling

Files:
- `pro/includes/Experiments/Admin/Init.php`
- `pro/includes/Experiments/Experiment.php`

Findings:
Plugin Check reports nonce and sanitization warnings across experiment list filters, request-driven admin state, and experiment configuration POST handling.

Options:
- Classify each request read as either read-only filtering/navigation or state-changing save/action, then suppress read-only warnings and add nonce checks to state-changing paths.
- Add a single experiment admin request parser that validates capabilities, nonces, and expected fields before the save/action code reads request data.
- Refactor experiment configuration saves into explicit form handlers with dedicated nonce names.

Recommendation:
Do not blanket-change these. Review each request path against the experiment UI first, because incorrect nonce placement could break experiment editing or list-table actions.

### 4. AJAX event payload sanitization

File:
- `includes/Ajax.php`

Finding:
Plugin Check flags `$_POST['data']` as unsanitized before `json_decode()`.

Current behavior:
The handler verifies `check_ajax_referer( 'fooconvert_nonce', 'nonce' )` and sanitizes known decoded fields individually. `extraData` is intentionally persisted as structured feature-specific analytics/lead metadata.

Options:
- Add a targeted PHPCS suppression at the JSON decode line, documenting that nonce verification happens first and known fields are sanitized after decode.
- Add a recursive sanitizer for `extraData`. This is safer for arbitrary text, but can alter metadata shape and values.
- Add per-source schemas for `extraData` and sanitize each known payload type separately.

Recommendation:
Use a targeted suppression for the decode line only if the current `extraData` behavior is intentional. For a stricter security pass, prefer per-source schemas over blanket recursive sanitization.

### 5. FooFields settings-page POST detection

File:
- `includes/Admin/FooFields/SettingsPage.php`

Finding:
`is_settings_page()` reads `$_POST['option_page']` without nonce verification.

Options:
- Sanitize the `option_page` value and add a targeted suppression if this method only detects screen context.
- Require settings nonce verification here if the return value can trigger saving or privileged behavior.
- Move POST-specific detection into the settings save path and keep screen detection read-only.

Recommendation:
Review call sites before changing. If this method only affects asset loading/screen detection, sanitize and suppress. If it gates saving, add nonce and capability checks.

### 6. AI popup builder streaming timeout extension

File:
- `includes/AI/PopupBuilder/RestController.php`

Finding:
`set_time_limit( 0 )` is discouraged.

Options:
- Keep it with a targeted PHPCS suppression because the stream route is long-running and already uses REST permissions.
- Remove it and rely on server/PHP defaults, accepting that long AI streams may terminate earlier.
- Make it filterable so hosts can disable timeout extension.

Recommendation:
Keep it for now with a targeted suppression or filter. Removing it could change streaming behavior.

### 7. Translation loading warning

File:
- `includes/Init.php`

Finding:
`load_plugin_textdomain()` is discouraged for WordPress.org-hosted plugins.

Options:
- Remove the manual call if WordPress.org translation loading is the only supported distribution path.
- Keep it if direct ZIP/Freemius installs need bundled translations loaded consistently.
- Gate it by distribution context if both paths need different behavior.

Recommendation:
Treat this as distribution policy, not a security issue.

## Non-security Findings

The remaining Plugin Check output also includes performance and naming warnings, including `post__not_in`/`exclude` query usage, missing resource versions, global naming conventions, and Freemius constants. These are outside the security-focused cleanup and should be reviewed separately.
