import { CountdownElement } from "./element";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-countdown", CountdownElement );
}

if ( !globalThis?.FooConvertPro ) {
    globalThis.FooConvertPro = {};
}

globalThis.FooConvertPro = {
    ...globalThis.FooConvertPro,
    CountdownElement
};