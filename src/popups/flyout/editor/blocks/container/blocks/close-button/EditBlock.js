import PopupButtonEditBlock from "../../../../../../shared/editor/blocks/button/EditBlock";

export const BUTTON_CLASS_NAME = "fc--flyout-close-button";

const EditBlock = props => (
    <PopupButtonEditBlock
        { ...props }
        className={ BUTTON_CLASS_NAME }
        positionClassName="position"
    />
);

export default EditBlock;
