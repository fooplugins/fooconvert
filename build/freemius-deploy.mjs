import { createHmac } from "node:crypto";
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

if ( !Number.isInteger( config.plugin_id ) || !Number.isInteger( config.developer_id ) ) {
    throw new Error( "fs-config.json must contain integer developer_id and plugin_id values." );
}

const resourceUrl = `/v1/developers/${ config.developer_id }/plugins/${ config.plugin_id }/tags.json`;
const boundary = `----${ Date.now().toString( 16 ) }`;
const contentMd5 = "";
const date = new Date().toUTCString();
const stringToSign = [
    "POST",
    contentMd5,
    `multipart/form-data; boundary=${ boundary }`,
    date,
    resourceUrl
].join( "\n" );
const signature = createHmac( "sha256", config.secret_key )
    .update( stringToSign )
    .digest( "hex" );
const authorization = `FS ${ config.developer_id }:${ config.public_key }:${ Buffer.from( signature, "utf8" ).toString( "base64" ).replace( /=/g, "" ) }`;

if ( dryRun ) {
    console.info( `Validated Freemius deploy configuration for '${ zipName }'.` );
    console.info( `Ready to POST to https://api.freemius.com${ resourceUrl }` );
    process.exit( 0 );
}

const zipBuffer = await readFile( zipPath );
const body = Buffer.concat( [
    Buffer.from(
        `--${ boundary }\r\n`
        + `Content-Disposition: form-data; name="add_contributor"\r\n\r\n`
        + `true\r\n`
        + `--${ boundary }\r\n`
        + `Content-Disposition: form-data; name="file"; filename="${ zipName }"\r\n`
        + "Content-Type: application/zip\r\n\r\n",
        "utf8"
    ),
    zipBuffer,
    Buffer.from( `\r\n--${ boundary }--\r\n`, "utf8" )
] );

const response = await fetch( `https://api.freemius.com${ resourceUrl }`, {
    method: "POST",
    headers: {
        "Authorization": authorization,
        "Content-MD5": contentMd5,
        "Content-Type": `multipart/form-data; boundary=${ boundary }`,
        "Date": date
    },
    body
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
