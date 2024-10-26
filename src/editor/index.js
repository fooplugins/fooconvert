export * from "./components";
export * from "./hooks";
export * from "./plugins";
export * from "./utils";

import * as components from "./components";
import * as hooks from "./hooks";
import * as plugins from "./plugins";
import * as utils from "./utils";
import "./filters";

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert.editor = {
    ...globalThis.FooConvert, // extend any pre-existing global object
    ...components,
    ...hooks,
    ...plugins,
    ...utils
};