import { CouponElement } from "./element";

import "./index.scss";

if ( !!globalThis.customElements ) {
    globalThis.customElements.define( "fc-coupon", CouponElement );
}

if ( !globalThis?.FooConvertPro ) {
    globalThis.FooConvertPro = {};
}

globalThis.FooConvertPro = {
    ...globalThis.FooConvertPro,
    CouponElement
};