const assert = require( "node:assert/strict" );
const { spawnSync } = require( "node:child_process" );
const { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } = require( "node:fs" );
const { join } = require( "node:path" );
const test = require( "node:test" );

const rootDir = join( __dirname, ".." );
const proEntry = join( rootDir, "pro", "start.php" );

assert.equal( existsSync( proEntry ), false, "These regressions must run without the private pro submodule." );

function runNpm( script ) {
    return spawnSync( "npm", [ "run", script ], {
        cwd: rootDir,
        encoding: "utf8",
        env: { ...process.env, NO_COLOR: "1" },
    } );
}

test( "free-only CI mode builds and packages without the private pro submodule", { timeout: 300_000 }, () => {
    const syntheticProDir = join( rootDir, "pro", "includes" );
    const syntheticProFile = join( syntheticProDir, "private-sentinel.php" );
    const privateSentinel = "synthetic-private-pro-string-must-not-leak";

    mkdirSync( syntheticProDir, { recursive: true } );
    writeFileSync( syntheticProFile, `<?php esc_html_e( "${ privateSentinel }", "fooconvert" );\n` );

    let result;
    try {
        result = runNpm( "package:create-zip:free" );
    } finally {
        rmSync( join( rootDir, "pro" ), { force: true, recursive: true } );
    }
    assert.equal( result.status, 0, `${ result.stdout }\n${ result.stderr }` );

    const pkg = JSON.parse( readFileSync( join( rootDir, "package.json" ), "utf8" ) );
    const archivePath = join( rootDir, "dist", `${ pkg.name }.v${ pkg.version }.zip` );
    assert.equal( existsSync( archivePath ), true, `Expected ${ archivePath } to exist.` );

    const listing = spawnSync( "unzip", [ "-Z1", archivePath ], { encoding: "utf8" } );
    assert.equal( listing.status, 0, listing.stderr );
    assert.equal(
        listing.stdout.split( "\n" ).some( filepath => filepath === "pro" || filepath.startsWith( "pro/" ) ),
        false,
        "The free-only archive must not contain pro files."
    );

    const translations = spawnSync( "unzip", [ "-p", archivePath, "languages/fooconvert.pot" ], { encoding: "utf8" } );
    assert.equal( translations.status, 0, translations.stderr );
    assert.doesNotMatch( translations.stdout, new RegExp( privateSentinel ) );
} );

test( "database schema quotes the reserved conversion column for current MariaDB", () => {
    const schema = readFileSync( join( rootDir, "includes", "Data", "Schema.php" ), "utf8" );
    assert.match( schema, /`conversion` tinyint\(1\)/ );
    assert.match( schema, /post_id, `conversion`/ );
} );

test( "default full package fails clearly when the private pro submodule is absent", { timeout: 30_000 }, () => {
    rmSync( join( rootDir, "dist" ), { force: true, recursive: true } );

    const result = runNpm( "package:create-zip" );
    assert.notEqual( result.status, 0, "The default package unexpectedly succeeded without pro." );
    assert.match(
        `${ result.stdout }\n${ result.stderr }`,
        /full build requires the private pro submodule/i
    );
} );
