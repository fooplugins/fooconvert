import { useBlockProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { getIconSetsIcon, useStyles } from "#editor";
import { Icon } from "@wordpress/icons";

const CLASS_NAME = 'fc--bar-button';

const EditBlock = props => {

    const {
        isHidden,
        attributes: {
            position = 'right',
            icon,
            styles
        },
        defaults,
        iconSets
    } = props;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );
    const buttonProps = useBlockProps( {
        className: classnames( CLASS_NAME, {
            'is-hidden': isHidden,
            [`position-${ position }`]: position !== defaults?.position
        } ),
        style: {
            ...buttonStyles,
            fontSize: icon?.size ?? defaults?.size
        }
    } );

    const iconClose = getIconSetsIcon( iconSets, icon?.close?.slug ?? defaults?.close?.slug ?? 'wordpress-reset' );
    return (
        <button { ...buttonProps }>
            <Icon icon={ iconClose.svg } size={ icon?.size ?? defaults?.size } />
        </button>
    );
};

export default EditBlock;