import "./Component.scss";
import classnames from "classnames";
import { isNumber } from "@steveush/utils";

/**
 * The properties for the {@link TextContentControl} component.
 *
 * @typedef {ComponentProps<"p">} TextContentControlProps
 * @property {string} value - The text to display.
 * @property {number} [lineClamp] - Optional. A number greater than zero representing the maximum number of lines to display. Defaults to `-1`.
 */

/**
 * Renders the value as a paragraph that matches the layout of a `TextControl` or `TextareaControl` based on the
 * `lineClamp` value.
 *
 * Useful for displaying a placeholder block of text that can be switched out for a text type input without
 * disturbing the overall layout of the parent component.
 *
 * @param {TextContentControlProps} properties - The {@link TextContentControlProps|properties} supplied to the component.
 * @returns {JSX.Element} The rendered component.
 */
const TextContentControl = ( {
                                 value,
                                 lineClamp = -1,
                                 ...props
                             } ) => {

    // get the variables we work with internally
    const {
        className,
        style: inline,
        ...extraProps
    } = props;

    const isLineClamped = isNumber( lineClamp ) && lineClamp >= 1;

    // add the css variable for the max lines as an inline style if required
    let style = inline;
    if ( isLineClamped ) {
        style = style ?? {};
        style[ '--line-clamp' ] = lineClamp;
    }

    // create the props for the component
    const ownProps = {
        ...extraProps,
        className: classnames( 'fc--text-content-control', className, { [ 'is-line-clamped' ]: isLineClamped } ),
        style
    };

    return ( <p { ...ownProps }>{ value }</p> );
};

export default TextContentControl;