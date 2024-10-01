import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore, PluginDocumentSettingPanel } from "@wordpress/editor";
import { PanelRow, TextControl } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";

import "./Plugin.scss";
import usePostTypeLabels from "../../hooks/usePostTypeLabels";

const PluginCustomEditor = () => {
    const title = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( 'title' );
    }, [] );

    const labels = usePostTypeLabels() ?? { singular_name: '' };
    const help = sprintf( __( 'Set the title for the %s', 'fooconvert' ), labels.singular_name );

    const { editPost } = useDispatch( editorStore );

    return (
        <PluginDocumentSettingPanel name="fc--post-title" title={ __( 'Title', 'fooconvert' ) }>
            <PanelRow>
                <TextControl
                    className={ 'fc--post-title__text-control' }
                    value={ title }
                    onChange={ value => editPost( { title: value } ) }
                    help={ help }
                    __next40pxDefaultSize
                />
            </PanelRow>
        </PluginDocumentSettingPanel>
    );
};

export default PluginCustomEditor;