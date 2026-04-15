import $object from "./$object";
/**
 *
 * @param {string} name
 * @param {Record<string, Record<string, unknown>|undefined>} attributes
 * @param {(value: Record<string, Record<string, unknown>|undefined>) => void} setAttributes
 * @param {Record<string, Record<string, unknown>|undefined>} [attributesDefaults]
 * @returns {[Record<string, unknown>,((next: Record<string, unknown>|undefined)=>void),Record<string, unknown>]}
 */
const useObject = ( name, attributes, setAttributes, attributesDefaults = {} ) => {
    const _attributes = attributes[ name ] ?? {};
    const _setAttributes = value => setAttributes( { [ name ]: $object( _attributes, value ) } );
    const _attributesDefaults = { ...( attributesDefaults[ name ] ?? {} ) };
    return [ _attributes, _setAttributes, _attributesDefaults ];
};

/**
 *
 * @param {string} name
 * @param {Record<string, Record<string, unknown>|undefined>} attributes
 * @param {(value: Record<string, Record<string, unknown>|undefined>) => void} setAttributes
 * @param {Record<string, Record<string, unknown>|undefined>} [attributesDefaults]
 * @returns {{attributes: Record<string, unknown>, setAttributes: (value: Record<string, unknown>|undefined) => void, attributesDefaults: Record<string, unknown>, settings: Record<string, unknown>, setSettings: (value: Record<string, unknown>|undefined) => void, settingsDefaults: Record<string, unknown>, styles: Record<string, unknown>, setStyles: (value: Record<string, unknown>|undefined) => void, stylesDefaults: Record<string, unknown>}}
 */
const makePropsFromObjectAttribute = ( name, attributes, setAttributes, attributesDefaults = {} ) => {
    const [ _attributes, _setAttributes, _attributesDefaults ] = useObject( name, attributes, setAttributes, attributesDefaults );
    const [ settings, setSettings, settingsDefaults ] = useObject( 'settings', _attributes, _setAttributes, _attributesDefaults );
    const [ styles, setStyles, stylesDefaults ] = useObject( 'styles', _attributes, _setAttributes, _attributesDefaults );
    return {
        attributes: _attributes,
        setAttributes: _setAttributes,
        attributesDefaults: _attributesDefaults,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults
    };
};

export default makePropsFromObjectAttribute;
