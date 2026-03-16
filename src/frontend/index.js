import "./index.scss";

import * as utils from "./utils";
import * as elements from "./elements";
import * as triggers from "./triggers";
import config from "./config";
import "./hooks";
import "./triggers";

export * from "./utils";
export * from "./elements";
export * from "./triggers";
export { config };

if ( !globalThis?.FooConvert ) {
    globalThis.FooConvert = {};
}
globalThis.FooConvert = {
    ...globalThis.FooConvert,
    ...utils,
    ...elements,
    ...triggers,
    config: {
        ...( globalThis.FooConvert?.config ?? {} ),
        ...config
    }
};
