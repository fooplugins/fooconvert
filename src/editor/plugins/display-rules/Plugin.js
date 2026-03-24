import { PluginPostStatusInfo } from "@wordpress/editor";
import { __, sprintf } from "@wordpress/i18n";

import { DisplayRulesPostStatusInfo, DisplayRulesPrePublishPanel } from "../../components";
import { useExperimentVariantLock } from "../../hooks";

const DisplayRulesPlugin = () => {
    const { isLocked, label } = useExperimentVariantLock();

    if ( isLocked ) {
        const message = label
            ? sprintf(
                __( "Variant %s inherits display rules from the experiment control widget.", "fooconvert" ),
                label.toUpperCase()
            )
            : __( "This experiment variant inherits display rules from the control widget.", "fooconvert" );

        return (
            <PluginPostStatusInfo>
                <strong>{ __( "Display rules", "fooconvert" ) }</strong>
                <div>{ message }</div>
            </PluginPostStatusInfo>
        );
    }

    return (
        <>
            <DisplayRulesPostStatusInfo/>
            <DisplayRulesPrePublishPanel/>
        </>
    );
};

export default DisplayRulesPlugin;
