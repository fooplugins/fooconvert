import { defineConfig } from "vitest/config";
import { fileURLToPath } from "node:url";

const resolvePath = relativePath => fileURLToPath( new URL( relativePath, import.meta.url ) );

export default defineConfig( {
    resolve: {
        alias: {
            "#editor": resolvePath( "./src/editor/index.js" ),
            "#frontend": resolvePath( "./src/frontend/index.js" ),
            "#editor-pro": resolvePath( "./pro/src/editor/index.js" ),
            "#frontend-pro": resolvePath( "./pro/src/frontend/index.js" )
        }
    },
    test: {
        environment: "jsdom",
        include: [
            "src/**/*.test.js",
            "pro/src/**/*.test.js"
        ],
        restoreMocks: true,
        clearMocks: true
    }
} );
