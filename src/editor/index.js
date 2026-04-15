export * from "./components";
export * from "./hooks";
export * from "./plugins";
export * from "./utils";
export * from "./icons";

import { getPlugin, registerPlugin } from "@wordpress/plugins";

import "./index.scss";

import * as components from "./components";
import * as hooks from "./hooks";
import * as plugins from "./plugins";
import * as utils from "./utils";
import * as icons from "./icons";
import "./filters";

[
    [ "fc-ai-builder", plugins.AiBuilderPlugin ],
    [ "fc-compatibility", plugins.CompatibilityPlugin ],
    [ "fc-custom-editor", plugins.CustomEditorPlugin ],
    [ "fc-display-rules", plugins.DisplayRulesPlugin ],
    [ "fc-override-template-validity", plugins.OverrideTemplateValidityPlugin ],
].forEach( ( [ name, render ] ) => {
    if ( ! getPlugin( name ) ) {
        registerPlugin( name, { render } );
    }
} );

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert.editor = {
    ...globalThis.FooConvert, // extend any pre-existing global object
    ...components,
    ...hooks,
    ...plugins,
    ...utils,
    ...icons
};
