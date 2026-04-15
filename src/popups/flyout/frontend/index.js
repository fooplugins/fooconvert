import { FlyoutElement } from "./flyout";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-flyout", FlyoutElement );
}

globalThis.FooConvert = {
    ...globalThis.FooConvert,
    FlyoutElement
};