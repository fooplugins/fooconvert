import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore, PluginDocumentSettingPanel } from "@wordpress/editor";
import { PanelRow, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import "./Plugin.scss";

const PluginCustomEditor = () => {
    const title = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( 'title' );
    }, [] );

    const { editPost } = useDispatch( editorStore );

    return (
        <PluginDocumentSettingPanel name="post-title-setting-panel" title={ __( 'Title', 'fooconvert' ) }>
            <PanelRow>
                <TextControl
                    value={ title }
                    onChange={ value => editPost( { title: value } ) }
                    help={ __( 'Set the post title', 'fooconvert' ) }
                />
            </PanelRow>
        </PluginDocumentSettingPanel>
    );
};

export default PluginCustomEditor;