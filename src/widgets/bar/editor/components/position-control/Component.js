import { ToggleSelectControl } from "#editor";
import { __ } from "@wordpress/i18n";
import { arrowDown, arrowUp } from "@wordpress/icons";
import classnames from "classnames";

const CLASS_NAME = 'fc--bar__position-control';

const PositionControl = ( props ) => {
    const {
        value,
        onChange,
        label = __( 'Vertical' ),
        help = __( 'Choose where to display the bar within the page.', 'fooconvert' ),
        iconOnly = false,
        className
    } = props;

    const positions = [{
        value: 'top',
        label: __( 'Top', 'fooconvert' ),
        icon: arrowUp
    },{
        value: 'bottom',
        label: __( 'Bottom', 'fooconvert' ),
        icon: arrowDown
    }];

    return (
        <ToggleSelectControl
            className={ classnames( CLASS_NAME, className ) }
            label={ label }
            help={ help }
            value={ value }
            onChange={ onChange }
            options={ positions }
            iconOnly={ iconOnly }
        />
    );
};

export default PositionControl;