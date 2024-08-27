import { __ } from "@wordpress/i18n";
import { DisplayRulesLocationControl } from "./location-control";
import classNames from "classnames";
import { isArray } from "@steveush/utils";

import "./Component.scss";
import { RepeaterControl } from "../../../repeater-control";

const rootClass = 'fc-display-rules-locations-control';

/**
 * The properties for the `DisplayRulesLocationsControl` component.
 *
 * @typedef DisplayRulesLocationsControlProps
 * @param {GroupedSelectOptions} options - The options to display in the select controls.
 * @param {DisplayRulesLocation[]} items - The current location items.
 * @param {(items:DisplayRulesLocation[]) => void} onChange - Callback to for when any items have changed.
 * @param {string} [className] - Optional. The CSS class name to append to the component.
 */

/**
 *
 * @param {DisplayRulesLocationsControlProps} props - The props for the display rules locations control.
 * @return {JSX.Element}
 */
const DisplayRulesLocationsControl = ( {
                                     options,
                                     items,
                                     onChange,
                                     className,
                                 } ) => {

    if ( !isArray( items ) ) {
        items = [];
    }

    const createNewItem = () => ( { type: '', data: [] } );

    const isEntireSite = item => item.type === 'general:entire_site';
    const hasEntireSite = items.some( isEntireSite );

    /**
     *
     * @param {RepeaterControlItemRendererProps<DisplayRulesLocation>} props
     * @returns {JSX.Element}
     */
    const renderItem = ( { item, index, onChange, onRequestRemove } ) => <DisplayRulesLocationControl
        key={ index }
        options={ options }
        value={ item }
        onChange={ onChange }
        onRequestRemove={ onRequestRemove }
        disabled={ hasEntireSite && !isEntireSite( item ) }
    />;

    return (
        <RepeaterControl
            className={ classNames( rootClass, className ) }
            items={ items }
            itemRenderer={ renderItem }
            onChange={ onChange }
            onRequestNewItem={ createNewItem }
            noItemsLabel={ __( 'No location rules are set.', 'fooconvert' ) }
            addItemLabel={ __( 'Add rule', 'fooconvert' ) }
        />
    );
};

export default DisplayRulesLocationsControl;