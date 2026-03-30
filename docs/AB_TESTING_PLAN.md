# FooConvert PRO A/B Testing Plan

## Status

The A/B testing foundation is implemented and lives in FooConvert PRO.

This document is the current-state plan, not the original draft. It reflects what is already built, what is intentionally deferred, and what still needs to be finished before the feature can be considered fully rounded out.

## Product Scope

- PRO-only feature for `fc-popup`, `fc-bar`, and `fc-flyout`
- Same-type experiments only
- Existing analytics tables are reused: `fooconvert_events` and `fooconvert_leads`
- No new database tables
- Native WordPress CPT screens and metaboxes, not a separate custom CRUD UI
- One small free-core runtime hook is required so PRO can resolve control widgets to assigned variants at render time

## Implemented Architecture

### Experiment container

- Experiment CPT: `fc-experiment`
- Native edit screen under `FooConvert > Experiments`
- Supported run states: `running`, `paused`, `completed`, `applied`
- WordPress `draft` post status is used for unpublished experiments

### Experiment data model

Experiment posts store:

- control participant ID
- ordered variant participant IDs
- per-participant weights keyed by participant ID
- goal: `click`, `conversion`, or `lead`
- winner participant ID
- optional end date
- auto-winner settings, stored for future automation
- `started_at`, `ended_at`, and active `run_windows`

Widget posts store canonical membership:

- current experiment ID
- experiment role: `control` or `variant`
- experiment label: `Control`, `Variant A`, `Variant B`, etc.
- experiment history

This replaces the earlier draft idea of reverse lookups based on serialized experiment meta.

### Variant lifecycle

- Variants are duplicated participant posts created from the experiment screen
- New variants can be created from the control or from an existing variant
- Variants remain same-type as the control
- Only one non-completed experiment can reserve a participant at a time
- Controls keep the public display rules and shortcode entrypoint
- Variant widgets are internal draft copies and do not own targeting
- Variants keep track of which participant they were created from

### Runtime and assignment

- Free-core exposes `fooconvert_resolve_widget_post_id`
- Display-rule queueing and shortcode rendering both pass through that resolver
- PRO resolves control widgets to an assigned participant for `display_rules` and `shortcode` contexts
- Assignment is server-side and sticky via `fooconvert_exp_{experiment_id}`
- The cookie stores the assigned participant ID, not an array index
- If an experiment is active on the request, page caching is disabled via `DONOTCACHEPAGE` and `nocache_headers()`

### Admin and editor UX

- Native experiments list screen with custom columns, row actions, bulk actions, and filtered views
- Experiment edit screen uses FooFields plus supporting metaboxes
- The `Experiment Participants` metabox lets users:
  - edit the control directly
  - create a variant from the control
  - edit any participant from the list
  - create a new variant from any existing participant
- Applied winners can be followed by a `Clean Up` action that permanently deletes all variants
- Control, variants, weights, and goal are locked after the experiment has started
- Widget editors show experiment membership through a PRO `PluginPostStatusInfo` panel
- Variant widgets lock experiment-owned targeting:
  - display rules
  - trigger/open behavior
  - close/open button visibility tied to experiment-controlled behavior

### Results and winner management

- Results are aggregated from existing events/leads tables
- Result queries respect `run_windows`, so paused periods are excluded
- Current experiment-screen results include:
  - views
  - clicks
  - conversions
  - leads
  - goal rate
  - uplift vs control
  - confidence/significance
- Significance uses an in-plugin two-proportion z-test
- Manual completion and manual winner selection are implemented
- CSV export is implemented
- `Apply Winner` is implemented and copies the winner back to the control while preserving the control as the stable entrypoint
- After `Apply Winner`, the experiment moves to `Applied` and becomes read-only
- After `Apply Winner`, variants can optionally be permanently deleted with `Clean Up`

## Current User Flow

1. Create a popup, bar, or flyout and publish it.
2. Go to `FooConvert > Experiments` and add a new experiment.
3. Choose the control and save the experiment.
4. From the `Experiment Participants` metabox, create one or more variants from the control or from an existing participant.
5. Edit each participant from the links in the variants list.
6. Set the goal and participant weights.
7. Start the experiment.
8. The control remains the public entrypoint; runtime assignment resolves visitors to control or variant.
9. Review the inline experiment results and CSV export as traffic accumulates.
10. Pause, complete, or complete with a winner.
11. Optionally apply the winner back onto the control after completion.
12. Optionally use `Clean Up` to permanently delete all variants after the winner has been applied.

## Remaining Backlog

These items are still outstanding and should be treated as the active follow-up list.

### Admin preview and editorial polish

- Add admin preview mode / participant switcher for logged-in users
- Add side-by-side participant preview UI inside the experiment screen
- Add clearer control-specific editor warnings when editing a live control during a running test

### Analytics polish

- Add sample size calculator
- Add low-traffic / underpowered warnings
- Add richer result visualizations if needed beyond the current table + CSV export

### Automation

- Add a PRO cron job for auto-winner evaluation
- Automatically complete experiments when auto-winner rules are met
- Decide whether auto-apply should exist at all
- Add audit trail and optional notifications for auto-actions

### Verification

- Run builder compatibility QA for Elementor, Divi, Beaver Builder, and Gutenberg-heavy flows
- Run cache QA against common page-cache plugins and reverse-proxy setups
- Add automated test coverage for lifecycle, assignment, and results logic
- Perform full manual QA on an active local/staging site with FooConvert enabled

## Deferred / Not In V1

These appeared in earlier drafts but are not part of the current shipped scope.

- Separate archived experiment state/workflow
- No-cookie mode
- GDPR/cookie-notice integration
- Revenue goals
- Bayesian scoring
- Device-based traffic splitting
- Advanced cache-plugin specific integrations

## Notes For Future Updates

- Keep this document aligned with the actual implementation, not the earlier draft architecture
- When updating status, prefer marking shipped behavior and keeping a short, real backlog over maintaining speculative phases
