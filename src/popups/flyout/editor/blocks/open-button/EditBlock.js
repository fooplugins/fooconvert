import PopupButtonEditBlock from "../../../../shared/editor/blocks/button/EditBlock";

export const OPEN_BUTTON_CLASS_NAME = "fc--flyout-open-button";

const EditBlock = props => (
    <PopupButtonEditBlock
        { ...props }
        className={ OPEN_BUTTON_CLASS_NAME }
    />
);

export default EditBlock;
