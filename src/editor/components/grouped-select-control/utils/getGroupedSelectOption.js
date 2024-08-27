import isGroupedSelectOption from "./isGroupedSelectOption";
import isGroupedSelectOptGroup from "./isGroupedSelectOptGroup";

/**
 *
 * @param {GroupedSelectOptions} options
 * @param {string} value
 * @returns {?GroupedSelectOption}
 */
const getGroupedSelectOption = ( options, value ) => {
    for ( const optionOrGroup of options ) {
        if ( isGroupedSelectOption( optionOrGroup ) ) {
            if ( optionOrGroup.value === value ) {
                return optionOrGroup;
            }
            continue;
        }
        if ( isGroupedSelectOptGroup( optionOrGroup ) ) {
            const result = getGroupedSelectOption( optionOrGroup.options, value );
            if ( result ) {
                return result;
            }
        }
    }
};

export default getGroupedSelectOption;