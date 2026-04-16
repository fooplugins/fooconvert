# Dev Notes

## `package.json`

### `imports` option

The values configured here act like package names for the shared JavaScript and CSS files in the plugin.
Keeping them in one project avoids publishing separate packages, while still leaving room to split the
code later if needed.

```json
{
  "imports": {
    "#editor": "./src/editor/index.js",
    "#frontend": "./src/frontend/index.js"
  }
}
```

**_Note:_** These values are also used in `webpack.config.js` to configure the
`@wordpress/dependency-extraction-webpack-plugin` for the generated `.asset.php` files.

### `scripts` option

Contains the commands used to build and publish the project.

```json
{
  "scripts": {
    "check-updates": "npx npm-check-updates",
    "test:js": "vitest run",
    "test:js:watch": "vitest",
    "build:dev": "cross-env NODE_ENV=development wp-scripts build --output-path=assets && npm run copy",
    "build": "wp-scripts build --output-path=assets && npm run copy",
    "copy": "node build/copy-assets.mjs",
    "i18n": "node build/make-pot.mjs",
    "composer:install": "composer install --prefer-dist --optimize-autoloader --no-dev",
    "composer:update": "composer update --optimize-autoloader",
    "composer:refresh": "composer dump-autoload --optimize",
    "package:create-zip": "npm run build && npm run i18n && npm run composer:refresh && node build/create-zip.mjs",
    "package:deploy": "node build/freemius-deploy.mjs"
  }
}
```

#### `check-updates`

Utility script for [npm-check-updates](https://www.npmjs.com/package/npm-check-updates).

#### `build` and `build:dev`

These scripts are wrappers around [wp-scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) 
`build` command with the output set to the `assets` folder, followed by `build/copy-assets.mjs`.
`build` runs in production mode. `build:dev` runs the same pipeline with `NODE_ENV=development`.

**_Note:_** The default `@wordpress/scripts/config/webpack.config` is being extended by the projects 
`webpack.config.js` file.

#### `i18n`

The `build/make-pot.mjs` file wraps the [wp-cli](https://developer.wordpress.org/cli/commands/i18n/) `make-pot`
command and fills in the `.pot` headers that `--headers` does not reliably provide.

#### `composer:*`

Composer commands for installing dependencies, updating them, and refreshing the autoloader.

* `composer-install` - Install packages in `composer.json` and generate the classmap for the plugin PHP files.
* `composer-update` - Update installed packages and refresh the autoloader cache.
* `composer-refresh` - Recreate the autoloader cache so the classmap stays current.

#### `package:*`

These scripts are used to bundle the plugin into a `.zip` and deploy it to the Freemius dashboard.

* `package:create-zip` - Run the `build`, `i18n`, `composer:refresh` and `build/create-zip.mjs` commands in series to produce 
a new `<name>.v<version>.zip` file in the `dist` folder.
* `package:deploy` - Use the .gitignored `fs-config.json` file in the projects root directory to deploy the 
current versions `.zip` file to the freemius dashboard.

## `webpack.config.js`

This project extends the default `@wordpress/scripts/config/webpack.config` to perform the following:

1. Add the `editor` and `frontend` entry points to the build.
2. Configure the dependencies for entries discovered by the default configuration to include the `editor` and 
`frontend` scripts based on there include path.
3. Configure the `@wordpress/dependency-extraction-webpack-plugin` to handle the `editor` and `frontend` scripts.
4. Add `raw-loader` to the default `module.rules` and configure it to import `.html` files.
