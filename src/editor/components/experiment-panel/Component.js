import { Button, PanelRow } from "@wordpress/components";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { __, sprintf } from "@wordpress/i18n";

import { useExperimentVariantLock, usePostTypeLabels } from "../../hooks";

import "./Component.scss";

const buildAdminUrl = path => {
    if ( typeof window?.ajaxurl === "string" && window.ajaxurl.includes( "admin-ajax.php" ) ) {
        return window.ajaxurl.replace( "admin-ajax.php", path );
    }

    return path;
};

const ExperimentPanel = () => {
    const { experimentId, role, label } = useExperimentVariantLock();
    const labels = usePostTypeLabels( { singular_name: __( "Widget", "fooconvert" ) } );

    if ( experimentId <= 0 ) {
        return null;
    }

    const roleLabel = role === "control" ? __( "Control", "fooconvert" ) : __( "Variant", "fooconvert" );
    const participantLabel = label || roleLabel;
    const singularName = labels?.singular_name || __( "Widget", "fooconvert" );

    return (
        <PluginDocumentSettingPanel name="fc--experiment" title={ __( "Experiment", "fooconvert" ) }>
            <PanelRow>
                <div className="fc-experiment-panel__description">
                    { sprintf( __( "This %s is part of an A/B test experiement", "fooconvert" ), singularName ) }
                </div>
            </PanelRow>
            <PanelRow>
                <div className="fc-experiment-panel__participant">
                    <div className="fc-experiment-panel__participant-label">{ __( "Participant", "fooconvert" ) }</div>
                    <div>{ participantLabel }</div>
                </div>
            </PanelRow>
            <PanelRow>
                <div className="fc-experiment-panel__actions">
                    <Button
                        className="fc-experiment-panel__button"
                        variant="secondary"
                        href={ buildAdminUrl( `post.php?post=${ experimentId }&action=edit` ) }
                        __next40pxDefaultSize>
                        { __( "Open Experiment", "fooconvert" ) }
                    </Button>
                    <Button
                        className="fc-experiment-panel__button"
                        variant="secondary"
                        href={ buildAdminUrl( `admin.php?page=fooconvert-experiment-results&experiment_id=${ experimentId }` ) }
                        __next40pxDefaultSize>
                        { __( "View Results", "fooconvert" ) }
                    </Button>
                </div>
            </PanelRow>
        </PluginDocumentSettingPanel>
    );
};

export default ExperimentPanel;
