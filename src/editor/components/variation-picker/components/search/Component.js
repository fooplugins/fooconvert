import { __ } from "@wordpress/i18n";
import { useState } from "@wordpress/element";

import "./Component.scss";

const SearchInput = ( { value, onChange } ) => {
    const [ internalValue, setInternalValue ] = useState( value ?? '' );
    const onInternalChange = value => {
        setInternalValue( value );
        onChange( value );
    };
    return (
        <div className="fc-variation-picker__search">
            <input
                type="text"
                className="fc-variation-picker__search__input"
                placeholder={ __( "Search", "fooconvert" ) }
                value={ internalValue }
                onChange={ e => onInternalChange( e.target.value ) }
            />
        </div>
    );
};

export default SearchInput;