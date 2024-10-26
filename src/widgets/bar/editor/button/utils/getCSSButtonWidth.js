import { getBorderSizes, getBoxUnitSizes } from "#editor";

const getCSSButtonWidth = ( attributes, defaults ) => {
    const { left: borderLeft, right: borderRight } = getBorderSizes( attributes?.styles?.border ?? defaults?.styles?.border );
    const { left: paddingLeft, right: paddingRight } = getBoxUnitSizes( attributes?.styles?.dimensions?.padding ?? defaults?.styles?.dimensions?.padding );
    const { left: marginLeft, right: marginRight } = getBoxUnitSizes( attributes?.styles?.dimensions?.margin, defaults?.styles?.dimensions?.margin );
    const iconSize = attributes?.icon?.size ?? defaults?.icon?.size;
    return `calc(${ borderLeft } + ${ borderRight } + ${ paddingLeft } + ${ paddingRight } + ${ marginLeft } + ${ marginRight } + ${ iconSize })`;
};

export default getCSSButtonWidth;