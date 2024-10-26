import { useBlockProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { getIconSetsIcon, useStyles } from "#editor";
import { Icon } from "@wordpress/icons";

export const BUTTON_CLASS_NAME = 'fc--popup-close-button';

const EditBlock = props => {

    const {
        settings,
        defaultSettings,
        styles,
        iconSets
    } = props;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );

    const buttonProps = useBlockProps( {
        className: classnames( BUTTON_CLASS_NAME, {
            [`position-${ settings?.position ?? defaultSettings?.position }`]: settings?.position !== defaultSettings?.position
        } ),
        style: {
            ...buttonStyles,
            fontSize: settings?.icon?.size ?? defaultSettings?.icon?.size
        }
    } );

    const iconClose = getIconSetsIcon( iconSets, settings?.icon?.close?.slug ?? defaultSettings?.icon?.close?.slug ?? 'wordpress-closeSmall' );
    return (
        <button { ...buttonProps }>
            <Icon icon={ iconClose.svg } size={ settings?.icon?.size ?? defaultSettings?.icon?.size } />
        </button>
    );
};

export default EditBlock;