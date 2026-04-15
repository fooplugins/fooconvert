import { hasKeys, isArray, isNumber, isString, isUndefined } from "@steveush/utils";

const DISPLAY_RULES_LOCATION_DATA_DEFINITION = {
    id: isNumber,
    label: isString
};

const isDisplayRulesLocationData = value => hasKeys( value, DISPLAY_RULES_LOCATION_DATA_DEFINITION );

const DISPLAY_RULES_LOCATION_DEFINITION = {
    type: isString,
    data: value => isUndefined( value ) || isArray( value, false, isDisplayRulesLocationData )
};

const isDisplayRulesLocation = value => {
    const valid = hasKeys( value, DISPLAY_RULES_LOCATION_DEFINITION );
    if ( !valid ) {
        console.debug( "FooConvert: Invalid display rules location", value );
    }
    return valid;
};

export default isDisplayRulesLocation;
