import { hasKeys, isPlainObject, isString } from "@steveush/utils";

const objectName = "FC_AI_BUILDER";

/**
 * @typedef AiBuilderEditorData
 * @property {{ key: string, defaults: Record<string, unknown> }} meta
 * @property {string} builderUrl
 * @property {Record<string,string>} labels
 */

/**
 * @type {AiBuilderEditorData}
 */
const editorData = global[ objectName ];

if ( !isPlainObject( editorData ) ) {
    throw new Error( `AI_BUILDER_ERROR: The global "${ objectName }" variable is not an object.` );
}

if ( !hasKeys( editorData?.meta, { key: isString, defaults: isPlainObject } ) ) {
    throw new Error( `AI_BUILDER_ERROR: The global "${ objectName }" object is missing the required "meta" object property.` );
}

export default editorData;
