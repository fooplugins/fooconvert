import { useBlockProps } from "@wordpress/block-editor";
import { SlugIcon, useStyles } from "#editor";
import classnames from "classnames";

export const OPEN_BUTTON_CLASS_NAME = 'fc--bar-open-button';

const EditBlock = props => {

    const {
        settings: {
            position,
            icon
        },
        settingsDefaults: {
            position: positionDefault,
            icon: iconDefault
        },
        styles
    } = props;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );

    const buttonProps = useBlockProps( {
        className: classnames( OPEN_BUTTON_CLASS_NAME, {
            [`open-button-position-${ position ?? positionDefault }`]: position !== positionDefault
        } ),
        style: {
            ...buttonStyles,
            fontSize: icon?.size ?? iconDefault?.size
        }
    } );

    return (
        <button { ...buttonProps }>
            <SlugIcon slug={ icon?.slug ?? iconDefault?.slug }/>
        </button>
    );
};

export default EditBlock;