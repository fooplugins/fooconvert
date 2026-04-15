import { useBlockProps } from "@wordpress/block-editor";
import { getCSSBackgroundProperty, SlugIcon, useStyles } from "#editor";
import classnames from "classnames";

const PopupButtonEditBlock = ( props ) => {
    const {
        className,
        positionClassName,
        settings = {},
        settingsDefaults = {},
        styles,
    } = props;

    const {
        position,
        icon,
    } = settings;
    const {
        position: positionDefault,
        icon: iconDefault,
    } = settingsDefaults;

    const buttonStyles = useStyles( styles, {
        background: getCSSBackgroundProperty,
        icon: "color",
    } );

    const positionClass = positionClassName && position !== positionDefault
        ? `${ positionClassName }-${ position ?? positionDefault }`
        : undefined;

    const buttonProps = useBlockProps( {
        className: classnames( className, {
            [positionClass]: positionClass !== undefined,
        } ),
        style: {
            ...buttonStyles,
            fontSize: icon?.size ?? iconDefault?.size,
        },
    } );

    return (
        <button { ...buttonProps }>
            <SlugIcon slug={ icon?.slug ?? iconDefault?.slug }/>
        </button>
    );
};

export default PopupButtonEditBlock;
