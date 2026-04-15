import PopupButtonEditBlock from "../../../../shared/editor/blocks/button/EditBlock";

export const OPEN_BUTTON_CLASS_NAME = "fc--bar-open-button";

const EditBlock = props => (
    <PopupButtonEditBlock
        { ...props }
        className={ OPEN_BUTTON_CLASS_NAME }
        positionClassName="open-button-position"
    />
);

export default EditBlock;
