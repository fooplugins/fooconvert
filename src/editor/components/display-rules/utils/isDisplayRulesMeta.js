import { hasKeys, isArray, isString } from "@steveush/utils";
import isDisplayRulesLocation from "./isDisplayRulesLocation";

const isDisplayRulesLocationArray = ( value, notEmpty = false ) => isArray( value, notEmpty, isDisplayRulesLocation );
const isStringArray = ( value, notEmpty = false ) => isArray( value, notEmpty, item => isString( item ) );

const DISPLAY_RULES_META_DEFINITION = {
    location: isDisplayRulesLocationArray,
    exclude: isDisplayRulesLocationArray,
    users: isStringArray
};

const isDisplayRulesMeta = obj => hasKeys( obj, DISPLAY_RULES_META_DEFINITION );

export default isDisplayRulesMeta;