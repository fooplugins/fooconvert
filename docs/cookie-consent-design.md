# Cookie Consent Feature — Design

Status: Draft
Owner: FooConvert
Target branch: `claude/add-cookie-consent-eLFK2`

## 1. Goal

Ship a first-party cookie consent banner inside FooConvert that lets a site
operator meet EU GDPR + ePrivacy ("cookie law") + UK PECR obligations out of
the box, reusing FooConvert's existing popup runtime (bar / flyout / overlay)
for the visible UI and adding a site-wide consent layer that other blocks,
third-party scripts, and FooConvert's own event tracking can gate on.

The feature is free (not PRO), because a consent banner without granular
category control is not compliant, and shipping half a solution would be
worse than shipping none.

## 2. Compliance requirements (what "EU compliant" means here)

This is the non-negotiable list that the implementation must satisfy. Each
item drives a concrete design decision in §4–§7.

1. **Prior, opt-in consent.** No non-essential cookies, trackers, fingerprints
   or remote scripts fire before the user accepts. Default state = denied
   for everything except the "strictly necessary" category.
2. **Granular categories.** Users can accept/deny per category
   (Necessary / Preferences / Statistics / Marketing, extensible).
3. **Reject is as easy as accept.** The first layer must expose "Reject all"
   with equal visual weight to "Accept all" — no dark patterns, no
   "Accept" with only a nested "Settings" path to reject. (EDPB 03/2022.)
4. **No pre-checked boxes** for non-essential categories.
5. **Informed.** Plain-language description per category + link to the
   site's cookie / privacy policy + a visible cookie list per category.
6. **Withdrawable.** A persistent "Cookie settings" trigger (floating
   button and/or shortcode / block) lets a user re-open the banner and
   change their choice at any time, as easily as they gave it.
7. **Proof of consent.** Record the consent event (categories, timestamp,
   banner version, user agent, consent string) so the site owner can
   demonstrate compliance if challenged.
8. **Re-prompt.** Expire consent after a configurable period
   (default 6 months, max 12) and re-prompt on policy/version change.
9. **Script gating.** Third-party tags (GA, Meta Pixel, hotjar, etc.)
   must not execute until their category is granted. The plugin provides
   both a declarative way (attribute rewriting) and an imperative JS API
   (`FooConvert.consent.onGrant('statistics', cb)`).
10. **Google Consent Mode v2.** Emit the `gtag('consent', ...)` signals
    (`ad_storage`, `analytics_storage`, `ad_user_data`,
    `ad_personalization`, `functionality_storage`,
    `personalization_storage`, `security_storage`) so sites using
    Google Ads / GA4 stay measurable post-consent and don't lose
    modeled conversions.
11. **Accessible.** Keyboard trappable when modal, focus returned on
    close, ARIA roles, contrast, respect `prefers-reduced-motion`, no
    auto-dismiss on scroll (scroll = consent is explicitly disallowed
    by EDPB guidance).
12. **Localizable.** All banner strings go through `__()` / block
    attributes; default translations match the site's locale.
13. **Geo scope (optional).** "Apply to EU/EEA/UK only" and
    "Apply worldwide" toggle. Worldwide is the safer default and is
    what we recommend in-product.

## 3. Scope

### In scope (this feature branch)

- Site-wide consent store (option + cookie + JS runtime).
- New "Cookie Consent" popup subtype with a dedicated template that maps
  onto the existing bar/flyout/overlay rendering pipeline.
- Admin page: **FooConvert → Cookie Consent** with category editor,
  script/cookie inventory, banner assignment, policy links, geo rules,
  expiry, record-of-consent log.
- Frontend JS consent API + `<script data-fc-consent="statistics">`
  attribute rewriting.
- Google Consent Mode v2 bridge (on by default when the user has GA4
  enabled; detected by dequeuing `gtag`).
- Shortcode `[fooconvert_cookie_settings]` + block
  `fc/cookie-settings-link` to re-open the banner.
- Gate FooConvert's own event tracking (`fooconvert_events` table)
  behind the `statistics` category.
- Four default languages (en, de, fr, es) shipped; everything else via
  `.pot`.

### Out of scope (follow-ups)

