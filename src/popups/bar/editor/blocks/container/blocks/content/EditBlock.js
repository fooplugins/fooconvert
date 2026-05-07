import PopupContentEditBlock from "../../../../../../shared/editor/blocks/content/EditBlock";
import { CONTENT_CLASS_NAME } from "./Edit";
import {
    getBarContentWidth,
    isBarContentWidthMode,
} from "../../../../size-controls";

const EditBlock = props => {
    const {
        parentAttributes,
        parentAttributesDefaults,
        styles,
        stylesDefaults,
    } = props;

    const settings = parentAttributes?.settings ?? {};
    const settingsDefaults = parentAttributesDefaults?.settings ?? {};
    const extraStyle = {};

    if ( isBarContentWidthMode( settings, settingsDefaults ) ) {
        extraStyle.width = getBarContentWidth( styles, stylesDefaults );
    }

    return (
        <PopupContentEditBlock
            { ...props }
            className={ CONTENT_CLASS_NAME }
            extraStyle={ extraStyle }
        />
    );
};

export default EditBlock;
