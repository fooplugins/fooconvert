# FooConvert

FooConvert is a WordPress conversion optimization plugin for creating conversion-focused elements like popups, bars, and flyouts to increase leads and conversions. The plugin uses PHP, JavaScript, React, WordPress block-editor packages, and CSS/SCSS to provide both frontend functionality and Gutenberg editor integration.

## Product Overview

FooConvert allows you to create 3 popup types:
- bars
- flyouts
- overlays

These popups are designed and edited with the Gutenberg block editor through a single popup post type. When creating a popup, you can either start from scratch or choose from predefined templates.

Each popup supports configuration for:
- Display Rules: control where the popup is shown, for example all pages, a specific page, or a specific post type.
- Open Trigger: control when the popup opens, for example immediate, timer, scroll, anchor click, or exit intent.
- Appearance: control style, colors, size, layout, fonts, and media.
- Content: allow supported blocks or shortcodes inside the popup content area.

## Free and PRO Scope

The main plugin now includes these formerly PRO-oriented content features:
- `fc/countdown`
- `fc/coupon`
- `fc/sign-up`
- the migrated template pack and required template media
- Google font settings/runtime, including template-required fonts
- lead capture
- basic leads viewer
- lead export

The PRO plugin now focuses on premium extensions:
- WooCommerce-oriented functionality and future Woo trigger/runtime work
- lead integrations such as Mailchimp, MailPoet, and Webhook
- advanced analytics
- longer retention and other premium analytics-related features

## Admin Surface

FooConvert has an admin dashboard with panels such as:
- Getting Started
- Top Performers
- Help
- Premium Addons / premium feature discovery

There is also an admin page for popup stats, which shows metrics and recent activity for popups. Metrics include events, views, visitors, and engagements, with PRO extending analytics beyond the basic free surface.

## Development Commands

### Build and Development

```bash
# Production build
npm run build

# Development build (single run, non-production mode)
npm run build:dev

# Check for package updates
npm run check-updates
```

### PHP Dependencies

```bash
# Install composer dependencies for production
npm run composer:install

# Update composer dependencies
npm run composer:update

# Refresh composer autoloader
npm run composer:refresh
```

### Package and Deploy

```bash
# Create release zip package
npm run package:create-zip

# Deploy to Freemius (requires fs-config.json)
npm run package:deploy

# Generate translation files
npm run i18n
```

### Testing

```bash
npm run test:js
npm run test:js:watch
npm run test:php
```

## Architecture Overview

### Directory Structure

```text
fooconvert/
├── assets/                    # Built assets (generated from src/)
├── build/                     # Build, packaging, and deploy scripts
├── includes/                  # Core PHP files
│   ├── Admin/                 # Admin interface classes
│   ├── Components/            # Reusable Gutenberg components
│   ├── Data/                  # Database models and queries
│   ├── Popups/                # Core popup classes
├── pro/                       # Premium features
├── src/                       # Source JavaScript and SCSS
│   ├── admin/                 # Admin-specific resources
│   ├── blocks/                # Custom Gutenberg blocks
│   ├── editor/                # Gutenberg editor integrations
│   ├── frontend/              # Frontend JavaScript
│   └── popups/                # Popup-specific assets
├── vendor/                    # Composer dependencies
└── languages/                 # Translation files
```

### Key Technologies

- PHP 7.4+
- WordPress Blocks API
- React
- Webpack via `@wordpress/scripts`
- Freemius

### Core Components

#### Gutenberg Blocks

- `ExampleBlock`: sample custom block
- `BarPopup`: top/bottom bar popups
- `FlyoutPopup`: side panel popups
- `OverlayPopup`: modal popup popups

#### PRO Blocks and Extensions

- `Countdown`
- `Coupon`
- `SignUp`
- premium editor plugins live in `pro/src/editor/plugins/`

#### Custom Post Types

- `fc-bar`: bar popups
- `fc-flyout`: flyout popups
- `fc-popup`: popup popups

## Build System

### Build Entry Points

- `editor`: core editor functionality
- `frontend`: core frontend functionality
- `editor-pro`: premium editor features
- `frontend-pro`: premium frontend features

### Build Notes

- `npm run build` always produces the full build.
- `npm run build:dev` runs the same build pipeline in development mode for easier debugging.

### Module Aliases

- `#editor` → `./src/editor/index.js`
- `#frontend` → `./src/frontend/index.js`
- `#editor-pro` → `./pro/src/editor/index.js`
- `#frontend-pro` → `./pro/src/frontend/index.js`

### Configuration Files

- `webpack.config.js`: extends `@wordpress/scripts` for custom entry points
- `build/create-zip.mjs`: creates the distribution zip file
- `build/freemius-deploy.mjs`: uploads the distribution zip to Freemius
- `build/make-pot.mjs`: generates the translation `.pot` file
- `build/copy-assets.mjs`: copies assets after webpack build
- `vitest.config.mjs`: Vitest configuration and import aliases

## Data and Runtime Conventions

### Database Structure

- custom table: `fooconvert_events` for tracking conversion events
- post meta: popup-specific settings
- options: plugin-wide settings in `wp_options`

### Asset Handles

- `fc-editor`
- `fc-frontend`
- `fc-editor-pro`
- `fc-frontend-pro`

### Namespace Conventions

- core plugin: global namespace with `fooconvert_`-prefixed functions
- PRO plugin: `FooPlugins\FooConvert\Pro`
- providers: `FooPlugins\FooConvert\Pro\Providers`

## Developer Notes

### Block Development

- blocks use the WordPress Block Registration API
- blocks have separate editor and frontend builds
- `block.json` files define metadata and assets
- the component system uses custom WordPress components

### CSS and SCSS

- SCSS is compiled through webpack
- RTL support is generated automatically
- responsive design patterns are expected

### JavaScript

- uses WordPress data modules from `@wordpress/*`
- uses React hooks for state management
- uses a custom element system for frontend popups

### Hooks and Filters

- JavaScript hooks use `@wordpress/hooks`
- PHP hooks use the standard WordPress action/filter system
- conversion tracking uses custom events
