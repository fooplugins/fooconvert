import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import { useInnerBlocks, useStyles } from "#editor";

import ContentAppender from "./ContentAppender";

const PopupContentEditBlock = ( props ) => {
    const {
        className,
        clientId,
        extraStyle = {},
        styles,
    } = props;

    const { hasInnerBlocks } = useInnerBlocks( clientId );
    const inlineStyles = useStyles( styles );

    const blockProps = useBlockProps( {
        className: classnames( className, {
            "show-inserter": !hasInnerBlocks,
        } ),
        style: {
            ...inlineStyles,
            ...extraStyle,
        },
    } );

    const { children, ...contentProps } = useInnerBlocksProps( blockProps, {
        templateLock: false,
        orientation: "vertical",
        renderAppender: () => (
            <ContentAppender
                className={ `${ className }--button-block-appender` }
                rootClientId={ clientId }
            />
        ),
    } );

    return (
        <div { ...contentProps }>
            { children }
        </div>
    );
};

export default PopupContentEditBlock;
