import isBoxShadow from "./isBoxShadow";

const getBoxShadowStyle = value => isBoxShadow( value ) ? { boxShadow: value } : {};

export default getBoxShadowStyle;