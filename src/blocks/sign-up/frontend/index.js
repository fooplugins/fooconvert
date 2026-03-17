import { SignUpElement } from "./element";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-sign-up", SignUpElement );
}

globalThis.FooConvertPro = {
    ...globalThis.FooConvertPro,
    SignUpElement
};