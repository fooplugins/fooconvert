import { ToggleGroupControl, ToggleGroupControlOption, ToggleGroupControlOptionIcon } from "../experimental";
import classnames from "classnames";

import "./Component.scss";

const CLASS_NAME = 'fc--toggle-select-control';

const ToggleSelectControl = props => {

    const {
        value,
        onChange,
        options = [],
        className,
        iconOnly = false,
        __next40pxDefaultSize,
        ...restProps
    } = props;

    const hasIcons = options.every( option => !!option?.icon );

    const toggleProps = {
        ...restProps,
        className: classnames( CLASS_NAME, className ),
        isBlock: !iconOnly,
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: typeof __next40pxDefaultSize === 'boolean' ? __next40pxDefaultSize : !iconOnly
    };

    // noinspection JSValidateTypes
    return (
        <ToggleGroupControl
            { ...toggleProps }
            value={ value }
            onChange={ onChange }
        >
            { options.map( option => hasIcons && iconOnly ? (<ToggleGroupControlOptionIcon key={ option.value } { ...option }/>) : (<ToggleGroupControlOption key={ option.value } { ...option }/>) ) }
        </ToggleGroupControl>
    );
};

export default ToggleSelectControl;