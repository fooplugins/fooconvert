const gulp = require( "gulp" );
const gulpZip = import( "gulp-zip" );
const gulpFreemius = require( "gulp-freemius-deploy" );
const del = require( "del" );
const pkg = require( "./package.json" );
const freemiusConfig = require( "./fs-config.json" );

// register the freemius-deploy task
gulpFreemius( gulp, {
    ...freemiusConfig,
    zip_name: `${ pkg.name }.v${ pkg.version }.zip`,
    zip_path: "./dist/",
    add_contributor: true
} );

// clean up the files created by the tasks
function clean() {
    return del( [ `./dist/${ pkg.name }.v${ pkg.version }.zip` ] );
}

// create a .zip containing just the production code for the plugin
function zip() {
    return gulpZip.then( module => {
        return gulp.src( pkg.files )
            .pipe( module?.default(`${ pkg.name }.v${ pkg.version }.zip`) )
            .pipe( gulp.dest( "./dist" ) );
    } );
}

exports.default = gulp.series( clean, zip );