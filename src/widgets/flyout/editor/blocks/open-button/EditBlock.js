import { useBlockProps } from "@wordpress/block-editor";
import { SlugIcon, useStyles } from "#editor";

export const OPEN_BUTTON_CLASS_NAME = 'fc--flyout-open-button';

const EditBlock = props => {

    const {
        settings: {
            icon
        },
        settingsDefaults: {
            icon: iconDefault
        },
        styles,
    } = props;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );

    const buttonProps = useBlockProps( {
        className: OPEN_BUTTON_CLASS_NAME,
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