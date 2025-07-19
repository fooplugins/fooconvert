# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## FooConvert - WordPress Conversion Optimization Plugin

FooConvert is a WordPress plugin for creating conversion-focused elements like popups, bars, and flyouts to increase leads and conversions. The plugin uses a combination of PHP, JavaScript (React/WordPress blocks), and CSS to provide both frontend functionality and Gutenberg block editor integration.

## Development Commands

### Build & Development
```bash
# Development build with file watcher
npm start

# Production build
npm run build

# Development build without watcher (single build)
npm run develop

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

### Package & Deploy
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
# Check code standards (if configured)
npm run lint:js
npm run lint:css

# Type checking (if configured)
npm run typecheck
```

## Architecture Overview

### Directory Structure

```
fooconvert/
├── assets/                    # Built assets (generated from src/)
├── includes/                  # Core PHP files
│   ├── Admin/                 # Admin interface classes
│   ├── Components/            # Reusable Gutenberg components
│   ├── Data/                  # Database models & queries
│   ├── Widgets/               # Core widget classes
├── pro/                       # Premium features (paid only)
├── src/                       # Source JavaScript & SCSS
│   ├── admin/                 # Admin-specific resources
│   ├── blocks/                # Custom Gutenberg blocks
│   ├── editor/                # Gutenberg editor integrations
│   ├── frontend/              # Frontend JavaScript
│   └── widgets/               # Widget-specific assets
├── vendor/                    # Composer dependencies
└── languages/                 # Translation files
```

### Key Technologies
- **PHP 7.4+**: Server-side logic
- **WordPress Blocks API**: Custom Gutenberg blocks
- **React**: Gutenberg editor interfaces
- **Webpack**: Module bundling via @wordpress/scripts
- **Freemius**: Premium licensing & updates

### Core Components

#### Gutenberg Blocks
- **ExampleBlock**: Sample custom block
- **BarWidget**: Top/bottom bar widgets
- **FlyoutWidget**: Side panel widgets
- **PopupWidget**: Modal popup widgets

#### Pro Blocks (Premium)
- **Countdown**: Countdown timer block
- **Coupon**: Discount code display
- **SignUp**: Newsletter signup forms
- **Advanced blocks** in pro/src/blocks/

#### Custom Post Types
- `fc-bar`: Bar widgets
- `fc-flyout`: Flyout widgets  
- `fc-popup`: Popup widgets

### Build System

#### Build Entry Points
- `editor`: Core editor functionality
- `frontend`: Core frontend functionality
- `editor-pro`: Premium editor features
- `frontend-pro`: Premium frontend features

#### Module Aliases (package.json imports)
- `#editor` → `./src/editor/index.js`
- `#frontend` → `./src/frontend/index.js`
- `#editor-pro` → `./pro/src/editor/index.js`
- `#frontend-pro` → `./pro/src/frontend/index.js`

### Configuration Files
- **webpack.config.js**: Extends @wordpress/scripts for custom entry points
- **gulpfile.js**: Creates distribution zip files
- **make-pot.mjs**: Generates translation .pot files
- **copy-assets.mjs**: Copies assets after webpack build

### Database Structure
- **Custom Tables**: `fooconvert_events` for tracking conversion events
- **Post Meta**: Widget-specific settings stored as post meta
- **Options**: Plugin-wide settings stored in wp_options

### Asset Handles
- **fc-editor**: Editor assets handle
- **fc-frontend**: Frontend assets handle
- **fc-editor-pro**: Premium editor handle
- **fc-frontend-pro**: Premium frontend handle

### Namespace Conventions
- **Core Plugin**: Global namespace (functions are prefixed with `fooconvert_`)
- **Pro Plugin**: `FooPlugins\FooConvert\Pro` namespace
- **Providers**: `FooPlugins\FooConvert\Pro\Providers` namespace for all Pro provider classes

### Developer Notes

#### Block Development
- Blocks use WordPress Block Registration API
- Each block has separate editor/frontend builds
- Block.json files define metadata and assets
- Component system uses custom WordPress components

#### CSS/SCSS
- Uses SCSS compilation via Webpack
- RTL support automatically generated
- Responsive design standards

#### JavaScript
- Uses WordPress data modules (@wordpress/* packages)
- React hooks for state management
- Custom element system for frontend widgets

#### Hooks & Filters
- **JavaScript**: WordPress hooks system via @wordpress/hooks
- **PHP**: WordPress action/filter system
- **Events**: Custom event system for tracking conversions