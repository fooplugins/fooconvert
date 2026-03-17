import { PanelBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { IconsPickerControl, SizeControl, ToggleSelectControl } from "#editor";
import {
    image,
    positionCenter,
    positionLeft,
    positionRight,
    postContent, pullLeft, pullRight, row, stack
} from "@wordpress/icons";

const EditSettings = ( props ) => {
    const {
        segmentSettings,
        setSegmentSettings,
        segmentSettingsDefaults
    } = props;

    const layout = segmentSettings?.layout ?? segmentSettings?.layout ?? 'stack';
    const setLayout = value => setSegmentSettings( { layout: value !== segmentSettingsDefaults?.layout ? value : undefined } );

    const layoutOptions = [ {
        value: 'stack',
        label: __( 'Under', 'fooconvert' )
    }, {
        value: 'row',
        label: __( 'Inline', 'fooconvert' )
    } ];

    return (
        <>
            <PanelBody title={ __( 'Segment', 'fooconvert' ) } initialOpen={ true }>
                <ToggleSelectControl
                    label={ __( 'Text Position', 'fooconvert' ) }
                    value={ layout }
                    onChange={ setLayout }
                    options={ layoutOptions }
                    help={ __( 'The position of the text relative to the digit.', 'fooconvert' ) }
                />
            </PanelBody>
        </>
    );
};

export default EditSettings;