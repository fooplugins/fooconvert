import parseBoxShadow from "./parseBoxShadow";

const isBoxShadow = value => parseBoxShadow( value ).length > 0;

export default isBoxShadow;