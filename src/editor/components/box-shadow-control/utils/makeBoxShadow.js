import { isArray, isString } from "@steveush/utils";
import parseBoxShadow from "./parseBoxShadow";
import isStringNotEmpty from "../../../utils/isStringNotEmpty";

const makeBoxShadow = value => {
    if ( isString( value ) ) {
        value = parseBoxShadow( value );
    }
    if ( isArray( value, true ) ) {
        const shadows = value.map( shadow => {
            const {
                inset = false,
                color,
                offsetX,
                offsetY,
                blurRadius,
                spreadRadius
            } = shadow;
            const hasColor = isString( color );
            const hasOffset = isString( offsetX ) && isString( offsetY );
            const hasBlurRadius = hasOffset && isString( blurRadius );
            const hasSpreadRadius = hasBlurRadius && isString( spreadRadius );
            let lengths = '';
            if ( hasSpreadRadius ) {
                lengths = `${ offsetX } ${ offsetY } ${ blurRadius } ${ spreadRadius }`;
            } else if ( hasBlurRadius ) {
                lengths = `${ offsetX } ${ offsetY } ${ blurRadius }`;
            } else if ( hasOffset ) {
                lengths = `${ offsetX } ${ offsetY }`;
            }
            if ( lengths !== '' ) {
                return `${ inset ? 'inset ' : '' }${ lengths }${ hasColor ? ' ' + color : '' }`;
            }
            return undefined;
        } ).filter( isStringNotEmpty );
        if ( shadows.length > 0 ) {
            return shadows.join( ', ' );
        }
    }
    return undefined;
};

export default makeBoxShadow;