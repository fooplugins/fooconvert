import { access, readFile } from "node:fs/promises";
import { constants } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const buildDir = dirname( fileURLToPath( import.meta.url ) );
const rootDir = dirname( buildDir );
const pkg = JSON.parse( await readFile( join( rootDir, "package.json" ), { encoding: "utf8" } ) );
const config = JSON.parse( await readFile( join( rootDir, "fs-config.json" ), { encoding: "utf8" } ) );
const zipName = `${ pkg.name }.v${ pkg.version }.zip`;
const zipPath = join( rootDir, "dist", zipName );
const dryRun = process.argv.includes( "--dry-run" );

await access( zipPath, constants.R_OK );

if ( !Number.isInteger( config.plugin_id ) ) {
    throw new Error( "fs-config.json must contain an integer plugin_id value." );
}

const apiToken = process.env.FREEMIUS_API_TOKEN || config.api_token;

if ( typeof apiToken !== "string" || apiToken.trim() === "" ) {
    throw new Error( "Missing Freemius API token. Set FREEMIUS_API_TOKEN or add api_token to fs-config.json." );
}

const resourceUrl = `/v1/products/${ config.plugin_id }/tags.json`;

if ( dryRun ) {
    console.info( `Validated Freemius bearer deploy configuration for '${ zipName }'.` );
    console.info( `Ready to POST to https://api.freemius.com${ resourceUrl }` );
    process.exit( 0 );
}

const zipBuffer = await readFile( zipPath );
const form = new FormData();
form.set( "file", new Blob( [ zipBuffer ], { type: "application/zip" } ), zipName );

const response = await fetch( `https://api.freemius.com${ resourceUrl }`, {
    method: "POST",
    headers: {
        "Authorization": `Bearer ${ apiToken }`,
    },
    body: form
} );

const responseText = await response.text();
let payload;

try {
    payload = JSON.parse( responseText );
} catch {
    payload = responseText;
}

if ( !response.ok ) {
    throw new Error( typeof payload === "object" && payload?.error?.message
        ? payload.error.message
        : `Freemius deploy failed with HTTP ${ response.status }.` );
}

if ( typeof payload === "object" && payload?.error?.message ) {
    throw new Error( payload.error.message );
}

const version = typeof payload === "object" && payload?.version ? payload.version : pkg.version;
console.info( `Successfully deployed v${ version } to Freemius.` );
console.info( `Release it at https://dashboard.freemius.com/#!/live/plugins/${ config.plugin_id }/deployment/` );
