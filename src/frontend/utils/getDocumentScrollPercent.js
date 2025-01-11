/**
 * Get the current document scrolled percentage as a number from 0-100.
 * @returns {number}
 */
const getDocumentScrollPercent = () => ( globalThis.scrollY / ( globalThis.document.documentElement.scrollHeight - globalThis.document.documentElement.clientHeight ) ) * 100;

export default getDocumentScrollPercent;