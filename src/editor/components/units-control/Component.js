// WordPress
import { RangeControl, BaseControl, useBaseControlProps } from "@wordpress/components";

// noinspection ES6PreferShortImport - If switched to #editor from pkg.imports a circular import is created as we are in #editor
import { UnitControl, parseQuantityAndUnitFromRawValue } from "../experimental";

// External
import classnames from "classnames";

import "./Component.scss";
import { clamp } from "@steveush/utils";

/**
 * The base CSS class for the component.
 *
 * @type {HTMLInputTypeAttribute}
 */
const CLASS_NAME = 'fc--units-control';

/**
 * A unit for the `UnitsControl`. This simply extends the default `WPUnitControlUnit` with optional `min` and `max` number properties.
 * @typedef {import('@wordpress/components/build-types/unit-control/types').WPUnitControlUnit} UnitsControlUnit
 * @property {number} [min] - The minimum value allowed for this unit. If not supplied, the `min` value from the current `UnitsControl` will be used.
 * @property {number} [max] - The maximum value allowed for this unit. If not supplied, the `max` value from the current `UnitsControl` will be used.
 */

/**
 * The properties for the `UnitsControl` component.
 *
 * @typedef {Omit<import('@wordpress/components/build-types/base-control/types').BaseControlProps, "children">} UnitsControlProps
 * @property {(string|undefined)} value - Current value. If passed as a string, the current unit will be inferred from this value. For example, a `value` of `50%` will set the current unit to `%`.
 * @property {(nextValue: (string|undefined))=>void} onChange - Callback when the `value` changes.
 * @property {boolean} [disableUnits] - If `true`, the unit `<select>` is hidden.
 * @property {UnitsControlUnit[]} [units] - Collection of available units.
 * @property {number} [initialPosition] - The slider starting position, used when no `value` is passed. The `initialPosition` will be clamped between the provided `min` and `max` prop values.
 * @property {number} [min=0] - The minimum value allowed.
 * @property {number} [max=100] - The maximum value allowed.
 * @property {number|"any"} [step=1] - The minimum amount by which `value` changes. It is also a factor in validation as `value` must be a multiple of `step` (offset by `min`) to be valid. Accepts the special string value `"any"` that voids the validation constraint.
 * @property {(string|undefined)} [placeholder] - The placeholder to display when no `value` is passed.
 * @property {("default"|"small"|"compact"|"__unstable-large")} [size="__unstable-large"] - Adjusts the size of the input. Sizes include: `default`, `small` and the undocumented `__unstable-large`.
 * @property {()=>import('react').ReactNode} [before] - If this property is added, the callback allows for custom content to be rendered before the inputs.
 * @property {()=>import('react').ReactNode} [after] - If this property is added, the callback allows for custom content to be rendered after the inputs.
 */

/**
 * Allows the user to set a numeric quantity as well as a unit (e.g. `px`) using either an input or range slider.
 *
 * @param {UnitsControlProps} props - The {@link UnitsControlProps|component properties}.
 * @returns {JSX.Element}
 */
const UnitsControl = ( props ) => {
    const {
        value,
        onChange,
        units = [
            { value: 'px', label: 'px', default: 0, step: 1 },
            { value: '%', label: '%', default: 0, step: 1 },
            { value: 'em', label: 'em', default: 0, step: 0.05 },
            { value: 'rem', label: 'rem', default: 0, step: 0.05 },
            { value: 'vw', label: 'vw', default: 0, step: 1 },
            { value: 'vh', label: 'vh', default: 0, step: 1 },
        ],
        disableUnits = false,
        min = 0,
        max = 100,
        step = 1,
        initialPosition: givenPosition,
        size = "__unstable-large",
        placeholder,
        before,
        after,
        unitPrefix,
        unitSuffix,
        className,
        ...restProps
    } = props;

    const initialPosition = clamp( givenPosition, min, max );

    const hasBefore = typeof before === "function";
    const hasAfter = typeof after === "function";

    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        className: classnames( CLASS_NAME, className, {
            "has-before": hasBefore,
            "has-after": hasAfter
        } )
    } );

    const [ quantity, parsedUnit = "px" ] = parseQuantityAndUnitFromRawValue( value, units );

    const unit = units.find( u => u.value === parsedUnit );


    const renderControls = () => {
        return (
            <div className={ `${ CLASS_NAME }__inner` }>
                { hasBefore && before() }
                <div className={ `${ CLASS_NAME }__inputs-wrapper` }>
                    <UnitControl
                        isOnly
                        id={ controlProps.id }
                        className={ `${ CLASS_NAME }__unit-control` }
                        isResetValueOnUnitChange={ true }
                        prefix={ unitPrefix }
                        suffix={ unitSuffix }
                        value={ value }
                        onChange={ value => onChange( value ) }
                        units={ units }
                        disableUnits={ disableUnits }
                        size={ size }
                        min={ unit?.min ?? min }
                        max={ unit?.max ?? max }
                        step={ unit?.step ?? step }
                        placeholder={ placeholder }
                        __nextHasNoMarginBottom={ true }
                    />
                    <RangeControl
                        className={ `${ CLASS_NAME }__range-control` }
                        hideLabelFromVision={ true }
                        value={ quantity }
                        onChange={ value => onChange( `${ value }${ parsedUnit }` ) }
                        withInputField={ false }
                        showTooltip={ false }
                        initialPosition={ initialPosition }
                        min={ unit?.min ?? min }
                        max={ unit?.max ?? max }
                        step={ unit?.step ?? step }
                        __nextHasNoMarginBottom={ true }
                    />
                </div>
                { hasAfter && after() }
            </div>
        );
    };

    // noinspection JSValidateTypes - baseControlProps warning despite basic usage as per example -> https://developer.wordpress.org/block-editor/reference-guides/components/base-control/
    return (
        <BaseControl { ...baseControlProps }>
            { renderControls() }
        </BaseControl>
    );
};

export default UnitsControl;