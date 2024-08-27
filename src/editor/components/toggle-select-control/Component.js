import { ToggleGroupControl, ToggleGroupControlOption } from "../experimental";
import classnames from "classnames";

import "./Component.scss";

const CLASS_NAME = 'fc--toggle-select-control';

const ToggleSelectControl = props => {

    const {
        value,
        onChange,
        options,
        className,
        ...restProps
    } = props;

    const toggleProps = {
        ...restProps,
        className: classnames( CLASS_NAME, className ),
        isBlock: true,
        __nextHasNoMarginBottom: true,
        __next40pxDefaultSize: true
    };

    // noinspection JSValidateTypes
    return (
        <ToggleGroupControl
            { ...toggleProps }
            value={ value }
            onChange={ onChange }
        >
            { options.map( option => <ToggleGroupControlOption key={ option.value } { ...option }/> ) }
        </ToggleGroupControl>
    );
};

export default ToggleSelectControl;