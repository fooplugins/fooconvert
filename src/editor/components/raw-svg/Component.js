import { parseSVG, renderSVGPrimitive } from "./utils";

const RawSVG = ( { value = '' } ) => {
    const svg = parseSVG( value );
    if ( svg instanceof Element ) {
        return renderSVGPrimitive( svg );
    }
    return null;
};

export default RawSVG;