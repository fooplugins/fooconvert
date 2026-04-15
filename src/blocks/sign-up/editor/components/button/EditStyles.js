import ButtonEditStyles from "../../../../shared/editor/buttonStyles";

const EditStyles = props => (
    <ButtonEditStyles
        { ...props }
        dimensionControls={ [ "padding", "margin", "gap" ] }
    />
);

export default EditStyles;
