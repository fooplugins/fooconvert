import { useBlockProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { SlugIcon, useStyles } from "#editor";

export const BUTTON_CLASS_NAME = 'fc--flyout-close-button';

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
        className: classnames( BUTTON_CLASS_NAME, {
            [`position-${ position ?? positionDefault }`]: position !== positionDefault
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