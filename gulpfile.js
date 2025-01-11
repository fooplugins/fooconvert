const gulp = require( "gulp" );
const gulpZip = import( "gulp-zip" );
const gulpFreemius = require( "gulp-freemius-deploy" );
const rm = require( "node:fs/promises" ).rm;
const pkg = require( "./package.json" );
let freemiusConfig;

try {
    freemiusConfig = require("./fs-config.json");
} catch (err) {
    // Only warn about missing fs-config.json if deploying
    console.warn("Warning: fs-config.json not found. Freemius deploy task will not work.");
}

// Register the freemius-deploy task if fs-config.json exists
if (freemiusConfig) {
    gulpFreemius(gulp, {
        ...freemiusConfig,
        zip_name: `${pkg.name}.v${pkg.version}.zip`,
        zip_path: "./dist/",
        add_contributor: true
    });
}

// clean up the files created by the tasks
function clean() {
    return rm( `./dist/${ pkg.name }.v${ pkg.version }.zip`, { force: true } );
}

const files = [
    "**/*",
    "!{dist,dist/**,dist/**/*}",
    "!{node_modules,node_modules/**,node_modules/**/*}",
    "!{src,src/**,src/**/*}",
    "!{.github,.github/**,.github/**/*}",
    "!.gitignore",
    "!fs-config.json",
    "!gulpfile.js",
    "!make-pot.mjs",
    "!copy-assets.mjs",
    "!package.json",
    "!package-lock.json",
    "!webpack.config.js"
];

// create a .zip containing just the production code for the plugin
function zip() {
    return gulpZip.then( module => {
        // file list is in the package.json
        return gulp.src( files, {encoding: false} )
            .pipe( module?.default(`${ pkg.name }.v${ pkg.version }.zip`) )
            .pipe( gulp.dest( "./dist" ) );
    } );
}

exports.default = gulp.series( clean, zip );