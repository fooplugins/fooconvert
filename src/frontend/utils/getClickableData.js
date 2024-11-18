import { isString } from "@steveush/utils";

const stringOrUndefined = value => isString( value, true ) ? value : undefined;

const isWPBlockButton = element => element.classList.contains( 'wp-block-button__link' ) && !!element.parentElement?.classList.contains( 'wp-block-button');

const getWPBlockButtonData = element => {
    const { id, classList = [] } = element.parentElement;
    const { innerText, href, target, rel } = element;
    return {
        tagName: element.tagName.toLowerCase(),
        classList: [ ...classList ],
        id: stringOrUndefined( id ),
        innerText: stringOrUndefined( innerText ),
        href: stringOrUndefined( href ),
        target: stringOrUndefined( target ),
        rel: stringOrUndefined( rel ),
    };
};

const getAnchorData = element => {
    if ( isWPBlockButton( element ) ) {
        return getWPBlockButtonData( element );
    }
    const { id, classList, innerText, href, target, rel } = element;
    return {
        tagName: element.tagName.toLowerCase(),
        classList: [ ...classList ],
        id: stringOrUndefined( id ),
        innerText: stringOrUndefined( innerText ),
        href: stringOrUndefined( href ),
        target: stringOrUndefined( target ),
        rel: stringOrUndefined( rel ),
    };
};

const getButtonData = element => {
    const { id, name, classList, innerText } = element;
    return {
        tagName: element.tagName.toLowerCase(),
        classList: [ ...classList ],
        id: stringOrUndefined( id ),
        name: stringOrUndefined( name ),
        innerText: stringOrUndefined( innerText ),
    };
};

const getInputData = element => {
    const { id, classList, name } = element;
    return {
        tagName: element.tagName.toLowerCase(),
        classList: [ ...classList ],
        id: stringOrUndefined( id ),
        name: stringOrUndefined( name ),
    }
}

const getClickableData = element => {
    switch ( element.tagName.toLowerCase() ) {
        case 'a':
            return getAnchorData( element );
        case 'button':
            return getButtonData( element );
        case 'input':
        case 'textarea':
        case 'select':
            return getInputData( element );
        default:
            return undefined;
    }
};

export default getClickableData;