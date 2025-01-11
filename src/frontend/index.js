import "./index.scss";

import * as utils from "./utils";
import * as elements from "./elements";
import config from "./config";
import "./hooks";

export * from "./utils";
export * from "./elements";
export { config };

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert = {
    ...globalThis.FooConvert,
    ...utils,
    ...elements,
    config: {
        ...( globalThis.FooConvert?.config ?? {} ),
        ...config
    }
};