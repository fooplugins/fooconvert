import { useSelect } from '@wordpress/data';

/**
 * Determines whether an inner block is selected.
 *
 * @param {string} clientId - The client ID of the block.
 * @param {boolean} [deep=false] - Whether to check for deep selection.
 * @returns {boolean} - Whether an inner block is selected.
 */
const useIsInnerBlockSelected = ( clientId, deep = false ) => {
    return useSelect( ( select ) => {
        const { hasSelectedInnerBlock } = select( 'core/block-editor' );
        return hasSelectedInnerBlock( clientId, deep );
    }, [ clientId, deep ] );
};

export default useIsInnerBlockSelected;