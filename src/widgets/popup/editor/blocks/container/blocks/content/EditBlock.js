import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import {
    useBorderStyle,
    useColorStyle,
    useDimensionStyle,
    useInnerBlocks, useStyles
} from "#editor";
import ContentAppender from "./ContentAppender";

import { CONTENT_CLASS_NAME } from "./Edit";

const EditBlock = props => {

    const {
        clientId,
        styles,
        stylesDefaults
    } = props;

    const { hasInnerBlocks } = useInnerBlocks( clientId );

    const inlineStyles = useStyles( styles, { background: 'background', text: 'color' } );
    const width = styles?.width ?? stylesDefaults?.width;
    const widthStyle = {};
    if ( width !== stylesDefaults?.width ) {
        widthStyle['width'] = width;
    }
    const blockProps = useBlockProps( {
        className: classnames( CONTENT_CLASS_NAME, { 'show-inserter': !hasInnerBlocks } ),
        style: {
            ...inlineStyles,
            ...widthStyle
        }
    } );

    const { children, ...contentProps } = useInnerBlocksProps( blockProps, {
        templateLock: false,
        orientation: 'vertical',
        renderAppender: () => <ContentAppender rootClientId={ clientId }/>
    } );

    return (
        <div { ...contentProps }>
            { children }
        </div>
    );
};

export default EditBlock;