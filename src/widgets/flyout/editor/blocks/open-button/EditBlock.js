import { useBlockProps } from "@wordpress/block-editor";
import { getIconSetsIcon, useStyles } from "#editor";
import { Icon } from "@wordpress/icons";

export const OPEN_BUTTON_CLASS_NAME = 'fc--flyout-open-button';

const EditBlock = props => {

    const {
        settings,
        settingsDefaults,
        styles,
        iconSets
    } = props;

    const buttonStyles = useStyles( styles, {
        background: 'background',
        icon: 'color'
    } );

    const buttonProps = useBlockProps( {
        className: OPEN_BUTTON_CLASS_NAME,
        style: {
            ...buttonStyles,
            fontSize: settings?.icon?.size ?? settingsDefaults?.icon?.size
        }
    } );

    const slug = settings?.icon?.open?.slug ?? settingsDefaults?.icon?.open?.slug ?? 'wordpress-plus';
    const icon = getIconSetsIcon( iconSets, slug );
    return (
        <button { ...buttonProps }>
            <Icon icon={ icon.svg } size={ settings?.icon?.size ?? settingsDefaults?.icon?.size ?? '24px' } />
        </button>
    );
};

export default EditBlock;