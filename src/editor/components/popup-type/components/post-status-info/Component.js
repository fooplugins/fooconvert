import { PluginPostStatusInfo, store as editorStore } from "@wordpress/editor";
import { useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";

import { usePostTypeLabels } from "../../../../hooks";
import "./Component.scss";

const rootClass = "fc--popup-type__post-status-info";

const PopupTypePostStatusInfo = () => {
    const currentPostType = useSelect( select => {
        return select( editorStore )?.getCurrentPostType() || "";
    }, [] );

    const { singular_name: popupTypeLabel = "" } = usePostTypeLabels( { singular_name: "" } );

    if ( currentPostType !== "fc-popup" || popupTypeLabel.length === 0 ) {
        return null;
    }

    return (
        <PluginPostStatusInfo className={ rootClass }>
            <div className={ `${ rootClass }__label` }>{ __( "Popup Type", "fooconvert" ) }</div>
            <div className={ `${ rootClass }__value` }>{ popupTypeLabel }</div>
        </PluginPostStatusInfo>
    );
};

export default PopupTypePostStatusInfo;
