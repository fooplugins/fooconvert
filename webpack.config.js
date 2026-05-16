const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );
const { RawSource } = require( 'webpack' ).sources;
const { readFileSync } = require( 'fs' );
const { dirname, extname, join, relative, resolve } = require( 'path' );
const { sync: glob } = require( 'fast-glob' );

const PRO_SOURCE_PATH = './pro/src';

const getProBlockEntries = () => {
    const sourceRoot = resolve( process.cwd(), PRO_SOURCE_PATH );
    const blockJsonFiles = glob( '**/block.json', {
        absolute: true,
        cwd: sourceRoot,
    } );

    return blockJsonFiles.reduce( ( entries, blockJsonFile ) => {
        const parsed = JSON.parse( readFileSync( blockJsonFile, 'utf8' ) );

        [ 'editorScript', 'script', 'viewScript' ].forEach( ( fieldName ) => {
            const value = parsed?.[ fieldName ];
            if ( typeof value !== 'string' || ! value.startsWith( 'file:' ) ) {
                return;
            }

            const filepath = join( dirname( blockJsonFile ), value.replace( 'file:', '' ) );
            const relativePath = relative( sourceRoot, filepath ).replaceAll( '\\', '/' );
            const entryName = relativePath.replace( extname( relativePath ), '' );

            if ( entryName.includes( '/frontend/' ) ) {
                entries[ entryName ] = {
                    import: filepath,
                    dependOn: [ 'frontend-pro' ],
                };
                return;
            }

            if ( entryName.includes( '/editor/' ) ) {
                entries[ entryName ] = {
                    import: filepath,
                    dependOn: [ 'editor-pro' ],
                };
                return;
            }

            entries[ entryName ] = filepath;
        } );

        return entries;
    }, {} );
};

class FooConvertEntryAssetDependenciesPlugin {
    static entryToHandle = {
        "editor": "fc-editor",
        "frontend": "fc-frontend",
        "editor-pro": "fc-editor-pro",
        "frontend-pro": "fc-frontend-pro"
    };

    apply( compiler ) {
        compiler.hooks.thisCompilation.tap( this.constructor.name, compilation => {
            compilation.hooks.processAssets.tap(
                {
                    name: this.constructor.name,
                    stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_ANALYSE + 1,
                },
                () => this.patchAssets( compilation )
            );
        } );
    }

    patchAssets( compilation ) {
        for ( const [ entryName, entrypoint ] of compilation.entrypoints.entries() ) {
            const dependOn = entrypoint.options?.dependOn;
            const entryDependencies = Array.isArray( dependOn ) ? dependOn : ( typeof dependOn === "string" ? [ dependOn ] : [] );
            if ( entryDependencies.length === 0 ) {
                continue;
            }

            const handles = entryDependencies
                .map( dependency => FooConvertEntryAssetDependenciesPlugin.entryToHandle[ dependency ] )
                .filter( Boolean );

            if ( handles.length === 0 ) {
                continue;
            }

            const assetFilename = entrypoint.getFiles().find( file => file.endsWith( '.asset.php' ) );
            if ( !assetFilename ) {
                continue;
            }

            const asset = compilation.getAsset( assetFilename );
            if ( !asset ) {
                continue;
            }

            const source = asset.source.source().toString();
            const dependencyMatch = source.match( /'dependencies'\s*=>\s*array\(([^)]*)\)/s );
            if ( !dependencyMatch ) {
                continue;
            }

            const existingDependencies = [ ...dependencyMatch[1].matchAll( /'([^']+)'/g ) ].map( ( [ , value ] ) => value );
            const dependencies = [ ...new Set( [ ...handles, ...existingDependencies ] ) ];
            const replacement = `'dependencies' => array(${ dependencies.map( value => `'${ value }'` ).join( ', ' ) })`;
            const nextSource = source.replace( /'dependencies'\s*=>\s*array\(([^)]*)\)/s, replacement );

            if ( nextSource !== source ) {
                compilation.updateAsset( assetFilename, new RawSource( nextSource ) );
            }
        }
    }
}

/**
 * Modify the default WordPress config entry points to include our internal packages '#editor' and '#frontend' that are
 * defined in the `pkg.imports`. This lets us split up the code base into reusable chunks.
 *
 * @type {import('webpack').EntryFunc}
 */
const entry = () => {
    // get the default config entries
    const blockEntries = defaultConfig.entry();
    const proBlockEntries = getProBlockEntries();
    // create our custom entry points to match the `pkg.imports` values
    const entries = {
        "editor": "./src/editor/index.js",
        "frontend": "./src/frontend/index.js",
        "admin/ai-popup-builder/index": "./src/admin/ai-popup-builder/index.js",
        "admin/brand-context/index": "./src/admin/brand-context/index.js",
        "admin/display-rules-list/index": "./src/scripts/admin/display-rules-list/index.js",
        "editor-pro": {
            "import": "./pro/src/editor/index.js",
            "dependOn": [ "editor" ]
        },
        "frontend-pro": {
            "import": "./pro/src/frontend/index.js",
            "dependOn": [ "frontend" ]
        },
    };
    // iterate the default entries and add them to our new entries object
    const resolvedEntries = Object.entries( blockEntries ).reduce( ( acc, [ key, value ] ) => {
        if ( key.includes( "/frontend/" ) ) {
            acc[ key ] = {
                import: value,
                dependOn: [ "frontend" ]
            };
        } else if ( key.includes( "/editor/" ) ) {
            acc[ key ] = {
                import: value,
                dependOn: [ "editor" ]
            };
        } else {
            acc[ key ] = value;
        }
        return acc;
    }, entries );

    return {
        ...resolvedEntries,
        ...proBlockEntries
    };
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
        // handle requests like `import { XY } from '#frontend-pro';`
        if ( request.startsWith( "#frontend-pro" ) ) {
            // expect to find `#frontend-pro` as `FooConvertPro` in the global scope. See 'pro/src/frontend/index.js' for the definition.
            return [ "FooConvertPro" ];
        }
        // handle requests like `import { XY } from '#editor-pro';`
        if ( request.startsWith( "#editor-pro" ) ) {
            // expect to find `#editor-pro` as `FooConvertPro` in the global scope. See 'pro/src/editor/index.js' for the definition.
            return [ "FooConvertPro", "editor" ];
        }
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
        if ( request.startsWith( "#editor-pro" ) ) {
            return "fc-editor-pro";
        }
        if ( request.startsWith( "#frontend-pro" ) ) {
            return "fc-frontend-pro";
        }
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
        if (
            request.startsWith( "#editor" )
            || request.startsWith( "#frontend" )
            || request.startsWith( "#editor-pro" )
            || request.startsWith( "#frontend-pro" )
        ) {
            throw new Error( `Attempted to use FooConvert script in a module: ${ request }, which is not supported.` );
        }
    }
};

// const cwp = defaultConfig.plugins.find( plugin => plugin.constructor.name === 'CopyPlugin' );
// if ( cwp && cwp.patterns ) {
//     const copiedPatterns = cwp.patterns.slice();
//     copiedPatterns.forEach( pattern => {
//
//     } );
// }

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
        !process.env.WP_NO_EXTERNALS && new DependencyExtractionWebpackPlugin( dependencyExtractionWebpackPluginOptions ),
        new FooConvertEntryAssetDependenciesPlugin()
    ].filter( Boolean )
};
