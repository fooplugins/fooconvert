import $object from "./$object";
/**
 *
 * @param {string} name
 * @param {object} attributes
 * @param {(value:?object)=>void} setAttributes
 * @param {object} [attributesDefaults]
 * @returns {[object,((next:?object)=>void),object]}
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
 * @param {object} attributes
 * @param {(value:?object)=>void} setAttributes
 * @param {object} [attributesDefaults]
 * @returns {{attributes: Object, setAttributes: (function(?Object): void), attributesDefaults: Object, settings: Object, setSettings: (function(?Object): void), settingsDefaults: Object, styles: Object, setStyles: (function(?Object): void), stylesDefaults: Object}}
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