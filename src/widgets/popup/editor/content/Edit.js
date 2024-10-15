import { useBlockProps, useInnerBlocksProps } from "@wordpress/block-editor";
import classnames from "classnames";
import {
    getBoxUnitSizes,
    useBorderStyle,
    useColorStyle,
    useDimensionStyle,
    useOverrideSelectedBlock
} from "#editor";
import getCSSButtonWidth from "../button/utils/getCSSButtonWidth";
import { BUTTON_DEFAULTS } from "../button/Edit";
import { toCamelCase } from "@steveush/utils";
import ContentAppender from "./ContentAppender";

export const CONTENT_CLASS_NAME = 'fc--popup-content';

const Edit = props => {

    const {
        clientId,
        isSelected,
        context: {
            'fc-popup/clientId': parentClientId,
            'fc-popup/button': button,
            'fc-popup/styles': styles,
            'fc-popup/hideButton': isButtonHidden
        }
    } = props;

    useOverrideSelectedBlock( isSelected, parentClientId );

    const buttonOffset = {};
    if ( !isButtonHidden ) {
        const buttonWidth = getCSSButtonWidth( button, BUTTON_DEFAULTS );
        const buttonPosition = button?.position ?? BUTTON_DEFAULTS.position;
        const padding = getBoxUnitSizes( styles?.dimensions?.padding );
        buttonOffset[ toCamelCase( `padding-${ buttonPosition }` ) ] = `calc( ${ padding[ buttonPosition ] } + ${ buttonWidth })`;
    }
    const borderStyle = useBorderStyle( styles?.border );
    const colorStyle = useColorStyle( styles?.color, { background: 'background' } );
    const dimensionsStyle = useDimensionStyle( styles?.dimensions, [ 'padding' ] );
    const blockProps = useBlockProps( {
        className: classnames( CONTENT_CLASS_NAME ),
        style: {
            ...borderStyle,
            ...colorStyle,
            ...dimensionsStyle,
            // ...buttonOffset
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

export default Edit;