- IAB TCF v2.2 string generation (complex, audited, rarely needed for
  small sites — defer to PRO, and only if there's demand).
- Per-page / per-post consent overrides.
- A/B testing of banner copy.
- ePrivacy Regulation (not yet law — revisit when adopted).

## 4. User experience

### 4.1 Banner layers

Layer 1 — first view (required):
- Short intro: "We use cookies…", short enough to read in 3 s.
- Three buttons with **equal visual weight**:
  - **Accept all**
  - **Reject all**
  - **Customize** (opens layer 2)
- Link to full cookie policy.
- Dismiss via ✕ is **treated as Reject** (per CNIL / Garante). The ✕
  is never "implicit accept".

Layer 2 — preferences:
- One toggle per category. **Necessary** is locked on and visually
  disabled.
- Each category expands to show the cookies in that category (name,
  provider, purpose, retention) — the list is driven by the inventory
  admin UI (§5.2) so site owners own their own disclosure.
- "Save preferences", "Accept all", "Reject all" — all three present.

Persistent re-entry:
- Small floating pill (configurable side, hideable per page) labelled
  "Cookie settings".
- Block `fc/cookie-settings-link` / shortcode
  `[fooconvert_cookie_settings]` for footers.

### 4.2 Popup form factors

The banner reuses FooConvert's existing rendering — the cookie consent
*is* a popup, just with a reserved role:

| Form factor | When to use                          |
|-------------|--------------------------------------|
| Bar         | Lowest friction, recommended default |
| Flyout      | Corner card, less invasive than modal|
| Overlay     | Only if the site legitimately needs a hard stop (rare; tends to read as a dark pattern) |

The admin picks one per-site. We ship a template per form factor in the
existing template pack so the user is never staring at a blank editor.

## 5. Architecture

### 5.1 PHP

New namespace: `FooPlugins\FooConvert\Consent`.

```
includes/
  Consent/
    Init.php              // wires hooks, registers settings, REST, assets
    Settings.php          // admin tab under FooConvert → Cookie Consent (FooFields)
    Categories.php        // category registry + defaults + `fooconvert_consent_categories` filter
    Inventory.php         // cookie/script inventory CRUD (stored in options)
    ConsentStore.php      // read/write the per-visitor consent cookie + server-side log
    ConsentLog.php        // append-only proof-of-consent records (custom table)
    GeoResolver.php       // resolves visitor region (CF-IPCountry, Cloudflare, GeoIP filter)
    ScriptGate.php        // filters enqueued scripts; rewrites src→data-src, type→text/plain
    ConsentMode.php       // emits Google Consent Mode defaults + updates
    Rest.php              // REST endpoints for record-of-consent writes
    Shortcodes.php        // [fooconvert_cookie_settings]
```

Registered in `includes/Init.php` alongside `EventHooks`, `Cron`, etc.

Constants added to `includes/constants.php`:

```php
define( 'FOOCONVERT_OPTION_CONSENT',          'fooconvert_consent_settings' );
define( 'FOOCONVERT_OPTION_CONSENT_INVENTORY','fooconvert_consent_inventory' );
define( 'FOOCONVERT_DB_TABLE_CONSENT_LOG',    'fooconvert_consent_log' );
define( 'FOOCONVERT_COOKIE_CONSENT',          'fc_consent' );   // visitor cookie
define( 'FOOCONVERT_CONSENT_VERSION_OPTION',  'fooconvert_consent_version' );
```

### 5.2 Data

**Option `fooconvert_consent_settings`** (site config):

```php
[
  'enabled'          => true,
  'geo_scope'        => 'worldwide', // 'eu_only' | 'worldwide'
  'form_factor'      => 'bar',       // 'bar' | 'flyout' | 'overlay'
  'popup_id'         => 123,         // FK to the fc-* CPT
  'reject_on_dismiss'=> true,
  'expiry_days'      => 180,
  'policy_url'       => '/cookie-policy/',
  'privacy_url'      => '/privacy/',
  'consent_mode_v2'  => true,
  'show_floating_button' => true,
  'floating_position'=> 'bottom-left',
  'version'          => 3,           // bump → re-prompts all visitors
]
```

**Option `fooconvert_consent_inventory`** (categories + cookies):

```php
[
  'categories' => [
    'necessary'   => [ 'locked' => true, 'label' => 'Necessary',   'desc' => '…' ],
    'preferences' => [ 'locked' => false,'label' => 'Preferences', 'desc' => '…' ],
    'statistics'  => [ 'locked' => false,'label' => 'Statistics',  'desc' => '…' ],
    'marketing'   => [ 'locked' => false,'label' => 'Marketing',   'desc' => '…' ],
  ],
  'cookies' => [
    [ 'name'=>'_ga',     'category'=>'statistics','provider'=>'Google','purpose'=>'…','retention'=>'2y' ],
    …
  ],
  'scripts' => [
    [ 'handle'=>'ga4', 'category'=>'statistics', 'match'=>'googletagmanager.com' ],
    …
  ],
]
```

**Visitor cookie `fc_consent`** (first-party, SameSite=Lax, Secure, 180 d):

```
v=3.necessary=1.preferences=0.statistics=1.marketing=0.ts=1729600000.id=<uuid>
```

Compact so it stays well under the 4 KB cookie limit and is cheap to parse
server-side. The `id` is a random UUID (not personally identifying) used to
correlate with the server-side log.

**Proof-of-consent storage.** Two viable options; see §5.2.1 for the
trade-off. The design picks **Option A (dedicated table)** but the
middle-ground Option B is implementable and documented here so the
decision isn't silent.

Option A — custom table `{$wpdb->prefix}fooconvert_consent_log`
(append-only):

| column          | type                     | notes                               |
|-----------------|--------------------------|-------------------------------------|
| id              | BIGINT UNSIGNED PK AUTO  |                                     |
| consent_id      | CHAR(36)                 | matches `id` in the cookie          |
| created_at      | DATETIME                 | UTC                                 |
| version         | SMALLINT UNSIGNED        | banner/config version at capture    |
| categories      | VARCHAR(255)             | `n=1,p=0,s=1,m=0`                   |
| url             | VARCHAR(255)             | page the consent was given on       |
| user_agent_hash | CHAR(64)                 | sha256 of UA — demonstrates proof w/o storing the raw string |
| ip_hash         | CHAR(64)                 | sha256(IP + site salt), truncated to /24 before hashing for IPv4 and /64 for IPv6 to minimise identifiability |
| source          | VARCHAR(20)              | `banner`, `settings`, `withdraw`    |

Rationale for hashing: we need *proof* of consent, not the raw identifier.
Storing raw IPs in a log is itself processing of personal data and is the
thing we're trying to help users avoid. A truncated + salted hash is
sufficient to rebut a "I never consented" claim while staying proportionate.

Log retention is independent of the general FooConvert retention setting,
with a floor of 12 months (shorter than that defeats the purpose of proof
of consent) and a ceiling configurable per site.

### 5.2.1 Should we reuse `fooconvert_events` instead?

Worth considering honestly, because "one table per plugin" is a legitimate
goal and the existing table already captures many of the same fields
(`session_id`, `anonymous_user_guid`, `page_url`, `user_id`, `timestamp`,
`extra_data`).

**Option B — reuse `fooconvert_events` with `event_type = 'consent_grant'`
/ `consent_withdraw'`.** The `ip_hash` / `user_agent_hash` / `consent_id` /
`version` / `categories` payload lives in `extra_data` JSON. `post_id`
references either the popup that hosted the banner (if there is one) or
a sentinel (0 or a reserved `fc-consent` post).

Pros of Option B (reuse):

- **No second table.** Fewer migrations, no new "does the table exist?"
  support path in §support calls, no extra row in the Database admin tab.
- **Reuses `Event::track()` entirely.** The anonymous-user-GUID derivation,
  URL cleaning, session/user wiring, `fooconvert_event_data` filter, and
  template hook are already there. We'd write ~10 lines of glue instead
  of a new data layer.
- **Existing indexes help us.**
  `idx_session_event_lookup (session_id, event_type, sentiment, timestamp)`
  and `idx_anonymous_event_lookup (anonymous_user_guid, event_type, …)`
  already make "what is the latest consent state for this visitor?" a
  cheap query — no schema work.
- **Same mental model.** A consent decision *is* a user event; developers
  reading the code aren't surprised to find it in the events pipeline.
- **Free tooling.** Dashboard charts, Top Performers, and the Database
  admin tab's row count / size / orphan detection all just work.

Cons of Option B (reuse):

- **`post_id` is `NOT NULL`.** Consent is site-wide; it is not an event
  "for" a popup. We'd need a sentinel value (0, or a reserved `fc-consent`
  post). Every existing query that assumes `post_id` maps to a real popup
  in `wp_posts` needs an audit — and `delete_orphaned_events` specifically
  will happily delete any consent row whose sentinel post is missing.
