import { ToggleSelectControl } from "#editor";
import { __ } from "@wordpress/i18n";
import { cleanObject } from "@steveush/utils";
import { arrowDown, arrowLeft, arrowRight, arrowUp, lineSolid } from "@wordpress/icons";
import "./Component.scss";

const rootClass = 'fc--flyout__position-control';

const getXY = value => {
    if ( typeof value === 'string' && value.includes( '-' ) ) {
        const parts = value.split( '-' );
        if ( parts.length === 2 ) {
            return {
                x: parts[0],
                y: parts[1]
            }
        }
    }
    return {
        x: 'right',
        y: 'center'
    }
};

const FlyoutPositionControl = ( props ) => {

    const {
        value,
        onChange,
        help = __( 'Choose where to display the flyout within the page.', 'fooconvert' )
    } = props;

    const { x, y } = getXY( value );

    const setX = value => {
        onChange( `${ value }-${ y }` );
    };

    const setY = value => {
        onChange( `${ x }-${ value }` );
    };

    const positionsX = [{
        value: 'left',
        label: __( 'Left', 'fooconvert' ),
        icon: arrowLeft
    },{
        value: 'right',
        label: __( 'Right', 'fooconvert' ),
        icon: arrowRight
    }];

    const positionsY = [{
        value: 'top',
        label: __( 'Top', 'fooconvert' ),
        icon: arrowUp
    },{
        value: 'center',
        label: __( 'Center', 'fooconvert' ),
        icon: lineSolid
    },{
        value: 'bottom',
        label: __( 'Bottom', 'fooconvert' ),
        icon: arrowDown
    }];

    return (
        <div className={ rootClass }>
            <div className={ `${ rootClass }__fields` }>
                <ToggleSelectControl
                    label={ __( 'Horizontal', 'fooconvert' ) }
                    value={ x }
                    onChange={ setX }
                    options={ positionsX }
                    iconOnly={ true }
                />
                <ToggleSelectControl
                    label={ __( 'Vertical', 'fooconvert' ) }
                    value={ y }
                    onChange={ setY }
                    options={ positionsY }
                    iconOnly={ true }
                />
            </div>
            <p className={ `${ rootClass }__help` }>{ help }</p>
        </div>
    );
};

export default FlyoutPositionControl;