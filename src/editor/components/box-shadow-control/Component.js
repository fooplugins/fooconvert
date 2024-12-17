import { BaseControl, useBaseControlProps } from "@wordpress/components";
import { $string } from "../../utils";
import classnames from "classnames";

import "./Component.scss";
import { BoxShadowPresetsDropdown } from "./components";

const CLASS_NAME = 'fc--box-shadow-control';

const BoxShadowControl = ( props ) => {
    const {
        value,
        onChange,
        label,
        className,
        ...restProps
    } = props;

    // Get the base control properties from the remaining props ensuring we merge in our CSS classes.
    const { baseControlProps, controlProps } = useBaseControlProps( {
        ...restProps,
        label: label,
        className: classnames( CLASS_NAME, className )
    } );

    const setValue = nextValue => {
        const newValue = $string( nextValue );
        onChange( newValue );
    };

    // noinspection JSValidateTypes - baseControlProps warning despite basic usage as per example -> https://developer.wordpress.org/block-editor/reference-guides/components/base-control/
    return (
        <BaseControl { ...baseControlProps } __nextHasNoMarginBottom={ true }>
            <BoxShadowPresetsDropdown
                value={ value }
                onChange={ setValue }
                { ...controlProps }
            />
        </BaseControl>
    );
};

export default BoxShadowControl;