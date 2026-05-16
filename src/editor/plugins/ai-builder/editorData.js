import { hasKeys, isPlainObject, isString } from '@steveush/utils';

const objectName = 'FC_AI_BUILDER';

/**
 * @typedef AiBuilderEditorData
 * @property {{ key: string, defaults: Record<string, unknown> }} meta       Metadata storage configuration.
 * @property {string}                                             builderUrl AI popup builder admin URL.
 * @property {Record<string,string>}                              labels     Popup type labels.
 */

/**
 * @type {AiBuilderEditorData}
 */
const rawEditorData = global?.[ objectName ];

const editorData =
	isPlainObject( rawEditorData ) &&
	hasKeys( rawEditorData?.meta, { key: isString, defaults: isPlainObject } )
		? rawEditorData
		: null;

export const hasAiBuilderEditorData = () => editorData !== null;

export default editorData;
