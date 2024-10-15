import { useBlockProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { getIconSetsIcon, useStyles } from "#editor";
import { Icon } from "@wordpress/icons";
import getButtonTransform from "./utils/getButtonTransform";

export const BUTTON_CLASS_NAME = 'fc--popup-button';

const EditBlock = props => {

    const {
        isHidden,
        attributes,
        defaults,
        borderSizes,
        iconSets
    } = props;

    const {
        position = 'right',
        alignment = 'inside',
        icon,
        styles
    } = attributes;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );

    const buttonTransform = getButtonTransform( attributes, defaults, borderSizes );

    const buttonProps = useBlockProps( {
        className: classnames( BUTTON_CLASS_NAME, {
            'is-hidden': isHidden,
            [`position-${ position }`]: position !== defaults?.position,
            [`alignment-${ alignment }`]: alignment !== defaults?.alignment
        } ),
        style: {
            ...buttonStyles,
            ...buttonTransform,
            fontSize: icon?.size ?? defaults?.size
        }
    } );

    const iconClose = getIconSetsIcon( iconSets, icon?.close?.slug ?? defaults?.close?.slug ?? 'wordpress-close' );
    return (
        <button { ...buttonProps }>
            <Icon icon={ iconClose.svg } size={ icon?.size ?? defaults?.size } />
        </button>
    );
};

export default EditBlock;