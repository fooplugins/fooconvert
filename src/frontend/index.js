export * from "./utils";
export * from "./elements";
export * from "./events";

import "./index.scss";

import * as utils from "./utils";
import * as elements from "./elements";
import * as events from "./events";

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert = {
    ...globalThis.FooConvert,
    ...utils,
    ...elements,
    ...events
};