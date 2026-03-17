import { InspectorControls } from "@wordpress/block-editor";
import { TabPanel } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { cog, styles } from "@wordpress/icons";
import {
    FormEditSettings,
    FormEditStyles,
    InputsEditSettings,
    InputsEditStyles,
    ButtonEditSettings,
    ButtonEditStyles
} from "./components";

const EditSettings = props => {

    const styleTabs = [{
        name: 'form',
        title: __( 'Form', 'fooconvert' )
    },{
        name: 'inputs',
        title: __( 'Inputs', 'fooconvert' )
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
                    <FormEditSettings { ...props } />
                    <InputsEditSettings { ...props } />
                    <ButtonEditSettings { ...props } />
                </>
            );
        }
        if ( tab.name === 'styles' ) {
            return (
                <TabPanel
                    className="fc--sidebar-tabs"
                    tabs={ styleTabs }
                >
                    { ( styleTab ) => {
                        switch ( styleTab.name ) {
                            case "form":
                                return ( <FormEditStyles { ...props } /> );
                            case "inputs":
                                return ( <InputsEditStyles { ...props } /> );
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
                className="fc--sidebar-tabs"
                tabs={ tabs }
            >
                { renderTabs }
            </TabPanel>
        </InspectorControls>
    );
};

export default EditSettings;