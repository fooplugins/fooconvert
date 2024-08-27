import useInnerBlocks from "./useInnerBlocks";
import { isArray } from "@steveush/utils";

const findInnerBlock = ( innerBlocks, blockName, deep = false ) => {
    if ( isArray( innerBlocks, true ) ) {
        for ( const innerBlock of innerBlocks ) {
            if ( innerBlock.name === blockName ) {
                return innerBlock;
            }
            if ( !deep ) continue;
            const found = findInnerBlock( innerBlock.innerBlocks, blockName, deep );
            if ( found ) {
                return found;
            }
        }
    }
    return null;
};

/**
 *
 * @param {string} clientId
 * @param {string} blockName
 * @param {boolean} deep
 * @return {?WPBlock}
 */
const useInnerBlock = ( clientId, blockName, deep = false ) => {
    const { innerBlocks } = useInnerBlocks( clientId );
    return findInnerBlock( innerBlocks, blockName, deep );
};

export default useInnerBlock;