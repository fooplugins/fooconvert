export * from "./utils";
export * from "./elements";

import "./index.scss";

import * as utils from "./utils";
import * as elements from "./elements";

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert = {
    ...globalThis.FooConvert,
    ...utils,
    ...elements
};