import { __ } from "@wordpress/i18n";

const backgroundSizeHelpText = ( value ) => {
    if ( value === 'cover' || value === undefined ) {
        return __( 'Image covers the space evenly.', 'fooconvert' );
    }
    if ( value === 'contain' ) {
        return __( 'Image is contained without distortion.', 'fooconvert' );
    }
    return __( 'Image has a fixed width.', 'fooconvert' );
};

export default backgroundSizeHelpText;