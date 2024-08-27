import { BaseControl, Button, useBaseControlProps } from "@wordpress/components";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { settings } from "@wordpress/icons";
import { isString } from "@steveush/utils";
import { $string } from "../../utils";
import { UnitsControl } from "../units-control";
import classnames from "classnames";

import { ToggleGroupControl, ToggleGroupControlOption } from "../experimental";

import "./Component.scss";

const CLASS_NAME = 'fc--size-control';

const SizeControl = ( props ) => {
    const {
        value,
        onChange,
        sizes = [],
        units = [
            { value: 'px', label: 'px', default: 0 },
            { value: 'em', label: 'em', default: 0 },
            { value: 'rem', label: 'rem', default: 0 },
            { value: 'vw', label: 'vw', default: 0 },
            { value: 'vh', label: 'vh', default: 0 },
        ],
        disableUnits = false,
        min = 0,
        max = 100,
        step = 1,
        initialPosition,
        size = "__unstable-large",
        placeholder,
        before,
        after,
        label,
        className,
        ...restProps
    } = props;

    const hasValue = isString( value, true );
    const hasSizes = Array.isArray( sizes ) && sizes.length > 0;
    const selectedSize = hasValue && hasSizes ? sizes.find( size => size.value === value ) : undefined;
    const hasSelectedSize = !!selectedSize;

    const [ view, setView ] = useState( hasSelectedSize || !hasValue ? "default" : "custom" );
    const isDefault = view === "default";

    const makeLabel = () => {
        let current = '';
        if ( hasSelectedSize || hasValue ) {
            current = isDefault && hasSelectedSize ? selectedSize.label : __( 'Custom', 'fooconvert' );
        }
        return (
            <>
                <span className={ `${ CLASS_NAME }__label-text` }>
                    <span>{ label }</span>
                    <small>{ current }</small>
                </span>
                <Button className={ `${ CLASS_NAME }__toggle-custom` }
                        icon={ settings }
                        size="small"
                        label={ isDefault ? __( 'Set custom size', 'fooconvert' ) : __( 'Select a size', 'fooconvert' ) }
                        onClick={ () => setView( isDefault ? "custom" : "default" ) }
                        isPressed={ !isDefault }
                />
            </>
        );
    };

    // Get the base control properties from the remaining props ensuring we merge in our CSS classes.
    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        label: makeLabel(),
        className: classnames( CLASS_NAME, className, {
            "is-default": isDefault,
            "is-custom": !isDefault
        } )
    } );

    const setSize = nextValue => {
        const newValue = $string( nextValue );
        onChange( newValue );
    };

    const renderDefault = () => {
        return (
            <ToggleGroupControl
                className={ `${ CLASS_NAME }__toggle-group-control` }
                label={ __( 'Sizes', 'fooconvert' ) }
                value={ value }
                onChange={ setSize }
                isBlock={ true }
                isDeselectable={ true }
                hideLabelFromVision={ true }
                __nextHasNoMarginBottom={ true }
            >
                { sizes.map( size => (
                    <ToggleGroupControlOption
                        className={ `${ CLASS_NAME }__toggle-group-control-option` }
                        key={ size.value }
                        value={ size.value }
                        label={ size?.abbr ?? size.label }
                        aria-label={ size.label }
                        showTooltip
                    />
                ) ) }
            </ToggleGroupControl>
        );
    };

    const renderCustom = () => {
        return (
            <UnitsControl
                value={ value }
                onChange={ setSize }
                units={ units }
                disableUnits={ disableUnits }
                min={ min }
                max={ max }
                step={ step }
                initialPosition={ initialPosition }
                size={ size }
                placeholder={ placeholder }
                before={ before }
                after={ after }
            />
        );
    };

    // noinspection JSValidateTypes - baseControlProps warning despite basic usage as per example -> https://developer.wordpress.org/block-editor/reference-guides/components/base-control/
    return (
        <BaseControl { ...baseControlProps } __nextHasNoMarginBottom={ true }>
            { isDefault ? renderDefault() : renderCustom() }
        </BaseControl>
    );
};

export default SizeControl;