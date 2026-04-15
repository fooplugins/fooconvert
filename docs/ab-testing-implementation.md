# Task: FooConvert PRO A/B Testing Implementation

## Status

Core implementation shipped. Follow-up work remains.

## Summary

FooConvert now has a working PRO A/B testing foundation built around a native WordPress experiment CPT, popup duplication, server-side sticky assignment, and results reporting on top of the existing analytics tables.

The earlier draft backlog in this directory is no longer accurate. This document summarizes what is actually implemented today and what still needs follow-up work.

## Implemented

### Runtime

- PRO-only feature for `fc-overlay`, `fc-bar`, and `fc-flyout`
- Same-type experiments only
- Server-side participant assignment with sticky cookies
- Assignment cookie stores popup IDs, not indexes
- Display rules and shortcode rendering both resolve through the experiment runtime
- Active experiments disable page caching for matched requests

### Experiment management

- Experiment CPT: `fc-experiment`
- Native wp-admin list/edit screens under FooConvert
- Control selection, goal selection, weights, end date, and stored auto-winner config
- Variant creation from the control or from an existing participant
- Start, pause, complete, complete-with-winner, and apply-winner flows
- Applied state after winner application
- Cleanup flow for permanently deleting variants after apply
- Popup membership metadata with role, label, current experiment, and history
- Run-window tracking so paused time is excluded from results

### Editor integration

- Experiment status panel in popup editors
- Direct links back to the owning experiment
- Variant popups cannot edit experiment-owned targeting:
  - display rules
  - trigger/open behavior
  - experiment-controlled close/open button settings

### Results

- Uses `fooconvert_events` and `fooconvert_leads`
- Per-participant metrics:
  - views
  - clicks
  - conversions
  - leads
  - goal rate
  - uplift
  - confidence
- Confidence/significance uses an in-plugin two-proportion z-test
- Inline experiment results and CSV export are implemented

## Acceptance Criteria

### Shipped

- [x] Register experiment CPT
- [x] Register experiment/popup meta
- [x] Create experiment lifecycle service
- [x] Build experiment admin UI on native CPT screens
- [x] Duplicate popups into variants
- [x] Resolve assigned participants during frontend rendering
- [x] Add sticky assignment cookies
- [x] Add results aggregation from existing analytics tables
- [x] Add significance/confidence calculation
- [x] Add inline results and CSV export
- [x] Add manual winner selection and apply-winner flow
- [x] Add applied state and post-apply cleanup flow
- [x] Add popup editor membership/status UI
- [x] Lock variant targeting config in the editor

### Still Open

- [ ] Admin preview mode / participant switcher
- [ ] Side-by-side participant preview UI
- [ ] Sample size calculator
- [ ] Low-traffic / underpowered warnings
- [ ] Auto-winner cron workflow
- [ ] Notification/audit trail for auto-actions
- [ ] Builder compatibility QA
- [ ] Cache-plugin QA
- [ ] Automated test coverage
- [ ] Full manual QA on an active site

## Notes

- The feature is PRO-only, but one small free-core runtime hook exists so PRO can resolve the correct popup at render time.
- Earlier docs referred to `fooconvert_experiment`; the implemented CPT is `fc-experiment`.
- Earlier docs assumed popup-only support and a custom admin architecture; the shipped implementation supports popups, bars, and flyouts on native CPT screens.
- `Apply Winner` copies the winner back to the control and moves the experiment to `Applied`.
- Variants are retained after `Apply Winner` until the user explicitly runs `Clean Up`.

## Verification To Date

- `php -l` on touched PHP files
- `npm run build`
- `npm run build:pro`

`npm run build` now ends on the PRO-scoped editor build so the shared `fc-editor` runtime remains compatible with `fc-editor-pro` in a PRO-enabled local site. Use `npm run build:free` when you explicitly need free-only packaged assets.

## Recommended Next Step

Focus next on admin preview support, sample size guidance, and auto-winner automation, then run a real end-to-end QA pass on an active WordPress site.
