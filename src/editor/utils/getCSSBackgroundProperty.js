import isCSSGradient from "./isCSSGradient";

const getCSSBackgroundProperty = value => isCSSGradient( value ) ? 'backgroundImage' : 'backgroundColor';

export default getCSSBackgroundProperty;