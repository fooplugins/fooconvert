import { hasKeys, isPlainObject, isString } from "@steveush/utils";

const objectName = 'FC_COMPATIBILITY';
/**
 * @typedef CompatibilityEditorData
 * @property {{ key: string, defaults: DisplayRulesMeta }} meta
 */

/**
 * @type {CompatibilityEditorData}
 */
const editorData = global[ objectName ];
if ( !isPlainObject( editorData ) ) {
    throw new Error( `COMPATIBILITY_ERROR: The global "${ objectName }" variable is not an object.` );
}
if ( !hasKeys( editorData?.meta, { key: isString, defaults: isPlainObject } ) ) {
    throw new Error( `COMPATIBILITY_ERROR: The global "${ objectName }" object is missing the required "meta" object property.` );
}

export default editorData;