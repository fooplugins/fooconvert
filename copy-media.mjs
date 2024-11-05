import { globby } from "globby";
import { dirname, join } from "path";
import { copyFile, mkdir } from "fs/promises";

const source = "./src/media";
const target = "./assets/media";
const patterns = [ '**/*' ];

const toShortTime = timespan => {
    if ( timespan > 1000 ) {
        return ( timespan / 1000 ).toFixed( 3 )
            .replace( /[.,]?0+$/i, "" ) + "s";
    }
    return timespan + "ms";
};

const performCopy = async() => {
    const started = Date.now();
    try {
        console.log( `copying "${ source }" to "${ target }"...` );
        const found = await globby( patterns, { cwd: source } );
        await Promise.all( found.map( file => {
            const output = join( target, file );
            return mkdir( dirname( output ), { recursive: true } )
                .then( () => copyFile( join( source, file ), output ) );
        } ) );
        console.log( `copied "${ source }" in ${ toShortTime( Date.now() - started ) }` );
    } catch ( err ) {
        console.error( `copy error: ${ err.message }` );
    }
};

await performCopy();