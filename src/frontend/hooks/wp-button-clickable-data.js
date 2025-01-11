import stringOrUndefined from "../utils/stringOrUndefined";

/**
 *
 * @param element
 * @returns {boolean}
 */
const isWPBlockButton = element => element.classList.contains( 'wp-block-button__link' ) && !!element.parentElement?.classList.contains( 'wp-block-button' );

const getWPBlockButtonData = element => {
    const { classList = [], id } = element.parentElement;
    const { tagName, name, href, target, rel } = element;
    return {
        tagName: tagName.toLowerCase(),
        classList: [ ...classList ],
        id: stringOrUndefined( id ),
        name: stringOrUndefined( name ),
        href: stringOrUndefined( href ),
        target: stringOrUndefined( target ),
        rel: stringOrUndefined( rel ),
    };
};

globalThis.document.addEventListener( 'fooconvert.clickable-data', event => {
    if ( isWPBlockButton( event.target ) ) {
        event.detail.data = getWPBlockButtonData( event.target );
    }
} );