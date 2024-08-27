import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { create } from "@wordpress/icons";
import classNames from "classnames";
import { isArray } from "@steveush/utils";

import "./Component.scss";

/**
 * @template T
 * @typedef RepeaterControlItemRendererProps
 * @property {T} item - The item to render.
 * @property {number} index - The items index within the 'items' array.
 * @property {T[]} items - The original 'items' array passed to the component.
 * @property {()=>void} onRequestRemove - Callback to remove the item from the repeater.
 * @property {( value: T )=>void} onChange - Callback to notify the repeater an item has changed.
 */

/**
 * @template T
 * @callback RepeaterControlItemRenderer
 * @param {RepeaterControlItemRendererProps<T>} props - The props for the item to render.
 * @returns {JSX.Element} The rendered item.
 */

const rootClass = 'fc-repeater-control';

/**
 * @template T
 * @typedef RepeaterControlProps
 * @property {T[]} items
 * @property {RepeaterControlItemRenderer<T>} itemRenderer
 * @property {string} [className]
 * @property {( items: T[] ) => void} onChange
 * @property {() => T} onRequestNewItem
 * @property {string} [noItemsLabel]
 * @property {string} [addItemLabel]
 */

/**
 *
 * @template T
 * @param {RepeaterControlProps<T>} props - The props for the repeater.
 * @return {JSX.Element}
 */
const RepeaterControl = ( {
                              items,
                              itemRenderer,
                              onChange,
                              onRequestNewItem,
                              className,
                              noItemsLabel = __( 'No items.', 'fooconvert' ),
                              addItemLabel = __( 'Add item', 'fooconvert' )
                          } ) => {

    if ( !isArray( items ) ) {
        items = [];
    }

    const itemsChanged = () => {
        onChange( [ ...items ] );
    };

    const addNewItem = () => {
        items.push( onRequestNewItem() );
        itemsChanged();
    };

    const onNotifyChange = ( index, item ) => {
        items[ index ] = item;
        itemsChanged();
    };

    const onRequestRemove = index => {
        items.splice( index, 1 );
        itemsChanged();
    };

    const isEmpty = items.length === 0;

    const renderItems = () => {
        if ( isEmpty ) {
            return ( <label className={ `${ rootClass }__empty` }>{ noItemsLabel }</label> );
        }
        return (
            <div className={ `${ rootClass }__items` }>
                { items.map( ( item, index ) => {
                    return itemRenderer( {
                        item,
                        index,
                        items,
                        onChange: value => onNotifyChange( index, value ),
                        onRequestRemove: () => onRequestRemove( index )
                    } );
                } ) }
            </div>
        );
    };

    return (
        <div className={ classNames( rootClass, className, { 'is-empty': isEmpty } ) }>
            { renderItems() }
            <div className={ `${ rootClass }__buttons` }>
                <Button
                    icon={ create }
                    size="compact"
                    variant="tertiary"
                    onClick={ addNewItem }
                >
                    { addItemLabel }
                </Button>
            </div>
        </div>
    );
};

export default RepeaterControl;