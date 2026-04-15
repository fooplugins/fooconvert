import { OverlayElement } from "./overlay";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-overlay", OverlayElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    OverlayElement,
};
