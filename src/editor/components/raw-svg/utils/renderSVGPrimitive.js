import {
    Circle,
    G,
    Line,
    Path,
    Polygon,
    Rect,
    Defs,
    RadialGradient,
    LinearGradient,
    Stop,
    SVG
} from "@wordpress/primitives";
import { strim, toCamelCase } from "@steveush/utils";

const special = {
    'class': 'className',
    'tabindex': 'tabIndex'
};

/**
 *
 * @param {string} name
 * @returns {string}
 */
const camelize = name => {
    if ( Object.hasOwn( special, name ) ) {
        name = special[ name ];
    } else if ( !( name.startsWith( 'aria-' ) || name.startsWith( 'data-' ) || name.startsWith( '--' ) ) ) {
        name = toCamelCase( name );
    }
    return name;
};

/**
 *
 * @param {string} attrValue
 * @returns {{[key: string]: string;}}
 */
const styleProps = ( attrValue ) => {
    const parts = strim( attrValue, ';' );
    const entries = parts.map( part => {
        const [ name, value= '' ] = strim( part, /:(.*)/ );
        return [ camelize( name ), value ];
    } );
    return Object.fromEntries( entries );
};

/**
 * Render the supplied SVG element using its JSX counterpart.
 *
 * @param {Element} element
 * @param {string|number|bigint|undefined} [key]
 * @return {JSX.Element}
 */
const renderSVGPrimitive = ( element, key ) => {
    if ( element instanceof Element ) {
        const props = {};
        for ( const attr of element.attributes ) {
            if ( attr.name === 'style' ) {
                props[ attr.name ] = styleProps( attr.value );
            } else {
                props[ camelize( attr.name ) ] = attr.value;
            }
        }
        const children = () => Array.from( element.children ).map( ( child, i ) => renderSVGPrimitive( child, i ) );
        switch ( element.tagName.toLowerCase() ) {
            case "circle":
                // noinspection JSValidateTypes
                return ( <Circle key={ key } { ...props }>{ children() }</Circle> );
            case "g":
                // noinspection JSValidateTypes
                return ( <G key={ key } { ...props }>{ children() }</G> );
            case "line":
                // noinspection JSValidateTypes
                return ( <Line key={ key } { ...props }>{ children() }</Line> );
            case "path":
                // noinspection JSValidateTypes
                return ( <Path key={ key } { ...props }>{ children() }</Path> );
            case "polygon":
                // noinspection JSValidateTypes
                return ( <Polygon key={ key } { ...props }>{ children() }</Polygon> );
            case "rect":
                // noinspection JSValidateTypes
                return ( <Rect key={ key } { ...props }>{ children() }</Rect> );
            case "defs":
                // noinspection JSValidateTypes
                return ( <Defs key={ key } { ...props }>{ children() }</Defs> );
            case "radialGradient":
                // noinspection JSValidateTypes
                return ( <RadialGradient key={ key } { ...props }>{ children() }</RadialGradient> );
            case "linearGradient":
                // noinspection JSValidateTypes
                return ( <LinearGradient key={ key } { ...props }>{ children() }</LinearGradient> );
            case "stop":
                // noinspection JSValidateTypes
                return ( <Stop key={ key } { ...props }>{ children() }</Stop> );
            case "svg":
                return ( <SVG key={ key } { ...props }>{ children() }</SVG> );
        }
    }
    console.info( 'renderSVGPrimitive: unsupported element', element );
    return null;
};

export default renderSVGPrimitive;