import { mkdir, readFile, writeFile } from "node:fs/promises";
import { basename, dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const buildDir = dirname( fileURLToPath( import.meta.url ) );
const rootDir = dirname( buildDir );
const pkg = JSON.parse( await readFile( join( rootDir, "package.json" ), { encoding: "utf8" } ) );
const configPath = join( rootDir, "fs-config.json" );
const config = JSON.parse( await readFile( configPath, { encoding: "utf8" } ) );
const productId = config.plugin_id;
const apiToken = process.env.FREEMIUS_API_TOKEN || config.api_token;
const typeArg = process.argv.find( arg => arg.startsWith( "--type=" ) );
const type = typeArg ? typeArg.slice( "--type=".length ) : "all";
const dryRun = process.argv.includes( "--dry-run" );

if ( !Number.isInteger( productId ) ) {
    throw new Error( "fs-config.json must contain an integer plugin_id." );
}

if ( typeof apiToken !== "string" || apiToken.trim() === "" ) {
    throw new Error( "Missing Freemius API token. Set FREEMIUS_API_TOKEN or add api_token to fs-config.json." );
}

if ( ![ "released", "beta", "pending", "all" ].includes( type ) ) {
    throw new Error( `Invalid deployment type '${ type }'. Expected one of: released, beta, pending, all.` );
}

const latestUrl = new URL( `https://api.freemius.com/v1/products/${ productId }/tags/latest.json` );
latestUrl.searchParams.set( "is_premium", "false" );
latestUrl.searchParams.set( "type", type );

const latestResponse = await fetch( latestUrl, {
    headers: {
        "Authorization": `Bearer ${ apiToken }`,
    },
} );

if ( !latestResponse.ok ) {
    const body = await latestResponse.text();
    throw new Error( `Failed to fetch latest free deployment metadata (${ latestResponse.status }): ${ body }` );
}

const latest = await latestResponse.json();
const downloadUrl = latest?.url;
const version = latest?.version;

if ( typeof downloadUrl !== "string" || downloadUrl.trim() === "" ) {
    throw new Error( "Latest deployment response did not include a download URL." );
}

if ( typeof version !== "string" || version.trim() === "" ) {
    throw new Error( "Latest deployment response did not include a version." );
}

const filename = `${ pkg.name }-free.v${ version }.zip`;
const outputPath = join( rootDir, "dist", filename );

if ( dryRun ) {
    console.info( `Latest free deployment: ${ filename }` );
    console.info( `Resolved secure download URL: ${ basename( new URL( downloadUrl ).pathname ) || "latest.zip" }` );
    console.info( `Would save to '${ outputPath }'.` );
    process.exit( 0 );
}

await mkdir( join( rootDir, "dist" ), { recursive: true } );

const downloadResponse = await fetch( downloadUrl );

if ( !downloadResponse.ok ) {
    const body = await downloadResponse.text();
    throw new Error( `Failed to download latest free deployment (${ downloadResponse.status }): ${ body }` );
}

const buffer = Buffer.from( await downloadResponse.arrayBuffer() );
await writeFile( outputPath, buffer );

console.info( `Downloaded latest free deployment to '${ outputPath }'.` );
