import { isNonNullable, isString, strim } from "@steveush/utils";

const isCSSLength = value => /^(0|[+-]?[0-9]*\.?[0-9]+(px|em|rem|vw|vh|vmin|vmax))$/.test( value );

const SHADOWS_SPLIT_REGEX = /(?<!\([^)]*?),(?![^(]*?\))/;
/**
 * Splits a box-shadow value into chunks using white-space which is not within brackets.
 * @type {RegExp}
 */
const SHADOW_SPLIT_REGEX = /(?<!\([^)]*?)\s+(?![^(]*?\))/;

const parseBoxShadow = value => {
    if ( isString( value, true ) ) {
        const shadows = strim( value, SHADOWS_SPLIT_REGEX );
        return shadows.map( shadow => {
            const parts = strim( shadow, SHADOW_SPLIT_REGEX );
            if ( parts.length > 0 ) {
                let inset = false;
                let color = undefined;
                const lengths = [];
                for ( let i = 0; i < parts.length; i++ ) {
                    const part = parts[ i ];
                    if ( part === "inset" ) {
                        inset = true;
                    } else if ( isCSSLength( part ) ) {
                        lengths.push( part );
                    } else {
                        color = part;
                    }
                }
                if ( lengths.length > 1 && lengths.length <= 4 ) {
                    const [ offsetX, offsetY, blurRadius, spreadRadius ] = lengths;
                    return {
                        inset,
                        color,
                        offsetX,
                        offsetY,
                        blurRadius,
                        spreadRadius
                    };
                }
                return undefined;
            }
        }).filter( isNonNullable );
    }
    return [];
};

export default parseBoxShadow;