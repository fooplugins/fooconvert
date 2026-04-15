import cfg from "../config";

const POPUP_SELECTOR = cfg.popups.join(',');

/**
 *
 * @param {HTMLElement} childElement
 * @returns {*}
 */
const getParentPopupElement = childElement => {
    const found = childElement.closest( POPUP_SELECTOR );
    if ( found instanceof Node ) {
        globalThis.customElements.upgrade( found );
    }
    return found;
};

export default getParentPopupElement;