- **Retention mismatch is the big one.** Current default retention is
  **14 days**; `FOOCONVERT_CRON_DELETE_EVENTS` purges everything older
  than `fooconvert_retention()` on a schedule. Proof of consent must
  survive ≥ 12 months (usually longer). We'd need a per-`event_type`
  retention override, and every destructive path has to honour it:
  `Event::delete_old_events`, `Event::delete_all_events`,
  `Event::delete_orphaned_events`, and the three admin buttons in
  `Admin\Settings::delete_*`. Missing the override in any one of those
  paths produces a silently GDPR-hostile bug: the evidence is destroyed
  exactly when the site is asked to prove it had consent.
- **"Delete All Events" blast radius grows.** An admin who clicks
  *Delete All Events* to reset analytics currently wipes analytics. Under
  Option B, it wipes their legal evidence of consent too. We can carve
  out consent rows, but it's a footgun we didn't have before.
- **Every analytics query now has to filter `event_type NOT IN (…)`.**
  Forgetting the filter leaks consent events into popup metrics
  ("conversions went up!"). Today that's impossible by construction.
- **`extra_data` is un-indexed longtext.** "Find me all consent records
  from ip_hash X" (a DSAR response) is a full scan + JSON parse. A
  dedicated table can put a simple index on `ip_hash` and `consent_id`.
