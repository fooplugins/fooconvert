import { hasKeys, isPlainObject, isString } from "@steveush/utils";

const objectName = 'FC_DISPLAY_RULES';
/**
 * @typedef DisplayRulesEditorData
 * @property {{ key: string, defaults: DisplayRulesMeta }} meta
 * @property {GroupedSelectOptions} location
 * @property {GroupedSelectOptions} exclude
 * @property {GroupedSelectOptions} users
 */

/**
 * @type {DisplayRulesEditorData}
 */
const editorData = global[ objectName ];
if ( !isPlainObject( editorData ) ) {
    throw new Error( `DISPLAY_RULES_ERROR: The global "${ objectName }" variable is not an object.` );
}
if ( !hasKeys( editorData?.meta, { key: isString, defaults: isPlainObject } ) ) {
    throw new Error( `DISPLAY_RULES_ERROR: The global "${ objectName }" object is missing the required "meta" object property.` );
}

export default editorData;