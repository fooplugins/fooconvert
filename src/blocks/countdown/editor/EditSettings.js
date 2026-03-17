import "./EditSettings.scss";

import { InspectorControls } from "@wordpress/block-editor";
import { TabPanel } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { cog, styles } from "@wordpress/icons";
import {
    ContainerEditSettings,
    ContainerEditStyles,
    SegmentEditSettings,
    SegmentEditStyles
} from "./components";

const EditSettings = props => {

    const styleTabs = [{
        name: 'container',
        title: __( 'Container', 'fooconvert' )
    },{
        name: 'segment',
        title: __( 'Segment', 'fooconvert' )
    }];

    const tabs = [{
        name: 'settings',
        title: __( 'Settings', 'fooconvert' ),
        icon: cog
    },{
        name: 'styles',
        title: __( 'Styles', 'fooconvert' ),
        icon: styles
    }];

    const renderTabs = ( tab ) => {
        if ( tab.name === 'settings' ) {
            return (
                <>
                    <ContainerEditSettings { ...props }/>
                </>
            );
        }
        if ( tab.name === 'styles' ) {
            return (
                <TabPanel
                    className="fc--countdown__sidebar-tabs"
                    tabs={ styleTabs }
                >
                    { ( styleTab ) => {
                        switch ( styleTab.name ) {
                            case "container":
                                return ( <ContainerEditStyles { ...props } /> );
                            case "segment":
                                return ( <SegmentEditStyles { ...props } /> );
                            default:
                                return null;
                        }
                    } }
                </TabPanel>
            );
        }
        return null;
    };

    return (
        <InspectorControls>
            <TabPanel
                className="fc--countdown__sidebar-tabs"
                tabs={ tabs }
            >
                { renderTabs }
            </TabPanel>
        </InspectorControls>
    );
};

export default EditSettings;