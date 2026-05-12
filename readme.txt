=== Popup Builder for Block Editor - FooConvert ===
Contributors: fooplugins, bradvin, steveush
Tags: popups,bars,flyouts,conversion,marketing
Requires at least: 6.5
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 2.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Build WordPress bars, flyouts, and overlays in the block editor with templates, targeting, triggers, leads, and analytics.

== Description ==

FooConvert lets you create bars, flyouts, and overlays directly in the WordPress block editor.
It includes popup templates, built-in popup blocks, display rules, open triggers, lead capture, popup stats, and styling controls for building campaigns without leaving wp-admin.

[Read the FooConvert documentation](https://fooplugins.com/documentation/fooconvert/)

[Try the FooConvert demo](https://app.instawp.io/launch?t=fooconvert-free-demo&d=v2)

= What FooConvert Includes =

- Bars, flyouts, and overlays managed from the same workflow
- 15 bundled templates across bars, flyouts, and overlays
- Sign Up, Countdown, and Coupon blocks
- Support for WordPress core blocks, embeds, and shortcodes inside popup content
- Display rules for locations, exclusions, and user targeting
- Triggers for page load, timers, scroll depth, exit intent, and element interactions
- Leads capture with CSV export
- Popup stats with daily activity charts
- Google Fonts support and popup styling controls

= Popup Types =

- `Bar`: top or bottom announcements, promo strips, newsletter bars, and sticky calls to action.
- `Flyout`: slide-in messages and sidebar-style opt-ins that stay visible without taking over the whole screen.
- `Overlay`: centered modal popups for forms, announcements, coupon offers, and embedded content.

= Building Popup Content =

- Use WordPress core blocks and most third-party blocks inside popup content.
- Shortcodes and embedded content are supported inside popups.
- `Sign Up` block: capture name and email, or run email-only forms, with configurable labels, placeholders, button text, and success behavior.
- `Countdown` block: build evergreen or fixed countdown campaigns with editable labels and optional close-on-expire behavior.
- `Coupon` block: show a coupon code with copy-to-clipboard behavior, custom button text, copied message, and optional close-on-copy behavior.

= Templates And Design Controls =

- Start from one of the 15 bundled templates or build from scratch.
- Adjust colors, spacing, borders, radius, shadows, dimensions, and typography in the editor.
- Add background images to popup designs.
- Choose a global editor background so the editing canvas is easier to work with.
- Register Google Fonts from the settings screen and reuse fonts bundled with templates.
- Build responsive popups for desktop and mobile layouts.

= Display Rules =

- Show popups on the entire site, front page, blog index, search results, no-results pages, and 404 pages.
- Target specific posts, pages, public custom post types, public taxonomies, and post type archives.
- Add exclusions so a popup appears only where it makes sense.
- Target all users, only logged-in users, only logged-out users, or specific user roles.

= Open Triggers =

- Open on page load.
- Open after a timer elapses.
- Open after a visitor scrolls a chosen percentage of the page.
- Open on exit intent.
- Open when an anchor is clicked.
- Open when an anchor becomes visible.
- Open when a selected element is clicked.

= Leads And Popup Stats =

- View captured leads from the built-in Leads screen in wp-admin.
- Search, sort, bulk delete, and export selected leads to CSV.
- View per-popup stats including views, unique visitors, engagements, and daily activity charts.
- Open popup stats directly from the popup list table.
- Popup analytics data is retained for 14 days by default.

= Settings And Admin Tools =

- Add demo content to see example bars, flyouts, and overlays on a real site.
- Choose the popup editor background for all popup types.
- Enable debug mode when troubleshooting an issue.
- Hide selected dashboard panels or promo messages if you want a cleaner admin area.
- Review database stats and remove old or orphaned event data.
- Copy system information when contacting support.

= Typical Use Cases =

- Newsletter signup bar on the homepage.
- Coupon or limited-time offer overlay.
- Slide-in lead magnet or content upgrade.
- Announcement bar for site-wide notices.
- Embedded video or shortcode-based popup content.

= Pro Version =

The Pro version of FooConvert includes:

- AI Popup Builder.
- A/B Testing.
- Sales revenue attribution.
- More blocks for your popups (WooCommerce Apply Coupon Block; WooCommerce Free Shipping Progress Block; Confetti Block).
- WooCommerce Display Rules.
- WooCommerce triggers for ecommerce targeting.
- Lead automation integrations (send to Mailchimp; Mailpoet; Brevo; Custom Webhook).
- Advanced analytics + longer retention.

= AI Popup Builder =

Create popup drafts from a chat-based builder that uses FooConvert templates, supported block metadata, conversion guidance, and optional brand context to generate editable bars, flyouts, and overlays.

- Generate draft popup content from guided prompts and starter campaign ideas.
- Save generated designs as draft FooConvert popups for editing in the block editor.
- Use saved Brand Context, local or remote brand extraction, and selected block catalogs to guide output.
- Generate, import, reuse, or delete AI-created popup images and background images when media generation is available.
- Review the strategy summary, conversion checklist, activity log, and live preview before editing the draft.

= A/B Testing =

Run controlled popup experiments so you can compare a published control against one or more variants and choose a winner from real visitor behavior.

- Create A/B testing experiments from existing published popups and duplicate variants from the control or another variant.
- Split traffic across participants with configurable weights and persistent visitor assignments.
- Optimize for clicks, conversions, or leads.
- Review views, clicks, conversions, leads, goal rate, uplift, confidence, and winning participant status.
- Enable an automatic winner based of your chosen criteria.

= Sales Revenue Attribution =

Connect FooConvert popup engagement to WooCommerce orders so popup performance can be measured by attributed revenue, not only views and clicks.

- Attribute sales to the latest qualifying popup click or conversion before the order.
- Choose whether sales are credited when the order is created or when payment is completed.
- Configure the attribution lookback window, dedupe mode, and whether multiple orders in one session can be credited.
- Store attribution details on WooCommerce orders and FooConvert sale events.
- View attributed revenue, sale counts, order rows, recent sales, and top revenue popups in admin reporting.

= PRO Blocks =

Add WooCommerce-focused and engagement-focused blocks to build more persuasive store campaigns inside FooConvert popups.

- `Apply Coupon` block: search WooCommerce coupons, apply them from a popup, and optionally close or redirect after apply.
- `Apply Coupon` customization: set layout, label visibility, button style, override applied text, success messages, and friendly error messages.
- `Free Shipping Progress` block: show locked, almost-there, unlocked, or unavailable content based on live cart progress.
- `Free Shipping Progress` customization: set the threshold, almost-there percentage, tokenized messages, progress bar visibility, percentage display, and styles.
- `Confetti` block: trigger a confetti burst when the popup opens. A great effect for coupons or sales popups.

= WooCommerce Display Rules =

Target popups to WooCommerce-specific locations so store campaigns only appear on the shopper journeys where they make sense.

- Show popups on the shop, cart, checkout, and My Account pages.
- Target any product page, product tag archive, or product category archive.
- Target any page that contains a WooCommerce template.
- Target any WooCommerce page, including cart, checkout, shop, account, and product areas.
- Use these rules alongside the standard FooConvert include and exclusion rules.

= WooCommerce Triggers =

Open popups in response to shopper behavior, cart changes, coupon events, checkout events, and product intent signals.

- Trigger on product added to cart, product removed from cart, or cart updated.
- Limit cart triggers by selected products or subtotal comparisons.
- Trigger on successful coupon application or invalid coupon attempts.
- Trigger on checkout page view, checkout exit intent, checkout errors, or payment failure.
- Trigger on cart idle time, product page views, or high-intent product engagement based on scroll depth, time on page, or repeat views.

= Lead Automation Integrations =

Send captured leads to external marketing and automation tools after FooConvert saves them locally.

- Subscribe leads to a selected Mailchimp audience, with connection testing and list retrieval.
- Subscribe leads to a selected MailPoet list when MailPoet is installed.
- Add or update contacts in a selected Brevo list, including first-name and last-name attributes when available.
- POST leads as JSON to a custom webhook endpoint for Zapier, Make, n8n, or custom workflows.
- Add optional webhook headers and review integration error logs from the Leads settings.

= Advanced Analytics + Retention =

Expand popup reporting with deeper engagement metrics, richer activity charts, WooCommerce revenue data, and configurable data retention.

- Track clicks, click-through rate, conversions, conversion rate, engagement rate, positive and negative engagements, and overall sentiment.
- Include unique visitors, unique sessions, returning visitors, closes, and attributed sales in popup metric queries.
- Add clicks, conversions, closes, sales, and update annotations to popup activity charts.
- Compare week-over-week changes for views, engagements, clicks, and conversions in dashboard metric options.
- Extend analytics retention from the free default to the Pro default of 30 days, with configurable retention and longer activity ranges.



== Installation ==

1. Upload `fooconvert` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the settings page to change any of the settings
4. Enjoy the plugin!

== Frequently Asked Questions ==

= Are there any limitations? (Number of popups or views?)=

There is NO limit to the number of popups you can create.
There is NO limit to the number of views each popup can have.
The main limitation is analytics retention. Popup analytics data is stored for 14 days by default.

= Is FooConvert compatible with my theme? =

FooConvert is designed to work with most WordPress themes. If you encounter any compatibility issues, please contact our support team.

= Can I use WordPress blocks, embeds, or shortcodes inside a popup? =

Yes. FooConvert is built for the block editor and supports WordPress core blocks, most third-party blocks, embedded content, and shortcodes inside popup content.

= Where are captured leads stored? =

Captured leads are stored in WordPress and can be viewed from the Leads screen in wp-admin. You can search, sort, bulk delete, and export selected leads to CSV.

= Is FooConvert compatible with Classic Editor plugin? =

Not really. The FooConvert designer is built on top of the Gutenberg block editor, which means you need to enable the block editor to create and edit popups. You will not be able to easily create or edit popups using the Classic Editor.
If you have the Classic Editor plugin installed, You will need to enable the setting "Allow users to switch editors" from Settings -> Writing, so that you can switch to the block editor when adding or editing FooConvert popups.

= Can I use FooConvert on ecommerce sites? =

Yes. FooConvert can be used on ecommerce sites for announcements, list building, coupon messaging, and timed offers.
WooCommerce-specific targeting rules and store event triggers are available in the Pro version.

= Does FooConvert slow down my website? =

No, FooConvert is optimized for performance and has minimal impact on your website's loading speed.

== Screenshots ==

1. Popup Editor
2. FooConvert Dashboard
3. Stats
4. FooConvert Settings
5. Template Selector
6. Editor Settings

== Repository ==

The source code for this plugin is available publicly on GitHub [here](https://github.com/fooplugins/fooconvert).

== Changelog ==

= 2.0.1 =
* Date Updated : 12 May 2026
* New : Added new Split Layout block that templates will use.
* Updated : Removed the FooConvert branding header from the admin pages.
[//]: # fs_premium_only_begin
* New : Added 5 WooCommerce templates : Almost Free Shipping Bar; Cart Unlock Flyout; High Intent Product Offer Popup; Cart Idle Rescue Popup; Checkout Exit Save Popup; 
[//]: # fs_premium_only_end

= 2.0.0 =
* Date Updated : 20 Apr 2026
* New : Launched FooConvert 2.0 with a streamlined popup builder for bars, flyouts, and overlays in one consistent workflow.
* New : Added a guided popup type chooser with richer template previews so you can launch campaigns faster.
* New : Added 15 bundled templates across bars, flyouts, and overlays for quicker campaign launches.
* New : Added the Sign Up, Countdown, and Coupon blocks for lead capture and promotional campaigns.
* New : Added the Leads screen with filtering and export.
* New : Added Google Fonts support, bundled font listings, and a global popup editor background setting for stronger on-brand design control.
* Updated : Refined display rule controls and popup list summaries so campaign targeting is easier to review at a glance.
* Updated : Polished dashboard widgets, recent activity, and popup stats previews for clearer performance reporting.
* Updated : Added a confirmation step before deleting analytics data for safer admin workflows.
* Updated : Refreshed template previews and demo content to better showcase launch-ready campaigns.
[//]: # fs_premium_only_begin
* New : Added WooCommerce display rules for shop, cart, checkout, account, and product-focused journeys.
* New : Added WooCommerce triggers for cart, coupon, checkout, and product-intent shopper events.
* New : Added Apply Coupon, Free Shipping Progress, Free Shipping Text, Free Shipping Bar, Cart Threshold Progress, and Confetti blocks for more persuasive store campaigns.
* New : Added WooCommerce revenue attribution, sales metrics, and recent sales dashboard reporting to connect popup performance with revenue.
* New : Added A/B testing experiments with automated winner selection for faster campaign optimization.
[//]: # fs_premium_only_end

= 1.2.6 =
* Date Updated : 15 Dec 2025
* Fixed : Fixed error when creating bars and flyouts in block editor.
* Fixed : In block editor, disabled autosave for our custom post types.
* Fixed : Updated Freemius SDK to 2.12.3
* Fixed : Updated dashboard copy and reworded admin screens for clearer popup terminology.

= 1.2.5 =
* Date Updated : 28 Sep 2025
* Updated to Freemius SDK 2.12.2
[//]: # fs_premium_only_begin
* New : Added MailPoet integration for leads.
[//]: # fs_premium_only_end

= 1.2.4 =
* Date Updated : 19 Jul 2025
[//]: # fs_premium_only_begin
* New : Added Mailchimp integration for leads.
* New : Added webhook integration for leads. (Zapier, N8N, Make.com, etc)
[//]: # fs_premium_only_end

= 1.2.3 =
* Date Updated : 14 Jul 2025
* Fixed : fixed template selector when creating a new popup.

= 1.2.2 =
* Date Updated : 11 Jul 2025
[//]: # fs_premium_only_begin
* New : Updated the PRO templates to use new blocks.
* New : Added Signup block for capturing signup forms.
* New : Added Countdown block for countdown timers.
* New : Added Coupon block for easily copying coupon codes.
[//]: # fs_premium_only_end
* Fixed : multiple bugs and issues found when testing.
* Updated to Freemius SDK 2.12.1

= 1.2.1 =
* Date Updated : 8 Jun 2025
[//]: # fs_premium_only_begin
* New : Added leads admin page to show all captured leads, including export option.
[//]: # fs_premium_only_end

= 1.2.0 =
* Date Updated : 8 Jun 2025
* New : Added editor controls to add BG images to popups.
* New : Added new popup setting to only show once per user.
[//]: # fs_premium_only_begin
* New : Added 5 new professionally designed templates!
[//]: # fs_premium_only_end
* Fixed : multiple bugs and issues found when testing.
* Updated to Freemius SDK 2.12.0

= 1.1.1 =
* Date Updated : 8 Feb 2025
* FooConvert PRO now available!
* Updated whole plugin to use PSR4 autoloading standards.
* Fixed admin path issues where assets were not loading.

= 1.0.1 =
* Date Updated : 27 Jan 2025
* Added support for embeds within popup content.
* Added new filter 'fooconvert_can_create_event' to allow for disabling event creation.
* Added setting to hide Dashboard panels.
* Updated the popup metrics update job to be faster!
* Fixed default width of popups.
* Updated to Freemius SDK 2.11.0

= 1.0.0 =
* Date Updated : 7 Jan 2025
* Fixed bug with popups closing anchors not working as expected.
* Added support for shortcodes within popup content.

= 0.0.26 =
* Date Updated : 26 Dec 2024
* Added demo content (black friday scroll flyout & black friday exit intent popup).
* Added top performer panel to the dashboard.
* Added cron job to calculate popup stats (for top performers panel).
* Added cron job to delete old events (outside of retention period).
* Added box shadow to border tools panel in block editor.
* Updated template selector popup.
* Updated to Freemius SDK 2.10.1

= 0.0.16 =
* Date Updated : 3 Dec 2024
* Added compatability mode on popups, so that blocks that generate scripts will now work.
* Events now have a conversion bool field.
* Updated to Freemius SDK 2.9.0.
* Lots of updates and bug fixes!

= 0.0.15 =
* Date Updated : 16 Nov 2024
* Added dashboard page, which includes panel for demo content and help.
* Events now support subtypes and sentiment.
* Enabled Addon Support.

= 0.0.12 =
* Date Updated : 9 Nov 2024
* Added event table to the database to store conversion events for each popup.

= 0.0.11 =
* Date Updated : 2 Nov 2024
* Fixed demo content.
* Lots of updates to the popups!

= 0.0.10 =
* Fixed wrong redirect after plugin activation.
* Added 2 demo bars that are created on activation.

= 0.0.9 =
* First public release!
