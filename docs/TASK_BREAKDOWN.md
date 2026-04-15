# AB Testing Task Breakdown

## Status

This checklist reflects the current implementation status of FooConvert PRO A/B testing.

## Phase 1: Core Infrastructure

- [x] Register experiment CPT as `fc-experiment`
- [x] Register experiment meta and popup membership meta
- [x] Support `fc-overlay`, `fc-bar`, and `fc-flyout`
- [x] Implement the core `Experiment` service
- [x] Implement variant duplication from the control
- [x] Implement variant duplication from an existing variant
- [x] Enforce same-type participants
- [x] Enforce one non-completed experiment per popup
- [x] Track `started_at`, `ended_at`, and `run_windows`
- [x] Block deletion of popups that belong to a non-completed experiment

## Phase 2: Display And Assignment

- [x] Add the free-core `fooconvert_resolve_popup_post_id` hook
- [x] Resolve assigned participants during display-rule queueing
- [x] Resolve assigned participants during shortcode rendering
- [x] Store sticky assignment in `fooconvert_exp_{experiment_id}`
- [x] Store assigned popup IDs in cookies instead of array indexes
- [x] Reassign visitors when a stale cookie points at an invalid participant
- [x] Disable page caching when an active experiment matches the request
- [ ] Add admin preview mode / participant switcher for logged-in users
- [ ] Run formal cache compatibility QA across common cache plugins

## Phase 3: Admin And Editor UX

- [x] Use native experiment CPT list and edit screens
- [x] Add custom experiment columns, row actions, bulk actions, and filtered views
- [x] Add experiment configuration metabox
- [x] Add experiment participants metabox with control/variant actions
- [x] Add experiment actions metabox
- [x] Add results summary metabox
- [x] Add inline experiment results metabox
- [x] Allow creating new variants from the control on the experiment screen
- [x] Allow creating new variants from an existing variant on the experiment screen
- [x] Add direct edit links for the control and variants from the experiment screen
- [x] Add popup editor experiment membership/status panel
- [x] Lock experiment structure after first start
- [x] Lock variant display rules in the editor
- [x] Lock variant trigger/open-close configuration in the editor
- [x] Add winner highlighting in results summary and participants table
- [x] Add applied read-only state after winner application
- [x] Add cleanup action for permanently deleting variants after apply
- [ ] Add side-by-side participant previews in the experiment editor
- [ ] Add a stronger control-editor warning for live running experiments

## Phase 4: Analytics And Results

- [x] Aggregate experiment data from `fooconvert_events`
- [x] Aggregate experiment data from `fooconvert_leads`
- [x] Restrict results to active `run_windows`
- [x] Support goals for clicks, conversions, and leads
- [x] Calculate goal rate
- [x] Calculate uplift vs control
- [x] Calculate confidence/significance with a z-test
- [x] Render a tabular results dashboard
- [x] Export experiment results to CSV
- [ ] Add sample size calculator
- [ ] Add low-traffic / underpowered warnings
- [ ] Add richer charts or trend visualizations if still needed

## Phase 5: Winner Management

- [x] Support manual pause
- [x] Support manual complete
- [x] Support complete-with-winner from the participants table
- [x] Support apply-winner after completion
- [x] Preserve the control as the stable public entrypoint
- [x] Retain variants as historical records instead of deleting them
- [x] Mark applied experiments as `Applied` and make them read-only
- [x] Add cleanup flow to permanently delete variants after apply
- [ ] Add auto-winner cron processing
- [ ] Decide whether auto-apply should exist
- [ ] Add notifications or audit logging for automated winner actions
- [ ] Decide whether archive state/workflow is still wanted

## Phase 6: QA And Hardening

- [x] Add capability checks and nonce validation for admin actions
- [x] Sanitize experiment inputs and result queries
- [ ] Add automated tests for lifecycle, assignment, and results
- [ ] Perform builder compatibility testing for Elementor, Divi, Beaver Builder, and Gutenberg-heavy flows
- [ ] Perform full manual QA on an active WordPress site with FooConvert enabled

## Deferred / Out Of Scope For V1

- [ ] Archive-specific experiment state
- [ ] No-cookie mode
- [ ] Cookie-consent / GDPR integration
- [ ] Revenue goal support
- [ ] Bayesian winner scoring
- [ ] Device-based traffic splitting

## Next Recommended Work

1. Add admin preview/switcher support.
2. Add sample size and low-traffic guidance to the results UI.
3. Implement PRO auto-winner cron processing.
4. Run end-to-end QA on an active site and add automated coverage for core services.
