import PopupContentEditBlock from "../../../../../../shared/editor/blocks/content/EditBlock";
import { CONTENT_CLASS_NAME } from "./Edit";

const EditBlock = props => (
    <PopupContentEditBlock
        { ...props }
        className={ CONTENT_CLASS_NAME }
    />
);

export default EditBlock;
