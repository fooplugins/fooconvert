# Dev Notes

## `package.json`

### `imports` option

The values configured here can be thought of as packages for the shared JavaScript and CSS 
files for the plugin. Instead of publishing separate packages and then importing them, defining them here
allows us to keep everything in one project. If in the future we do decide to split the code into its own
packages this should also make the process simpler.

```json
{
  "imports": {
    "#editor": "./src/editor/index.js",
    "#frontend": "./src/frontend/index.js"
  }
}
```

**_Note:_** These values are also used in the `webpack.config.js` file to configure the 
`@wordpress/dependency-extraction-webpack-plugin` to automatically include the handles for these scripts
in the generated `<name>.asset.php` files output in the `assets` folder.

### `scripts` option

Contains various commands to build and publish the project.

```json
{
  "scripts": {
    "check-updates": "npx npm-check-updates",
    "build": "wp-scripts build --output-path=assets",
    "start": "wp-scripts start --output-path=assets",
    "develop": "wp-scripts start --no-watch --output-path=assets",
    "i18n": "node make-pot.mjs",
    "composer:install": "composer install --prefer-dist --optimize-autoloader --no-dev",
    "composer:update": "composer update --optimize-autoloader",
    "composer:refresh": "composer dump-autoload --optimize",
    "package:create-zip": "npm run build && npm run i18n && npm run composer:refresh && gulp",
    "package:deploy": "gulp freemius-deploy"
  }
}
```

#### `check-updates`

Utility script to run [npm-check-updates](https://www.npmjs.com/package/npm-check-updates) for any package updates.

#### `build`, `start` and `develop`

These scripts are wrappers around [wp-scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) 
`build` and `start` commands with the output set to the `assets` folder. The `develop` script essentially 
builds the development version of the plugin without starting a file watcher.

**_Note:_** The default `@wordpress/scripts/config/webpack.config` is being extended by the projects 
`webpack.config.js` file.

#### `i18n`

The `make-pot.mjs` file is a wrapper around the [wp-cli](https://developer.wordpress.org/cli/commands/i18n/) `make-pot` 
command. The reason this exists in a separate file is that regardless of the format supplied, the `--headers` option 
for the `make-pot` command does not seem to work. The code in this file simply emulates that option and configures
the headers in the `.pot` file.

#### `composer:*`

The composer commands I never remember...

* `composer-install` - Install packages in `composer.json` and configure the autoloader to generate a classmap for 
the projects PHP files.
* `composer-update` - Update installed packages and refresh the autoloader cache.
* `composer-refresh` - Recreate the autoloader cache ensuring the generated classmap is up-to-date.

#### `package:*`

These scripts are used to bundle the plugin into a `.zip` and deploy it to the Freemius dashboard.

* `package:create-zip` - Run the `build`, `i18n`, `composer:refresh` and `gulp` commands in series to produce 
a new `<name>.v<version>.zip` file in the `dists` folder.
* `package:deploy` - Use the .gitignored `fsconfig.json` file in the projects root directory to deploy the 
current versions `.zip` file to the freemius dashboard.

## `webpack.config.js`

This project extends the default `@wordpress/scripts/config/webpack.config` to perform the following:

1. Add the `editor` and `frontend` entry points to the build.
2. Configure the dependencies for entries discovered by the default configuration to include the `editor` and 
`frontend` scripts based on there include path.
3. Configure the `@wordpress/dependency-extraction-webpack-plugin` to handle the `editor` and `frontend` scripts.
4. Add `raw-loader` to the default `module.rules` and configure it to import `.html` files.
5. Add `copy-webpack-plugin` to the default `plugins` and configure it to copy the `src/media` folder to the `assets` folder.

**_Note:_** Check the comments in the file for more detailed explanations of the above.