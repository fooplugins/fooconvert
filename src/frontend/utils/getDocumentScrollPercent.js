/**
 * Get the current document scrolled percentage as a number from 0-100.
 * @returns {number}
 */
const getDocumentScrollPercent = () => ( global.scrollY / ( global.document.documentElement.scrollHeight - global.document.documentElement.clientHeight ) ) * 100;

export default getDocumentScrollPercent;