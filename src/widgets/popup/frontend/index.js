import { PopupElement } from "./popup";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-popup", PopupElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    PopupElement
};