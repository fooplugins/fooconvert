import { Icon } from "@wordpress/icons";
import { isValidElement } from "@wordpress/element";

/**
 *
 * @param {{ icon: import("react").ReactElement, size?: number|string }} props
 * @returns {JSX.Element}
 */
const SafeIcon = ( props ) => {
    const { icon, size } = props;
    if ( !isValidElement( icon ) ) {
        return null;
    }
    return ( <Icon icon={ icon } size={ size } /> );
};

export default SafeIcon;
