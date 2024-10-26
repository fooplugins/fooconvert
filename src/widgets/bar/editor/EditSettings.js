import { BlockControls, InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    BorderToolsPanel,
    ColorToolsPanel, DimensionToolsPanel,
    // DisplayRulesButton,
    InnerBlocksButton,
    OpenTriggerPanel, ToggleSelectControl,
    ToolbarVariationMenu
} from "#editor";
import { update } from "@wordpress/icons";
import { cleanObject, isString } from "@steveush/utils";
import { useState } from "@wordpress/element";
import { PluginDocumentSettingPanel } from "@wordpress/editor";

const EditSettings = props => {
    const {
        clientId,
        attributes: {
            styles,
            trigger,
            lockTrigger,
            closeAnchor,
            hideButton,
            transitions,
            position,
            pagePush
        },
        setAttributes,
        defaults
    } = props;

    const [ closeAnchorChecked, setCloseAnchorChecked ] = useState( isString( closeAnchor, true ) );

    const setTrigger = ( value ) => setAttributes( { trigger: value } );
    const setCloseAnchor = value => setAttributes( { closeAnchor: isString( value, true ) ? value : undefined } );
    const setHideButton = value => setAttributes( { hideButton: value !== defaults?.hideButton ? value : undefined } );
    const setTransitions = value => setAttributes( { transitions: value !== defaults?.transitions ? value : undefined } );
    const setPosition = value => setAttributes( { position: value !== defaults?.position ? value : undefined } );
    const setPagePush = value => setAttributes( { pagePush: value !== defaults?.pagePush ? value : undefined } );
    const setStyles = newValue => {
        const previousValue = styles ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        setAttributes( { styles: cleanObject( nextValue ) } );
    };

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setDimensions = value => setStyles( { dimensions: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    }, {
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    } ];

    const positions = [{
        value: 'top',
        label: __( 'Top', 'fooconvert' )
    },{
        value: 'bottom',
        label: __( 'Bottom', 'fooconvert' )
    }];

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( 'Position', 'fooconvert' ) }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( 'Position', 'fooconvert' ) }
                            hideLabelFromVision={ true }
                            value={ position ?? defaults?.position }
                            onChange={ setPosition }
                            options={ positions }
                            help={ __( 'Choose where to display the bar within the page.', 'fooconvert' ) }
                        />
                    </PanelRow>
                </PanelBody>
                <PanelBody title={ __( 'Behavior', 'fooconvert' ) }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable transitions', 'fooconvert' ) }
                            help={ __( 'Choose if transitions are used when toggling the bar.', 'fooconvert' ) }
                            checked={ transitions ?? defaults?.transitions }
                            onChange={ setTransitions }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable page push', 'fooconvert' ) }
                            help={ __( 'Choose if the page is "pushed", either up or down, based on the bar height and position.', 'fooconvert' ) }
                            checked={ pagePush ?? defaults?.pagePush }
                            onChange={ setPagePush }
                        />
                    </PanelRow>
                </PanelBody>
                <OpenTriggerPanel
                    value={ trigger }
                    onChange={ value => setTrigger( value ) }
                    locked={ lockTrigger }
                />
                <PanelBody title={ __( 'Close Trigger', 'fooconvert' ) }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Hide close button', 'fooconvert' ) }
                            help={ __( 'Hide the default close button.', 'fooconvert' ) }
                            checked={ hideButton ?? defaults?.hideButton }
                            onChange={ setHideButton }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Close on anchor click', 'fooconvert' ) }
                            help={ __( 'Clicking specific anchors closes the notification.', 'fooconvert' ) }
                            checked={ closeAnchorChecked }
                            onChange={ value => setCloseAnchorChecked( value ) }
                        />
                    </PanelRow>
                    { closeAnchorChecked && (
                        <PanelRow>
                            <TextControl
                                label={ __( 'Anchor', 'fooconvert' ) }
                                help={ __( 'Add an anchor to a button block and then insert the same value here to close the notification on click.', 'fooconvert' ) }
                                value={ closeAnchor ?? "" }
                                onChange={ value => setCloseAnchor( value !== "" ? value : undefined ) }
                            />
                        </PanelRow>
                    ) }
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="advanced">
                <PanelRow>
                    <InnerBlocksButton
                        targetClientId={ clientId }
                        prepareAttributes={ ( attr, slug ) => {
                            const { clientId, postId, ...result } = attr;
                            return { ...result, variation: slug };
                        } }
                        variant="secondary">
                        { __( 'Make Variation', 'fooconvert' ) }
                    </InnerBlocksButton>
                </PanelRow>
            </InspectorControls>
            <InspectorControls group="styles">
                <ColorToolsPanel
                    panelId={ clientId }
                    value={ styles?.color }
                    onChange={ setColor }
                    options={ colors }
                    defaults={ defaults?.styles?.color }
                />
                <BorderToolsPanel
                    panelId={ clientId }
                    value={ styles?.border }
                    onChange={ setBorder }
                />
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    controls={ [ 'margin', 'padding', 'gap' ] }
                    defaults={ defaults?.styles?.dimensions }
                />
            </InspectorControls>
            {/*<PluginDocumentSettingPanel name="fooconvert-display-rules" title={ __( 'Display Rules', 'fooconvert' ) }  >*/}
            {/*    <PanelRow>*/}
            {/*        <DisplayRulesButton variant="secondary" label={ __( 'Edit Rules', 'fooconvert' ) }/>*/}
            {/*    </PanelRow>*/}
            {/*</PluginDocumentSettingPanel>*/}
        </>
    );
};

export default EditSettings;