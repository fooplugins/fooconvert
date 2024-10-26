import { ExampleElement } from "./element";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-example-block", ExampleElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    ExampleElement
};