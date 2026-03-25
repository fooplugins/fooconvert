import { PluginPostStatusInfo } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";

import { DisplayRulesPostStatusInfo, DisplayRulesPrePublishPanel } from "../../components";
import { useExperimentVariantLock } from "../../hooks";

const DisplayRulesPlugin = () => {
    const { isLocked } = useExperimentVariantLock();

    if ( isLocked ) {
        return (
            <PluginPostStatusInfo>
                <strong>{ __( "Display rules", "fooconvert" ) }</strong>
                <div>{ __( "Inherited", "fooconvert" ) }</div>
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