- **Schema migrations touch a hot table.** Adding a column for, say, a
  future TCF consent string means `ALTER TABLE` on a table that gets
  writes on every popup open/close. A dedicated consent table is small
  and cold; altering it is safe at any traffic level.
- **Domain coupling.** Compliance bugs and analytics bugs now share a
  blast radius. An analytics refactor that changes how `extra_data` is
  serialised could silently break consent proof, and vice versa.
- **DSAR/export.** "Give me everything you hold about me" is a clean
  `SELECT * FROM fooconvert_consent_log WHERE consent_id = ?` with
  Option A. With Option B it's a filter on event_type in a multi-purpose
  table and the caller has to know which rows count.

Middle-ground Option B′: reuse the table, but also ship a general
"per-event-type retention override" map (e.g.,
`['consent_grant' => 365, 'consent_withdraw' => 365]`) and plumb it
through every delete path + each admin button. That fixes retention
and the Delete-All-Events footgun, but not the orphan-cleanup issue,
not the query-filter tax, not the un-indexed payload, not the domain
coupling. It's a plausible path if we're strongly opposed to a second
table, but it pays for reuse with lasting complexity in every destructive
code path.

**Decision: Option A (dedicated table).**

Short version of why: a consent record is a legal artefact with different
retention (≥ 12 months vs. 14 days default), different access pattern
(DSAR lookups by `consent_id` / `ip_hash`), and different deletion
semantics (must survive "Delete All Events" by design) than an analytics
event. The cost of a dedicated table is a single `CREATE TABLE` in
`Data\Schema` and a single cleanup cron hook — trivial, one-time, and
isolated. The cost of coupling is a permanent surcharge on every
destructive code path forever, in a domain where the failure mode is a
regulator-facing compliance bug. That's a poor trade.

We do, however, keep Option B in mind for one thing: the **transient**
consent interactions that *are* analytics-relevant — banner shown, layer
2 opened, "Accept all" clicked, "Reject all" clicked, "Customize" clicked
— belong in `fooconvert_events` against the banner popup's `post_id`
(reusing `FOOCONVERT_EVENT_TYPE_OPEN` / `CLICK` / `CLOSE`). Those are
engagement signals; the site owner wants to see them in the popup stats
dashboard alongside every other popup. Only the *authoritative* consent
record — the one that has to be produced in court — goes into the
dedicated table.

