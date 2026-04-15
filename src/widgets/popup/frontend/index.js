import { PopupElement } from "./popup";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-overlay", PopupElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    PopupElement,
    OverlayElement: PopupElement,
};
