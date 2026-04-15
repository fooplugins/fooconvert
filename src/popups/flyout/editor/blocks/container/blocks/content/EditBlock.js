import PopupContentEditBlock from "../../../../../../shared/editor/blocks/content/EditBlock";
import { CONTENT_CLASS_NAME } from "./Edit";

const EditBlock = props => {
    const {
        styles,
        stylesDefaults,
    } = props;

    const width = styles?.width ?? stylesDefaults?.width;
    const extraStyle = {};

    if ( width !== stylesDefaults?.width ) {
        extraStyle.width = width;
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
