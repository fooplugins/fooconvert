import { hasKeys, isPlainObject, isString } from "@steveush/utils";

/**
 * @typedef {import("./types").CompatibilityEditorData} CompatibilityEditorData
 */

const objectName = 'FC_COMPATIBILITY';

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
