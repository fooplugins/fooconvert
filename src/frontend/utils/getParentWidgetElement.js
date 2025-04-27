import cfg from "../config";

const WIDGET_SELECTOR = cfg.widgets.join(',');

/**
 *
 * @param {HTMLElement} childElement
 * @returns {*}
 */
const getParentWidgetElement = childElement => {
    const found = childElement.closest( WIDGET_SELECTOR );
    if ( found instanceof Node ) {
        globalThis.customElements.upgrade( found );
    }
    return found;
};

export default getParentWidgetElement;