import { Inserter } from "@wordpress/block-editor";
import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { plus } from "@wordpress/icons";

const ContentAppender = ( { rootClientId, disable } ) => {
    if ( disable ) {
        return null;
    }
    return (
        <Inserter
            rootClientId={ rootClientId }
            position="bottom right"
            renderToggle={ ( { onToggle, ...props } ) => {
                return (
                    <Button
                        variant="secondary"
                        size="small"
                        className="fc--bar-content--button-block-appender"
                        onClick={ onToggle }
                        label={ __( 'Add block', 'fooconvert' ) }
                        icon={ plus }
                    />
                );
            } }
            isAppender
            __experimentalIsQuick
        />
    );
};

export default ContentAppender;