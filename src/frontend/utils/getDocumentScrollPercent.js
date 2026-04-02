/**
 * Get the current document scrolled percentage as a number from 0-100.
 * @returns {number}
 */
const getDocumentScrollPercent = () => {
    const root = globalThis?.document?.documentElement;
    if ( !( root instanceof HTMLElement ) ) {
        return 0;
    }

    const maxScroll = root.scrollHeight - root.clientHeight;
    if ( maxScroll <= 0 ) {
        return 100;
    }

    const percent = ( globalThis.scrollY / maxScroll ) * 100;
    if ( !Number.isFinite( percent ) ) {
        return 0;
    }

    return Math.max( 0, Math.min( 100, percent ) );
};

export default getDocumentScrollPercent;
