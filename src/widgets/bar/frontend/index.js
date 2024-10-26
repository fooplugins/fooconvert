import { BarElement } from "./bar";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-bar", BarElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    BarElement
};