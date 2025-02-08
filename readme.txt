=== FooConvert - Boost Conversions & Lead Generation ===
Contributors: bradvin, steveush, fooplugins
Tags: sales-optimization,conversion-tools,lead-generation,marketing,wordpress-ecommerce
Requires at least: 6.5
Requires PHP: 7.4
Tested up to: 6.7
Stable tag: 1.1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Turn clicks into conversions, visitors into customers – FooConvert is the ultimate catalyst for online success!

== Description ==

FooConvert is a powerful WordPress plugin designed to help you optimize your website for maximum conversions. With high-converting widgets and advanced analytics, FooConvert helps you generate more leads, boost sales, and enhance customer engagement effortlessly.

[Launch Your Own FooConvert Demo](https://app.instawp.io/launch?t=fooconvert-free-demo&d=v2)

[Visit the FooConvert Homepage](https://fooplugins.com/fooconvert/)

= FooConvert Key Features =

- Add high converting widgets to your pages:
  - Popups
  - Bars
  - Flyouts
- Design and edit your widgets within the Gutenberg block editor.
  - Customize widget appearance, colors, and positioning.
  - Add any blocks to your widgets content.
  - Add shortcodes within widget content.
- Widget display rules:
  - locations (e.g. entire site, homepage, specific pages, etc)
  - exclusions (e.g. exclude certain pages or taxonomies)
  - users (e.g. logged-in users only, non-logged-in users, specific roles, etc)
- Widget open triggers:
  - Page load
  - Exit intent
  - Page scroll
  - Timer
  - Anchor click
  - Element click
  - Anchor visible
- Track widget analytics:
  - Base metrics : views, visitors, engagements.
  - Daily activity charts for views, visitors and engagements.
- Analytics Dashboard:
  - Top Performers by Views and Engagement.
- Widget data retention for 14 days only.
  - Longer retention period available with FooConvert PRO.

= FooConvert Benefits =

- Increase Conversion Rates: Turn more visitors into paying customers!
- Boost Lead Generation: Capture more qualified leads for your business!
- Optimize Sales Funnel: Streamline your sales process for better results!
- Sell More Products: Generate more revenue with high-converting widgets!
- Data-Driven Decisions: Make informed choices based on real-time analytics!

= FooConvert PRO Features =

PRO gives you insights into your widget performance and saves you time! Features include:

- Longer retention period for widget analytics. You can now track widget performance as long as your like!
- More advanced widget analytics, including:
  - Clicks and click-through-rates.
  - Conversions and conversion rates.
  - Engagement sentiment and engagement ratios (positive and negative).
  - Daily activity charts for clicks, conversions and engagements.
- More dashboard metrics, including:
  - Top Performers by Engagement Rate, Clicks, Click Rate, Conversions, Conversion Rate.
- 9 professionally designed templates:
  - No design skills needed!
  - Proven to convert and grab attention!
  - Create stunning, high-converting widgets in minutes – just pick, customize, publish!

== Installation ==

1. Upload `fooconvert` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the settings page to change any of the settings
4. Enjoy the plugin!

== Frequently Asked Questions ==

= Are there any limitations? (Number of widgets or views?)=

There is NO limit to the number of widgets you can create.
There is NO limit to the number of views each widget can have.
Most alternative conversion solutions limit how many widgets you can create or limit how many times each widget is displayed. Not FooConvert!

The only limitation is the data retention for widget analytics. The data is stored for 14 days only.
FooConvert PRO has a longer retention period for widget analytics, so you can track widget performance as long as your like!

= Is FooConvert compatible with my theme? =

FooConvert is designed to work with most WordPress themes. If you encounter any compatibility issues, please contact our support team.

= Is FooConvert compatible with Classic Editor plugin? =

Not really. The FooConvert widget designer is built on top of the Gutenberg block editor, which means you need to enable the block editor to create and edit widgets. You will not be able to easily create or edit widgets using the Classic Editor.
If you have the Classic Editor plugin installed, You will need to enable the setting "Allow users to switch editors" from Settings -> Writing, so that you can switch to the block editor when adding or editing FooConvert widgets.

= Can I use FooConvert with other e-commerce plugins? =

Yes, FooConvert integrates seamlessly with the popular e-commerce plugin WooCommerce.

= Does FooConvert slow down my website? =

No, FooConvert is optimized for performance and has minimal impact on your website's loading speed.

== Screenshots ==

1. Widget Editor
2. FooConvert Dashboard
3. Widget Stats
4. FooConvert Settings
5. Template Selector
6. Editor Settings

== Repository ==

The source code for this plugin is available publicly on GitHub [here](https://github.com/fooplugins/fooconvert).

== Changelog ==

= 1.1.1 =
* Date Updated : 8 Feb 2025
* FooConvert PRO now available!
* Updated whole plugin to use PSR4 autoloading standards.
* Fixed admin path issues where assets were not loading.

= 1.0.1 =
* Date Updated : 27 Jan 2025
* Added support for embeds within widget content.
* Added new filter 'fooconvert_can_create_event' to allow for disabling event creation.
* Added setting to hide Dashboard panels.
* Updated the widget metrics update job to be faster!
* Fixed default width of popups.
* Updated to Freemius SDK 2.11.0

= 1.0.0 =
* Date Updated : 7 Jan 2025
* Fixed bug with widgets closing anchors not working as expected.
* Added support for shortcodes within widget content.

= 0.0.26 =
* Date Updated : 26 Dec 2024
* Added demo content (black friday scroll flyout & black friday exit intent popup).
* Added top performer panel to the dashboard.
* Added cron job to calculate widget stats (for top performers panel).
* Added cron job to delete old events (outside of retention period).
* Added box shadow to border tools panel in block editor.
* Updated template selector popup.
* Updated to Freemius SDK 2.10.1

= 0.0.16 =
* Date Updated : 3 Dec 2024
* Added compatability mode on widgets, so that blocks that generate scripts will now work.
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
* Added event table to the database to store conversion events for each widget.

= 0.0.11 =
* Date Updated : 2 Nov 2024
* Fixed demo content.
* Lots of updates to the widgets!

= 0.0.10 =
* Fixed wrong redirect after plugin activation.
* Added 2 demo bars that are created on activation.

= 0.0.9 =
* First public release!