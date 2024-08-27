import { __ } from "@wordpress/i18n";
import { DisplayRulesRoleControl } from "./role-control";
import classNames from "classnames";
import { isArray } from "@steveush/utils";

import "./Component.scss";
import { RepeaterControl } from "../../../repeater-control";

const rootClass = 'fc-display-rules-roles-control';

/**
 * The properties for the `DisplayRulesRolesControl` component.
 *
 * @typedef DisplayRulesRolesControlProps
 * @param {GroupedSelectOptions} options - The options to display in the select controls.
 * @param {string[]} items - The current role items.
 * @param {( items: string[] ) => void} onChange - Callback to for when any items have changed.
 * @param {string} [className] - Optional. The CSS class name to append to the component.
 */

/**
 *
 * @param {DisplayRulesRolesControlProps} props - The props for the display rules locations control.
 * @return {JSX.Element}
 * @constructor
 */
const DisplayRulesRolesControl = ( {
                                           options,
                                           items,
                                           onChange,
                                           className,
                                       } ) => {

    if ( !isArray( items ) ) {
        items = [];
    }

    const createNewItem = () => '';

    /**
     *
     * @param {RepeaterControlItemRendererProps<string>} props
     * @returns {JSX.Element}
     */
    const renderItem = ( { item, index, onChange, onRequestRemove } ) => <DisplayRulesRoleControl
        key={ index }
        options={ options }
        value={ item }
        onChange={ onChange }
        onRequestRemove={ onRequestRemove }
    />;

    return (
        <RepeaterControl
            className={ classNames( rootClass, className ) }
            items={ items }
            itemRenderer={ renderItem }
            onChange={ onChange }
            onRequestNewItem={ createNewItem }
            noItemsLabel={ __( 'No roles rules are set.', 'fooconvert' ) }
            addItemLabel={ __( 'Add rule', 'fooconvert' ) }
        />
    );
};

export default DisplayRulesRolesControl;