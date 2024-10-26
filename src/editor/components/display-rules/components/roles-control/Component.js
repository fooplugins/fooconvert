import { __ } from "@wordpress/i18n";
import { DisplayRulesRoleControl } from "./role-control";
import classNames from "classnames";
import { isArray } from "@steveush/utils";

import "./Component.scss";
import { RepeaterControl } from "../../../repeater-control";

const rootClass = 'fc--display-rules__roles-control';

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
                                       label,
                                       help,
                                       options,
                                       items,
                                       onChange,
                                       className,
                                       noItemsLabel,
                                       addItemLabel,
                                       removeItemLabel,
                                   } ) => {

    if ( !isArray( items ) ) {
        items = [];
    }

    const createNewItem = () => '';

    const isAllUsers = item => item.type === 'general:all_users';
    const hasAllUsers = items.some( isAllUsers );

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
        disabled={ hasAllUsers && !isAllUsers( item ) }
        removeItemLabel={ removeItemLabel }
    />;

    return (
        <RepeaterControl
            label={ label }
            help={ help }
            className={ classNames( rootClass, className ) }
            items={ items }
            itemRenderer={ renderItem }
            onChange={ onChange }
            onRequestNewItem={ createNewItem }
            noItemsLabel={ noItemsLabel }
            addItemLabel={ addItemLabel }
        />
    );
};

export default DisplayRulesRolesControl;