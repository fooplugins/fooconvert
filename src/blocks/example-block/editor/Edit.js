import { useBlockProps, RichText } from "@wordpress/block-editor";
import Settings from "./Settings";
import { useStyles } from "#editor";

const Edit = ( props ) => {

    const {
        attributes: { content, styles },
        setAttributes
    } = props;

    const customStyles = useStyles( styles );

    const blockProps = useBlockProps({
        className: "fc--example-block",
        style: {
            ...customStyles
        },
        format: 'string',
        allowedFormats: [ 'core/bold', 'core/italic' ],
        tagName: 'div',
        withoutInteractiveFormatting: true
    });

    return (
        <>
            <Settings { ...props }/>
            <RichText
                { ...blockProps }
                value={ content }
                onChange={ value => setAttributes( { content: value } ) }
            />
        </>
    );
};

export default Edit;