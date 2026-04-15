import PopupButtonEditBlock from "../../../../../../shared/editor/blocks/button/EditBlock";

export const BUTTON_CLASS_NAME = "fc--bar-close-button";

const EditBlock = props => (
    <PopupButtonEditBlock
        { ...props }
        className={ BUTTON_CLASS_NAME }
        positionClassName="close-button-position"
    />
);

export default EditBlock;