So the split is:

| Data                               | Where                       |
|------------------------------------|-----------------------------|
| Banner impressions & button clicks | `fooconvert_events` (existing pipeline) |
| Authoritative grant/withdraw record| `fooconvert_consent_log` (new)          |

### 5.3 JavaScript runtime

New entry, or extension of `src/frontend/`:

```
src/frontend/consent/
  index.js          // boot, pub/sub, public API
  store.js          // cookie read/write
  banner.js         // render / open / close — thin shim over popup runtime
  script-gate.js    // rewrites <script data-fc-consent=...> on page load
  consent-mode.js   // Google Consent Mode v2 defaults + updates
  geo.js            // opt-in geo gating
```

Public API (exposed on `globalThis.FooConvert.consent`):

```js
FooConvert.consent.get();                       // { necessary:true, statistics:false, ... }
FooConvert.consent.has('statistics');           // boolean
FooConvert.consent.onChange(cb);                // subscribe
FooConvert.consent.onGrant('marketing', cb);    // fires once per grant
FooConvert.consent.open();                      // re-open banner (for "Cookie settings" link)
FooConvert.consent.accept(['statistics']);      // programmatic (used by "Accept all" button)
FooConvert.consent.reject();                    // programmatic
```

### 5.4 Script gating

Two mechanisms, site owners can use either or both:

**Declarative (preferred):** authors write
```html
<script type="text/plain" data-fc-consent="statistics"
        src="https://www.googletagmanager.com/gtag/js?id=G-XXX"></script>
```
The runtime swaps `type` → `text/javascript` and loads it the moment the
`statistics` category is granted. `text/plain` means the browser never
executes it pre-grant — this is the approach used by every audited CMP
(Cookiebot, OneTrust, Axeptio) because it's the only one that actually
prevents execution.

**PHP-side rewrite:** for scripts that are enqueued by themes or other
plugins we can't edit, `ScriptGate::filter_script_loader_tag()` matches
against the inventory (`scripts[].match` regex) and rewrites the `<script>`
tag before output.

**Opt-out:** a script can be excluded from gating with the
`fooconvert_consent_gate_script` filter.

### 5.5 FooConvert's own event tracking

`Event::track()` already writes to `fooconvert_events`. Wrap the write in
a check: if the visitor has not granted `statistics`, only the minimum
needed for popup rendering (e.g., frequency capping via a short-lived
first-party cookie) happens, and no row is inserted. Aggregate metrics in
the dashboard remain honest because the denied-consent visitor's impression
legitimately should not be counted.

This gate is a one-line change at the top of `Event::track()`; the rest of
the pipeline is untouched.

### 5.6 Google Consent Mode v2

On every page load, before any Google tag:

```js
window.dataLayer = window.dataLayer || [];
function gtag(){ dataLayer.push(arguments); }
gtag('consent', 'default', {
  ad_storage:            'denied',
  ad_user_data:          'denied',
  ad_personalization:    'denied',
  analytics_storage:     'denied',
  functionality_storage: 'denied',
  personalization_storage:'denied',
  security_storage:      'granted',
  wait_for_update:       500,
});
```

On grant/withdraw, emit `gtag('consent', 'update', { … })` with the
category→signal mapping:

| Category    | Signals                                           |
|-------------|---------------------------------------------------|
| necessary   | `security_storage`                                |
| preferences | `functionality_storage`, `personalization_storage`|
| statistics  | `analytics_storage`                               |
| marketing   | `ad_storage`, `ad_user_data`, `ad_personalization`|

This is the **only** reason to ship Consent Mode v2 by default; without it,
sites running GA4 with a compliant banner just lose their analytics data
outright.

## 6. Admin UX

New menu: **FooConvert → Cookie Consent**, implemented with the existing
FooFields tabbed settings page (same pattern as `includes/Admin/Settings.php`).

Tabs:

1. **Overview** — enabled toggle, live preview, compliance checklist
   (green ticks where the config is compliant, red where it isn't, e.g.,
   "Reject button is hidden" or "Consent expiry > 12 months").
2. **Banner** — choose form factor, pick/create the popup, copy for
   each string, policy/privacy URLs.
3. **Categories** — edit the four defaults, add custom ones. Necessary
   is locked.
