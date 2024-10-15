import { getBorderSizes, getBoxUnitSizes } from "#editor";

const getButtonTransform = ( attributes, defaults, borderSizes ) => {

    const { left: borderLeft, right: borderRight, top: borderTop, bottom: borderBottom } = borderSizes;
    const {
        left: marginLeft,
        right: marginRight,
        top: marginTop,
        bottom: marginBottom
    } = getBoxUnitSizes( attributes?.styles?.dimensions?.margin, defaults?.styles?.dimensions?.margin );
    const vertical = `(${ marginTop } + ${ marginBottom })`;
    const horizontal = `(${ marginLeft } + ${ marginRight })`;
    const position = attributes?.position ?? defaults?.position;
    const alignment = attributes?.alignment ?? defaults?.alignment;
    const css = {};
    if ( position === 'left' ) {
        switch ( alignment ) {
            case 'inside':
                break;
            case 'outside':
                css.transform = `translateX(calc(-100% - ${ horizontal })) translateY(calc(-100% - ${ vertical }))`;
                break;
            case 'corner':
                css.transform = `translateX(calc(-50% - ${ marginLeft } + (${ borderLeft }/2))) translateY(calc(-50% - ${ marginTop } + (${ borderTop }/2)))`;
                break;
        }
    } else {
        switch ( alignment ) {
            case 'inside':
                break;
            case 'outside':
                css.transform = `translateX(calc(100% + ${ horizontal })) translateY(calc(-100% - ${ vertical }))`;
                break;
            case 'corner':
                css.transform = `translateX(calc(50% + ${ marginRight } - (${ borderRight }/2))) translateY(calc(-50% - ${ marginTop } + (${ borderTop }/2)))`;
                break;
        }
    }
    return css;
};

export default getButtonTransform;