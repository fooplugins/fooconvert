
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

/**
 * Modify the default WordPress config entry points to include our internal packages '#editor' and '#frontend' that are
 * defined in the `pkg.imports`. This lets us split up the code base into reusable chunks.
 *
 * @type {import('webpack').EntryFunc}
 */
const entry = () => {
    // get the default config entries
    const defaultEntries = defaultConfig.entry();
    // create our custom entry points to match the `pkg.imports` values
    const entries = {
        "editor": "./src/editor/index.js",
        "frontend": "./src/frontend/index.js"
    };
    // iterate the default entries and add them to our new entries object
    return Object.entries( defaultEntries ).reduce( ( acc, [ key, value ] ) => {
        if ( key.includes( "/frontend/" ) ) {
            // if the current entry path includes /frontend/ then add it as a dependency.
            acc[ key ] = {
                import: value,
                dependOn: [ "frontend" ]
            };
        } else if ( key.includes( "/editor/" ) ) {
            // if the current entry path includes /editor/ then add it as a dependency.
            acc[ key ] = {
                import: value,
                dependOn: [ "editor" ]
            };
        } else {
            // otherwise leave it as is
            acc[ key ] = value;
        }
        return acc;
    }, entries );
};

/**
 * The extended options for the dependency extraction plugin.
 *
 * This allows our internal packages to be resolved correctly and have their handles automatically included in any generated assets.php files as a dependency.
 *
 * @type {{requestToExternal(string): (string|string[]|undefined), requestToHandle(string): (string|undefined), requestToExternalModule(string): (string|boolean|undefined)}}
 * @see https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin
 */
const dependencyExtractionWebpackPluginOptions = {
    /**
     * Hook into requests and resolve our internal packages to their expected global variables.
     *
     * @param {string} request - The requested module.
     * @returns {string | string[] | undefined}
     */
    requestToExternal( request ) {
        // handle requests like `import { CustomElement } from '#frontend';`
        if ( request.startsWith( "#frontend" ) ) {
            // expect to find `#frontend` as `FooConvert` in the global scope. See 'src/frontend/index.js' for the definition.
            return [ "FooConvert" ];
        }
        // handle requests like `import { UnitsControl } from '#editor';`
        if ( request.startsWith( "#editor" ) ) {
            // expect to find `#editor` as `FooConvert.editor` in the global scope. See 'src/editor/index.js' for the definition.
            return [ "FooConvert", "editor" ];
        }
    },
    /**
     * Return our internal package handles when requested. This allows our internal package handles to appear in the generated assets.php file as a dependency.
     * @param {string} request - The requested module.
     * @returns {string | undefined}
     */
    requestToHandle( request ) {
        if ( request.startsWith( "#editor" ) ) {
            return "fc-editor";
        }
        if ( request.startsWith( "#frontend" ) ) {
            return "fc-frontend";
        }
    },
    /**
     * Throw an error if one of our internal packages is being used incorrectly.
     *
     * @param {string} request - The requested module.
     * @returns {string | boolean | undefined}
     */
    requestToExternalModule( request ) {
        if ( request.startsWith( "#editor" ) || request.startsWith( "#frontend" ) ) {
            throw new Error( `Attempted to use FooConvert script in a module: ${ request }, which is not supported.` );
        }
    }
};

// merge and export the customized config
module.exports = {
    ...defaultConfig,
    // add in the raw-loader, this allows us to import *.html files as strings when building custom elements.
    module: {
        ...defaultConfig.module,
        rules: defaultConfig.module.rules.concat( [ {
            test: /\.html/i,
            use: 'raw-loader'
        } ] )
    },
    // replace the original entry function with our custom one.
    entry,
    // replace the default DependencyExtractionWebpackPlugin instance with our own and also include the
    // CopyPlugin to allow simple copying of files within the media folder.
    plugins: [
        ...defaultConfig.plugins.filter( plugin => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin' ),
        !process.env.WP_NO_EXTERNALS && new DependencyExtractionWebpackPlugin( dependencyExtractionWebpackPluginOptions )
    ].filter( Boolean )
};