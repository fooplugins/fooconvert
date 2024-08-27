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
import { isBoxUnit, makeBoxUnit, makeBoxUnitTuple } from "./utils";

import "./Component.scss";
import { cleanObject, distinct } from "@steveush/utils";

/**
 * An object containing the labels for the box unit sides.
 *
 * @type {{top: string, right: string, bottom: string, left: string}}
 */
const LABELS = {
    top: __( "Top", "fooconvert" ),
    right: __( "Right", "fooconvert" ),
    bottom: __( "Bottom", "fooconvert" ),
    left: __( "Left", "fooconvert" )
};

/**
 * The base CSS class for the component.
 *
 * @type {string}
 */
const CLASS_NAME = 'fc--box-units-control';

/**
 * The properties for the `BoxUnitsControl` component.
 *
 * @typedef {Omit<UnitsControlProps, 'before'|'after'|'value'|'onChange'>} FCBoxUnitsControlProps
 * @property {string|Partial<FCBoxUnit>|undefined} value - Current value. Can be a `string`, an `object` containing the `top`, `right`, `bottom` and/or `left` values, or `undefined`.
 * @property {(nextValue: string|Partial<FCBoxUnit>|undefined)=>void} onChange - Callback when the `value` changes.
 * @property {boolean} [initialUnlinked] - If `true`, and the `value` is a box unit object, the UI will display the unlinked input controls on load. By default, the control displays the linked UI with the 'Mixed' placeholder.
 */

/**
 * Allows the user to set a box unit value using a single value for all sides or separate values per side.
 *
 * @param {FCBoxUnitsControlProps} props - The {@link FCBoxUnitsControlProps|component properties}.
 * @returns {JSX.Element}
 */
const BoxUnitsControl = ( props ) => {
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

    const [ view, setView ] = useState( initialUnlinked && isBoxUnit( value, true ) ? "unlinked" : "linked" );
    const isUnlinked = view === "unlinked";

    // Get the base control properties from the remaining props ensuring we merge in our CSS classes.
    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className, {
            "is-linked": !isUnlinked,
            "is-unlinked": isUnlinked
        } )
    } );

    // Common properties regardless of the current view.
    const commonProps = { disableUnits, units, min, max, step, size, hideLabelFromVision: true };

    // Render the button that toggles the view between linked & unlinked.
    const renderToggle = () => {
        return (
            <Button
                label={ isUnlinked ? __( 'Link sides', 'fooconvert' ) : __( 'Unlink sides', 'fooconvert' ) }
                showTooltip={ true }
                className={ `${ CLASS_NAME }__link-toggle` }
                size="small"
                icon={ isUnlinked ? linkOff : link }
                onClick={ () => setView( isUnlinked ? "linked" : "unlinked" ) }
            />
        );
    };

    // Render the linked 'All sides' control.
    const renderLinked = () => {
        const values = makeBoxUnitTuple( value );
        const mixed = distinct( values ).length > 1;
        const onLinkedChange = nextValue => onChange( $string( nextValue ) );
        return (
            <UnitsControl
                id={ controlProps.id }
                value={ mixed ? undefined : values?.find( v => isStringNotEmpty( v ) ) }
                onChange={ onLinkedChange }
                className={ `${ CLASS_NAME }__units-control` }
                label={ __( 'All sides', 'fooconvert' ) }
                placeholder={ mixed ? __( 'Mixed', 'fooconvert' ) : placeholder }
                after={ renderToggle }
                { ...commonProps }
            />
        );
    };

    // Render the unlinked individual side controls.
    const renderUnlinked = () => {
        // Always make sure we're working with a box unit object when in the unlinked view
        const previousValue = makeBoxUnit( value ) ?? {
            top: undefined,
            right: undefined,
            bottom: undefined,
            left: undefined
        };
        return (
            <div className={ `${ CLASS_NAME }__inner` }>
                <div className={ `${ CLASS_NAME }__visualizer` }></div>
                { Object.keys( previousValue ).map( ( key, i ) => {
                    const onUnlinkedChange = nextValue => {
                        const newValue = cleanObject( {
                            ...previousValue,
                            [ key ]: $string( nextValue )
                        } );
                        onChange( newValue );
                    };
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
                { renderToggle() }
            </div>
        );
    };

    // noinspection JSValidateTypes - baseControlProps warning despite basic usage as per example -> https://developer.wordpress.org/block-editor/reference-guides/components/base-control/
    return (
        <BaseControl { ...baseControlProps } __nextHasNoMarginBottom={ true }>
            { isUnlinked ? renderUnlinked() : renderLinked() }
        </BaseControl>
    );
};

export default BoxUnitsControl;