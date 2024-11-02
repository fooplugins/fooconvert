import { Icon } from "@wordpress/icons";
import { isValidElement } from "@wordpress/element";
import getIconBySlug from "./getIconBySlug";

/**
 *
 * @param {{ slug: string, size?: string }} props
 * @returns {JSX.Element}
 */
const SlugIcon = ( props ) => {
    const { slug, size } = props;
    const icon = getIconBySlug( slug );
    if ( !isValidElement( icon?.value ) ) {
        return null;
    }
    return ( <Icon icon={ icon.value } size={ size } /> );
};

export default SlugIcon;