4. **Cookies & scripts** — inventory table (name, provider, purpose,
   category, retention). "Scan site" button does a one-shot crawl of
   the home page + 10 random posts, diffing cookies set with no consent
   vs. after full consent, to populate the inventory automatically
   (best-effort; user must review).
5. **Geo** — worldwide / EU+UK only. Explain the trade-off in plain
   language; recommend worldwide.
6. **Consent log** — table of recent records, "export CSV", "purge".
7. **Integrations** — GA4 / Consent Mode toggle, Meta Pixel, custom
   handles.

### 6.1 Compliance checklist widget

Shown on Overview and on the main FooConvert dashboard. Each row is a
hard-coded check against the current config. This is opinionated on
purpose — the whole point of a first-party CMP is that the plugin nudges
the site owner away from non-compliant configurations.

Checks include:
- Banner has a Reject button.
- Reject button has the same button style (not a text link) as Accept.
- No category other than Necessary is pre-checked.
- Dismiss is treated as reject.
- Expiry ≤ 365 days.
- Cookie policy URL is set.
- At least one cookie is listed per enabled category.
- Consent version has been bumped in the last 12 months (otherwise the
  whole base is due to re-prompt and the config is stale).

## 7. Integration with existing FooConvert popups

Non-consent popups (marketing bar, lead capture flyout, etc.) should not
fire their own tracking until the visitor has consented to the relevant
category. The change is scoped to two places:

1. `EventHooks` / `Event::track()` — gate on `statistics` as above.
2. The lead capture runtime — if the lead form itself is purely first-party
   (name + email into `wp_` tables), it's lawful basis "legitimate interest /
   contract performance" and does not need consent. We don't gate it.
   If the lead is forwarded to Mailchimp / MailPoet / a webhook (PRO), that
   integration needs to check consent for the `marketing` category before
   the forward.

For the popup display itself, we keep a short-lived first-party frequency
cookie (`fc_seen_<id>`, 24 h) even without consent — showing the popup
twice in one session to one visitor is not a privacy problem; it's a UX
problem, and the ePrivacy exemption for "strictly necessary" covers it.

## 8. Rollout plan

Phase 1 — foundations (this PR, implementation PR to follow):
- Category registry, consent store (cookie + option), JS API, minimal
  banner template (bar form factor), Consent Mode bridge, admin tab with
  the first three tabs of §6, migration that seeds the default four
  categories.

Phase 2:
- Flyout + overlay templates, cookie inventory + site scan, proof-of-consent
  log table, compliance checklist widget, shortcode + block.

Phase 3:
- Geo gating with CF-IPCountry, PRO: IAB TCF v2.2, per-integration consent
  pass-through for Mailchimp/MailPoet/Webhook.

Each phase ships behind `fooconvert_consent_settings.enabled` so nothing
breaks for existing sites that don't turn it on.

## 9. Testing

- **PHP unit tests** (`tests/php/Consent/…`): ConsentStore encode/decode,
  inventory CRUD, `Event::track()` gating, REST proof-of-consent writes.
- **JS unit tests** (Vitest): script-gate attribute rewriting,
  pub/sub, cookie parse/serialize.
- **E2E smoke test** (manual script in `bin/` for now): load a page with
  GA4 hardcoded, verify no `_ga` cookie pre-consent, grant, verify
  `analytics_storage=granted` in dataLayer and `_ga` now sets.
- **Accessibility pass**: axe-core on the banner in each form factor;
  keyboard-only walk-through; screen-reader announcement of banner on
  appearance.

## 10. Open questions

1. Do we surface geo gating in Phase 1 or wait until we have a well-tested
   resolver? Recommend wait — worldwide default is the safe fallback.
2. Should `EventHooks` emit anonymized events (no visitor id) under
   denied consent, so the dashboard keeps directional totals? Lean yes,
   but it's a policy decision for the product owner.
3. ~~Consent log in the existing `fooconvert_events` table (with a new
   event type) vs. a dedicated table?~~ **Resolved in §5.2.1** —
   dedicated `fooconvert_consent_log` for the authoritative record,
   but the banner's impressions/clicks stay in `fooconvert_events` so
   they show up in popup stats.
