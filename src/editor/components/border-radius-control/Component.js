// WordPress
import { Button, BaseControl, useBaseControlProps } from "@wordpress/components";
import { link, linkOff } from "@wordpress/icons";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

// noinspection ES6PreferShortImport - If switched to #editor from pkg.imports a circular import is created as we are in #editor
import { UnitControl } from "../experimental";

// External
import classnames from "classnames";

// Internal
import { UnitsControl } from "../units-control";
import { isStringNotEmpty, $string } from "../../utils";
import { isPartialBorderRadiusBox, makeBorderRadiusBox, makeBorderRadiusTuple } from "./utils";

import "./Component.scss";
import { cleanObject, distinct } from "@steveush/utils";

/**
 * An object containing the labels for the border radius corners.
 *
 * @type {{bottomLeft: string, bottomRight: string, topLeft: string, topRight: string}}
 */
const LABELS = {
    topLeft: __( 'Top left', 'fooconvert' ),
    topRight: __( 'Top right', 'fooconvert' ),
    bottomRight: __( 'Bottom right', 'fooconvert' ),
    bottomLeft: __( 'Bottom left', 'fooconvert' )
};

/**
 * The base CSS class for the component.
 *
 * @type {string}
 */
const CLASS_NAME = 'fc--border-radius-control';

/**
 * The properties for the `BorderRadiusControl` component.
 *
 * @typedef {Omit<UnitsControlProps, 'before'|'after'|'value'|'onChange'>} BorderRadiusControlProps
 * @property {BorderRadiusValue|undefined} value - Current value. Can be a `string`, an `object` containing the `topLeft`, `topRight`, `bottomRight` and/or `bottomLeft` values, or `undefined`.
 * @property {(nextValue: BorderRadiusValue|undefined)=>void} onChange - Callback when the `value` changes.
 * @property {boolean} [initialUnlinked] - If `true`, and the `value` is a border radius object, the UI will display the unlinked input controls on load. By default, the control displays the linked UI with the 'Mixed' placeholder.
 */

/**
 * Allows the user to set a border radius using a single value for all corners or separate values per corner.
 *
 * @param {BorderRadiusControlProps} props - The {@link BorderRadiusControlProps|component properties}.
 * @returns {JSX.Element}
 */
const BorderRadiusControl = ( props ) => {
    const {
        value,
        onChange,
        units = [
            { value: 'px', label: 'px', default: 0 },
            { value: '%', label: '%', default: 0 },
            { value: 'em', label: 'em', default: 0 },
        ],
        disableUnits = false,
        initialPosition = 0,
        min = 0,
        max = 100,
        step = 1,
        size = "__unstable-large",
        initialUnlinked = false,
        placeholder,
        className,
        ...restProps
    } = props;

    const [ view, setView ] = useState( initialUnlinked && isPartialBorderRadiusBox( value ) ? "unlinked" : "linked" );
    const isUnlinked = view === "unlinked";

    // Get the base control properties from the remaining props ensuring we merge in our CSS classes.
    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className, {
            "is-linked": !isUnlinked,
            "is-unlinked": isUnlinked
        } ),
        __nextHasNoMarginBottom: true
    } );

    // Common properties regardless of the current view.
    const commonProps = { disableUnits, units, min, max, step, size, hideLabelFromVision: true };

    // Render the button that toggles the view between linked & unlinked.
    const renderToggle = () => {
        return (
            <Button
                label={ isUnlinked ? __( 'Link corners', 'fooconvert' ) : __( 'Unlink corners', 'fooconvert' ) }
                showTooltip={ true }
                className={ `${ CLASS_NAME }__link-toggle` }
                size="small"
                icon={ isUnlinked ? linkOff : link }
                onClick={ () => setView( isUnlinked ? "linked" : "unlinked" ) }
            />
        );
    };

    // Render the linked 'All corners' control.
    const renderLinked = () => {
        const values = makeBorderRadiusTuple( value );
        const mixed = distinct( values ).length > 1;
        const onLinkedChange = nextValue => onChange( $string( nextValue ) );
        return (
            <UnitsControl
                id={ controlProps.id }
                value={ mixed ? undefined : values.find( v => isStringNotEmpty( v ) ) }
                onChange={ onLinkedChange }
                className={ `${ CLASS_NAME }__units-control` }
                label={ __( 'All corners', 'fooconvert' ) }
                placeholder={ mixed ? __( 'Mixed', 'fooconvert' ) : placeholder }
                after={ renderToggle }
                { ...commonProps }
            />
        );
    };

    // Render the unlinked individual corner controls.
    const renderUnlinked = () => {
        // Make sure we're working with a border radius object when in the unlinked view
        const previousValue = makeBorderRadiusBox( value );
        return (
            <div className={ `${ CLASS_NAME }__inner` }>
                <div className={ `${ CLASS_NAME }__inputs-wrapper` }>
                    <div className={ `${ CLASS_NAME }__visualizer` }></div>
                    { Object.keys( previousValue ).map( ( key, i ) => {
                        const onUnlinkedChange = nextValue => onChange( cleanObject( {
                            ...previousValue,
                            [ key ]: $string( nextValue )
                        } ) );
                        return (
                            <UnitControl
                                key={ key }
                                id={ i === 0 ? controlProps.id : `${ controlProps.id }-${ i }` }
                                value={ previousValue[ key ] }
                                onChange={ onUnlinkedChange }
                                className={ `${ CLASS_NAME }__unit-control is-${ key }` }
                                label={ LABELS[ key ] }
                                placeholder={ placeholder }
                                { ...commonProps }
                            />
                        );
                    } ) }
                </div>
                { renderToggle() }
            </div>
        );
    };

    // noinspection JSValidateTypes - baseControlProps warning despite basic usage as per example -> https://developer.wordpress.org/block-editor/reference-guides/components/base-control/
    return (
        <BaseControl { ...baseControlProps }>
            { isUnlinked ? renderUnlinked() : renderLinked() }
        </BaseControl>
    );
};

export default BorderRadiusControl;