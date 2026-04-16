import { spawn } from "node:child_process";
import { mkdir, readFile, rm } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import { globby } from "globby";

const buildDir = dirname( fileURLToPath( import.meta.url ) );
const rootDir = dirname( buildDir );
const pkg = JSON.parse( await readFile( join( rootDir, "package.json" ), { encoding: "utf8" } ) );
const archiveName = `${ pkg.name }.v${ pkg.version }.zip`;
const archivePath = join( rootDir, "dist", archiveName );

const files = ( await globby( "**/*", {
    cwd: rootDir,
    dot: true,
    onlyFiles: true,
    ignore: [
        "build/**",
        "dist/**",
        "node_modules/**",
        "src/**",
        ".github/**",
        ".chatgpt/**",
        ".gitignore",
        ".gitmodules",
        "fs-config.json",
        "package.json",
        "package-lock.json",
        "webpack.config.js"
    ]
} ) ).sort();

if ( files.length === 0 ) {
    throw new Error( "No files matched the distribution archive include rules." );
}

await mkdir( join( rootDir, "dist" ), { recursive: true } );
await rm( archivePath, { force: true } );

console.info( `Creating '${ archivePath }'...` );

const child = spawn( "zip", [ "-q", join( "dist", archiveName ), "-@" ], {
    cwd: rootDir,
    stdio: [ "pipe", "inherit", "inherit" ]
} );

child.stdin.write( `${ files.join( "\n" ) }\n` );
child.stdin.end();

const exitCode = await new Promise( ( resolve, reject ) => {
    child.on( "error", reject );
    child.on( "close", resolve );
} );

if ( exitCode !== 0 ) {
    process.exitCode = exitCode ?? 1;
    throw new Error( `zip exited with code ${ process.exitCode }.` );
}

console.info( `Created '${ archivePath }'.` );
