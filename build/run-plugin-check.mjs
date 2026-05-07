import { spawn } from "node:child_process";
import { access } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const buildDir = dirname( fileURLToPath( import.meta.url ) );
const rootDir = dirname( buildDir );
const wpPath = await findWordPressRoot( rootDir );
const passthroughArgs = process.argv.slice( 2 );

const excludedDirectories = [
    ".git",
    "tests",
    "test",
    "dist",
    "bin",
    "src",
];

const excludedFiles = [
    ".gitignore",
    ".gitmodules",
    "pro/.git",
    "pro/.gitignore",
];

const ignoredSqlCodes = [
    "PluginCheck.Security.DirectDB.UnescapedDBParameter",
    "WordPress.DB.DirectDatabaseQuery.DirectQuery",
    "WordPress.DB.DirectDatabaseQuery.NoCaching",
    "WordPress.DB.DirectDatabaseQuery.SchemaChange",
    "WordPress.DB.PreparedSQL.InterpolatedNotPrepared",
    "WordPress.DB.PreparedSQL.NotPrepared",
    "WordPress.DB.SlowDBQuery.slow_db_query_meta_key",
    "WordPress.DB.SlowDBQuery.slow_db_query_meta_query",
    "WordPress.DB.SlowDBQuery.slow_db_query_meta_value",
];

const pluginCheckStatus = await run(
    "wp",
    [
        `--path=${ wpPath }`,
        "plugin",
        "status",
        "plugin-check",
        "--skip-plugins",
        "--skip-themes",
    ],
    { capture: true, allowFailure: true }
);

if ( pluginCheckStatus.code !== 0 ) {
    throw new Error( "The plugin-check plugin must be installed locally before running Plugin Check." );
}

const pluginCheckWasActive = /Status:\s+Active/.test( pluginCheckStatus.stdout );

if ( !pluginCheckWasActive ) {
    await run(
        "wp",
        [
            `--path=${ wpPath }`,
            "plugin",
            "activate",
            "plugin-check",
            "--skip-plugins",
            "--skip-themes",
        ],
        { capture: true }
    );
}

let exitCode = 0;

try {
    const checkArgs = [
        `--path=${ wpPath }`,
        "plugin",
        "check",
        "fooconvert",
        "--mode=new",
        "--format=json",
        "--skip-themes",
        `--exclude-directories=${ excludedDirectories.join( "," ) }`,
        `--exclude-files=${ excludedFiles.join( "," ) }`,
        `--ignore-codes=${ ignoredSqlCodes.join( "," ) }`,
        ...passthroughArgs,
    ];

    exitCode = ( await run( "wp", checkArgs, { allowFailure: true } ) ).code;
} finally {
    if ( !pluginCheckWasActive ) {
        await run(
            "wp",
            [
                `--path=${ wpPath }`,
                "plugin",
                "deactivate",
                "plugin-check",
                "--skip-plugins",
                "--skip-themes",
            ],
            { capture: true, allowFailure: true }
        );
    }
}

process.exitCode = exitCode;

async function findWordPressRoot( startDir ) {
    let current = startDir;

    while ( current !== dirname( current ) ) {
        try {
            await access( join( current, "wp-load.php" ) );
            return current;
        } catch {
            current = dirname( current );
        }
    }

    throw new Error( `Unable to find WordPress root above ${ startDir }.` );
}

function run( command, args, options = {} ) {
    const { allowFailure = false, capture = false } = options;

    return new Promise( ( resolve, reject ) => {
        const child = spawn(
            command,
            args,
            {
                cwd: rootDir,
                stdio: capture ? [ "ignore", "pipe", "pipe" ] : "inherit",
            }
        );

        let stdout = "";
        let stderr = "";

        if ( capture ) {
            child.stdout.on( "data", chunk => {
                stdout += chunk.toString();
            } );
            child.stderr.on( "data", chunk => {
                stderr += chunk.toString();
            } );
        }

        child.on( "error", reject );
        child.on( "close", code => {
            const result = {
                code: code ?? 1,
                stdout,
                stderr,
            };

            if ( result.code !== 0 && !allowFailure ) {
                reject( new Error( `${ command } ${ args.join( " " ) } exited with code ${ result.code }.` ) );
                return;
            }

            resolve( result );
        } );
    } );
}
