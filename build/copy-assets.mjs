import { globby } from "globby";
import { dirname, join } from "path";
import { copyFile, mkdir, rm } from "fs/promises";
import sharp from "sharp";
import { readdir } from "fs/promises";

const toShortTime = timespan => {
    if ( timespan > 1000 ) {
        return ( timespan / 1000 ).toFixed( 3 )
            .replace( /[.,]?0+$/i, "" ) + "s";
    }
    return timespan + "ms";
};

const performCopy = async(source, target, patterns) => {
    const started = Date.now();
    console.log( `copying "${ source }" to "${ target }"...` );
    const found = await globby( patterns, { cwd: source } );
    await Promise.all( found.map( file => {
        const output = join( target, file );
        return mkdir( dirname( output ), { recursive: true } )
            .then( () => copyFile( join( source, file ), output ) );
    } ) );
    console.log( `copied "${ source }" in ${ toShortTime( Date.now() - started ) }` );
};

const performMove = async(source, target, patterns, clean = true) => {
    const started = Date.now();
    console.log( `moving "${ source }" to "${ target }"...` );
    const found = await globby( patterns, { cwd: source } );
    await Promise.all( found.map( file => {
        const input = join( source, file );
        const output = join( target, file );
        return mkdir( dirname( output ), { recursive: true } )
            .then( () => copyFile( input, output ) )
            .then( () => rm( input, { force: true, recursive: true } ) );
    } ) );
    if ( clean ) {
        await rm( source, { force: true, recursive: true } );
    }
    console.log( `moved "${ source }" in ${ toShortTime( Date.now() - started ) }` );
};

const resizeTemplates = async (sourceDir, destDir, width = 150, height = 150) => {
    try {
        const files = await readdir(sourceDir);
        const imageFiles = files.filter(file => /\.(jpe?g|png)$/i.test(file));

        await Promise.all(imageFiles.map(file => {
            const inputPath = `${sourceDir}/${file}`;
            const outputPath = `${destDir}/${file}`;
            return mkdir(dirname(outputPath), { recursive: true })
                .then(() => sharp(inputPath)
                    .resize(width, height, { fit: "cover" })
                    .toFile(outputPath));
        }));

        console.log(`Resized ${imageFiles.length} image(s) from "${sourceDir}" → "${destDir}" (${width}x${height})`);
    } catch (err) {
        if ( err?.code === "ENOENT" ) {
            return;
        }
        throw err;
    }
};

const mediaPatterns = [ '**/*.{png,jpg,jpeg,gif,webp,svg}', '!templates/preview/**' ];
const previewPatterns = [ '**/*.{png,jpg,jpeg,gif,webp,svg}' ];

await resizeTemplates("./src/media/templates/fullsize", "./src/media/templates");

await performCopy( "./src/media", "./assets/media", mediaPatterns );
await performCopy( "./src/media/templates/preview", "./assets/media/templates/preview", previewPatterns );
await performCopy( "./src/admin", "./assets/admin", [ '**/*' ] );

await rm( "./assets/pro", { force: true, recursive: true } );
await rm( "./pro/assets/blocks", { force: true, recursive: true } );
await resizeTemplates("./pro/src/media/templates/fullsize", "./pro/src/media/templates");
await performCopy( "./pro/src/media", "./pro/assets/media", mediaPatterns );
await performCopy( "./pro/src/media/templates/preview", "./pro/assets/media/templates/preview", previewPatterns );
await performCopy( "./pro/src", "./pro/assets", [ '**/block.json' ] );
await performMove( "./assets", "./pro/assets", [ 'editor-pro*.*', 'frontend-pro*.*' ], false );
await performMove( "./assets/admin/ai-popup-builder", "./pro/assets/admin/ai-popup-builder", [ '**/*' ], false );

const proBlockFiles = await globby( 'blocks/**/block.json', { cwd: './pro/src' } );
for ( const file of proBlockFiles ) {
    const directory = dirname( file );
    await performMove( join( './assets', directory ), join( './pro/assets', directory ), [ '**/*' ] );
}
