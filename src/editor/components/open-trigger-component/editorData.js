import { isArray, isPlainObject } from "@steveush/utils";

const objectName = "FC_OPEN_TRIGGER";

const editorData = global[ objectName ];

if ( !isPlainObject( editorData ) ) {
    throw new Error( `OPEN_TRIGGER_ERROR: The global "${ objectName }" variable is not an object.` );
}

if ( !isArray( editorData?.triggers ) ) {
    throw new Error( `OPEN_TRIGGER_ERROR: The global "${ objectName }" object is missing the required "triggers" array.` );
}

export default editorData;
