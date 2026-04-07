import "./EditSettings.scss";

import { InspectorControls } from "@wordpress/block-editor";
import { TabPanel } from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";
import { __ } from "@wordpress/i18n";
import { cog, styles } from "@wordpress/icons";

import {
    ContainerEditSettings,
    ContainerEditStyles,
    ButtonEditSettings,
    ButtonEditStyles, CodeEditSettings, CodeEditStyles
} from "./components";

const EditSettings = props => {
    const extensionPanels = applyFilters( 'fooconvert.coupon.editSettings', [], props );

    const styleTabs = [{
        name: 'container',
        title: __( 'Block', 'fooconvert' )
    },{
        name: 'code',
        title: __( 'Coupon', 'fooconvert' )
    },{
        name: 'button',
        title: __( 'Button', 'fooconvert' )
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
                    <CodeEditSettings { ...props }/>
                    <ButtonEditSettings { ...props }/>
                    { extensionPanels }
                </>
            );
        }
        if ( tab.name === 'styles' ) {
            return (
                <TabPanel
                    className="fc--coupon__sidebar-tabs"
                    tabs={ styleTabs }
                >
                    { ( styleTab ) => {
                        switch ( styleTab.name ) {
                            case "container":
                                return ( <ContainerEditStyles { ...props } /> );
                            case "code":
                                return ( <CodeEditStyles { ...props } /> );
                            case "button":
                                return ( <ButtonEditStyles { ...props } /> );
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
                className="fc--coupon__sidebar-tabs"
                tabs={ tabs }
            >
                { renderTabs }
            </TabPanel>
        </InspectorControls>
    );
};

export default EditSettings;
