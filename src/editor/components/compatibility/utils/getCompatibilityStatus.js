import { __ } from "@wordpress/i18n";
import { info, published } from "@wordpress/icons";

const getCompatibilityStatus = ({ enabled = false, required = false }) => {
    let value, text, icon;
    if ( enabled ) {
        value = 'enabled';
        text = __( 'Enabled', 'fooconvert' );
        icon = published;
    } else {
        value = required ? 'warning' : 'disabled';
        text = required ? __( 'Warning', 'fooconvert' ) : __( 'Disabled', 'fooconvert' );
        icon = required ? info : undefined;
    }
    return { value, text, icon };
};

export default getCompatibilityStatus;