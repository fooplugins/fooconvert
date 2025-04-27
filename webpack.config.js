const { basename, dirname, extname, join, sep } = require( 'path' );
const { sync: glob } = require( 'fast-glob' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const { fromProjectRoot, hasProjectFile } = require( "@wordpress/scripts/utils/file" );
const { readFileSync } = require( "fs" );
const { getBlockJsonScriptFields, getBlockJsonModuleFields } = require( "@wordpress/scripts/utils/block-json" );

/**
 *
 * @param root
 * @param buildType
 * @returns {{}|{index: string}}
 * @see getWebpackEntryPoints
 */
const getBlockEntries = ( root, buildType = 'script' ) => {

    const srcPath = join( root, 'src' );

    // Continue only if the source directory exists.
    if ( !hasProjectFile( srcPath ) ) {
        console.log(
            `Source directory "${ srcPath }" was not found.`
        );
        return {};
    }

    // 2. Checks whether any block metadata files can be detected in the defined source directory.
    //    It scans all discovered files looking for JavaScript assets and converts them to entry points.
    const blockMetadataFiles = glob( '**/block.json', {
        absolute: true,
        cwd: fromProjectRoot( srcPath ),
    } );

    if ( blockMetadataFiles.length > 0 ) {
        const srcDirectory = fromProjectRoot(
            srcPath + sep
        );

        const entryPoints = {};

        for ( const blockMetadataFile of blockMetadataFiles ) {
            const fileContents = readFileSync( blockMetadataFile );
            let parsedBlockJson;
            // wrapping in try/catch in case the file is malformed
            // this happens especially when new block.json files are added
            // at which point they are completely empty and therefore not valid JSON
            try {
                parsedBlockJson = JSON.parse( fileContents );
            } catch ( error ) {
                console.log(
                    `Skipping "${ blockMetadataFile.replace(
                        fromProjectRoot( sep ),
                        ''
                    ) }" due to malformed JSON.`
                );
            }

            const fields =
                buildType === 'script'
                    ? getBlockJsonScriptFields( parsedBlockJson )
                    : getBlockJsonModuleFields( parsedBlockJson );

            if ( !fields ) {
                continue;
            }

            for ( const value of Object.values( fields ).flat() ) {
                if ( !value.startsWith( 'file:' ) ) {
                    continue;
                }

                // Removes the `file:` prefix.
                const filepath = join(
                    dirname( blockMetadataFile ),
                    value.replace( 'file:', '' )
                );

                // Takes the path without the file extension, and relative to the defined source directory.
                if ( !filepath.startsWith( srcDirectory ) ) {
                    console.log(
                        `Skipping "${ value.replace(
                            'file:',
                            ''
                        ) }" listed in "${ blockMetadataFile.replace(
                            fromProjectRoot( sep ),
                            ''
                        ) }". File is located outside of the "${ srcPath }" directory.`
                    );
                    break;
                }
                const entryName = filepath
                    .replace( extname( filepath ), '' )
                    .replace( srcDirectory, '' )
                    .replace( /\\/g, '/' );

                // Detects the proper file extension used in the defined source directory.
                const [ entryFilepath ] = glob(
                    `${ entryName }.?(m)[jt]s?(x)`,
                    {
                        absolute: true,
                        cwd: fromProjectRoot( srcPath ),
                    }
                );

                if ( !entryFilepath ) {
                    console.log(
                        `Skipping "${ value.replace(
                            'file:',
                            ''
                        ) }" listed in "${ blockMetadataFile.replace(
                            fromProjectRoot( sep ),
                            ''
                        ) }". File does not exist in the "${ srcPath }" directory.`
                    );
                    break;
                }
                entryPoints[ root + '/' + entryName ] = entryFilepath;
            }
        }

        if ( Object.keys( entryPoints ).length > 0 ) {
            return entryPoints;
        }
    }

    // Don't do any further processing if this is a module build.
    // This only respects *module block.json fields.
    if ( buildType === 'module' ) {
        return {};
    }

    // 3. Checks whether a standard file name can be detected in the defined source directory,
    //  and converts the discovered file to entry point.
    const [ entryFile ] = glob( 'index.[jt]s?(x)', {
        absolute: true,
        cwd: fromProjectRoot( srcPath ),
    } );

    if ( !entryFile ) {
        console.log( `No entry file discovered in the "${ srcPath }" directory.` );
        return {};
    }

    return {
        index: entryFile,
    };
};

/**
 * Modify the default WordPress config entry points to include our internal packages '#editor' and '#frontend' that are
 * defined in the `pkg.imports`. This lets us split up the code base into reusable chunks.
 *
 * @type {import('webpack').EntryFunc}
 */
const entry = () => {
    // get the default config entries
    const defaultEntries = defaultConfig.entry();
    const proEntries = getBlockEntries( 'pro' );
    const blockEntries = { ...defaultEntries, ...proEntries };
    // create our custom entry points to match the `pkg.imports` values
    const entries = {
        "editor": "./src/editor/index.js",
        "frontend": "./src/frontend/index.js",
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
    return Object.entries( blockEntries ).reduce( ( acc, [ key, value ] ) => {
        if ( key.startsWith( "pro/" ) && key.includes( "/editor/" ) ) {
            // if the current entry path includes /editor/ then add it as a dependency.
            acc[ key ] = {
                import: value,
                dependOn: [ "editor", "editor-pro" ]
            };
        } else if ( key.startsWith( "pro/" ) && key.includes( "/frontend/" ) ) {
            // if the current entry path includes /frontend/ then add it as a dependency.
            acc[ key ] = {
                import: value,
                dependOn: [ "frontend", "frontend-pro" ]
            };
        } else if ( key.includes( "/frontend/" ) ) {
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
        !process.env.WP_NO_EXTERNALS && new DependencyExtractionWebpackPlugin( dependencyExtractionWebpackPluginOptions )
    ].filter( Boolean )
};