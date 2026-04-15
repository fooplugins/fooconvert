import { hasKeys, isArray, isString } from "@steveush/utils";
import isDisplayRulesLocation from "./isDisplayRulesLocation";

const isDisplayRulesLocationArray = ( value, notEmpty = false ) => isArray( value, notEmpty, isDisplayRulesLocation );
const isStringArray = ( value, notEmpty = false ) => isArray( value, notEmpty, item => isString( item ) );

const DISPLAY_RULES_META_DEFINITION = {
    location: isDisplayRulesLocationArray,
    exclude: isDisplayRulesLocationArray,
    users: isStringArray
};

const isDisplayRulesMeta = obj => {
    const valid = hasKeys( obj, DISPLAY_RULES_META_DEFINITION );
    if ( !valid ) {
        console.debug( "FooConvert: Invalid display rules meta", obj );
    }
    return valid;
};

export default isDisplayRulesMeta;
