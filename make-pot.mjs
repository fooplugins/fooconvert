import { exec } from "node:child_process";
import { readFile, writeFile } from "node:fs/promises";

const pkg = JSON.parse( await readFile( 'package.json', { encoding: 'utf8' } ) );
const output = `languages/${ pkg.name }.pot`;
const headers = {
    "Report-Msgid-Bugs-To": pkg.bugs,
    "Last-Translator": pkg.author,
    "Language-Team": pkg.author
};

console.info( `Creating '${ output }' file...` );
exec( `wp i18n make-pot . ${ output } --exclude=src`, async ( error, stdout, stderr ) => {
    if ( error ) {
        console.error( 'An error occurred executing the make-pot command.', error );
        process.exitCode = 1;
        return;
    }
    if ( typeof stderr === 'string' && stderr.trim().length > 0 ) {
        console.error( 'An error occurred creating the .pot file.', stderr );
        process.exitCode = 1;
    } else {
        try {
            let contents = await readFile( output, { encoding: 'utf8' } );

            Object.entries( headers ).forEach( ( [ name, value ] ) => {
                const regex = new RegExp( `"${ name }: (.*?)\\\\n"` );
                if ( regex.test( contents ) ) {
                    contents = contents.replace( regex, `"${ name }: ${ value }\\n"` );
                } else {
                    console.warn( `Unable to update the '${ name }' header within '${ output }'. Please check the file manually.` )
                }
            } );

            await writeFile( output, contents, { encoding: 'utf8' } );
            console.info( `Created '${ output }' file.` );
        } catch ( err ) {
            console.error( 'An error occurred updating the .pot file headers.', err );
            process.exitCode = 1;
        }
    }
} );