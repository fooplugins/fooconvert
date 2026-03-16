import { BaseControl, RangeControl, SelectControl, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { isNumberWithin, isPlainObject, isString } from "@steveush/utils";
import { Fragment } from "@wordpress/element";

import editorData from "./editorData";
import "./Component.scss";

const parseStringList = value => {
    if ( Array.isArray( value ) ) {
        return value.map( item => `${ item }`.trim() ).filter( Boolean );
    }
    if ( isString( value ) ) {
        return value.split( "," ).map( item => item.trim() ).filter( Boolean );
    }
    return [];
};

const parseIntList = value => {
    return parseStringList( value )
        .map( item => Number( item ) )
        .filter( number => Number.isInteger( number ) && number > 0 );
};

const getDefinitions = () => Array.isArray( editorData?.triggers ) ? editorData.triggers : [];

const getDefinitionByEvent = event => getDefinitions().find( definition => definition?.event === event ) ?? null;

const getDefinitionByLegacyType = legacyType => getDefinitions().find( definition => definition?.legacyType === legacyType ) ?? null;

const getNestedValue = ( object, path ) => {
    const segments = `${ path ?? "" }`.split( "." ).filter( Boolean );
    let value = object;
    for ( const segment of segments ) {
        if ( !isPlainObject( value ) || !( segment in value ) ) {
            return undefined;
        }
        value = value[ segment ];
    }
    return value;
};

const setNestedValue = ( object, path, value ) => {
    const segments = `${ path ?? "" }`.split( "." ).filter( Boolean );
    if ( segments.length === 0 ) {
        return isPlainObject( object ) ? object : {};
    }
    const result = isPlainObject( object ) ? { ...object } : {};
    let pointer = result;
    segments.forEach( ( segment, index ) => {
        if ( index === segments.length - 1 ) {
            pointer[ segment ] = value;
            return;
        }
        const next = pointer[ segment ];
        pointer[ segment ] = isPlainObject( next ) ? { ...next } : {};
        pointer = pointer[ segment ];
    } );
    return result;
};

const sanitizeFieldValue = ( field, value ) => {
    const fieldType = field?.type;
    switch ( fieldType ) {
        case "string-list":
            return parseStringList( value );
        case "int-list":
            return parseIntList( value );
        case "text":
            return isString( value ) ? value : `${ field?.default ?? "" }`;
        case "select": {
            const options = Array.isArray( field?.options ) ? field.options : [];
            const normalized = `${ value ?? "" }`;
            return options.some( option => option?.value === normalized ) ? normalized : `${ field?.default ?? "" }`;
        }
        case "range": {
            const min = Number( field?.min );
            const max = Number( field?.max );
            const fallback = Number( field?.default ?? 0 );
            const numeric = Number( value );
            if ( Number.isFinite( numeric ) && ( !Number.isFinite( min ) || numeric >= min ) && ( !Number.isFinite( max ) || numeric <= max ) ) {
                return numeric;
            }
            return Number.isFinite( fallback ) ? fallback : 0;
        }
        default:
            return field?.default;
    }
};

const sanitizeWhere = ( definition, where = {} ) => {
    const fields = Array.isArray( definition?.fields ) ? definition.fields : [];
    return fields.reduce( ( nextWhere, field ) => {
        if ( !isString( field?.path, true ) ) {
            return nextWhere;
        }
        return setNestedValue( nextWhere, field.path, sanitizeFieldValue( field, getNestedValue( where, field.path ) ) );
    }, {} );
};

const createTriggerConfig = ( definition, where = {}, once, lifetime = "page" ) => {
    if ( !isPlainObject( definition ) || !isString( definition?.event, true ) ) {
        return {};
    }
    const defaultOnce = Boolean( definition?.defaultOnce );
    return {
        version: 2,
        lifetime: [ "page", "session", "visit" ].includes( lifetime ) ? lifetime : "page",
        frequency: {
            mode: ( typeof once === "boolean" ? once : defaultOnce ) ? "once" : "repeat",
            cooldownSeconds: 0
        },
        steps: [ {
            event: definition.event,
            where: sanitizeWhere( definition, where )
        } ]
    };
};

const normalizeLegacy = value => {
    const definition = getDefinitionByLegacyType( value?.type );
    if ( !definition ) {
        return {};
    }

    let where = {};
    switch ( value?.type ) {
        case "immediate":
            where = {};
            break;
        case "anchor":
        case "visible":
            where = { ids: parseStringList( value?.data ) };
            break;
        case "element":
            where = { selector: isString( value?.data ) ? value.data : "" };
            break;
        case "scroll":
            where = { percent: isNumberWithin( value?.data, 1, 100 ) ? value.data : undefined };
            break;
        case "timer":
            where = { seconds: isNumberWithin( value?.data, 0, 100 ) ? value.data : undefined };
            break;
        case "exit-intent":
            where = { delaySeconds: isNumberWithin( value?.data, 0, 100 ) ? value.data : undefined };
            break;
        default:
            where = {};
    }

    const hasOnce = Object.prototype.hasOwnProperty.call( value ?? {}, "once" );
    return createTriggerConfig( definition, where, hasOnce ? Boolean( value?.once ) : undefined );
};

const normalizeV2 = value => {
    if ( !isPlainObject( value ) ) {
        return {};
    }

    const version = Number( value?.version );
    const step = Array.isArray( value?.steps ) && isPlainObject( value.steps.at( 0 ) ) ? value.steps.at( 0 ) : null;
    const definition = getDefinitionByEvent( step?.event );

    if ( version !== 2 || !definition ) {
        return {};
    }

    const lifetime = [ "page", "session", "visit" ].includes( value?.lifetime ) ? value.lifetime : "page";
    const frequencyMode = [ "once", "repeat" ].includes( value?.frequency?.mode ) ? value.frequency.mode : "repeat";
    const cooldownSeconds = Number( value?.frequency?.cooldownSeconds );

    return {
        version: 2,
        lifetime,
        frequency: {
            mode: frequencyMode,
            cooldownSeconds: Number.isFinite( cooldownSeconds ) && cooldownSeconds >= 0 ? cooldownSeconds : 0
        },
        steps: [ {
            event: definition.event,
            where: sanitizeWhere( definition, isPlainObject( step?.where ) ? step.where : {} )
        } ]
    };
};

const normalizeTriggerValue = value => {
    if ( isPlainObject( value ) && Number( value?.version ) === 2 ) {
        return normalizeV2( value );
    }
    if ( isPlainObject( value ) && isString( value?.type, true ) ) {
        return normalizeLegacy( value );
    }
    return {};
};

const EMPTY_TRIGGER = {
    value: "",
    label: __( "None", "fooconvert" )
};

const getTriggerOptions = allowEmpty => {
    const options = getDefinitions().map( definition => ({
        ...definition,
        value: definition.event
    }) );
    return allowEmpty ? [ EMPTY_TRIGGER, ...options ] : options;
};

const renderField = ( definition, field, where, once, setTrigger ) => {
    const value = getNestedValue( where, field.path );
    const setValue = nextValue => setTrigger( definition.event, setNestedValue( where, field.path, nextValue ), once );

    switch ( field?.type ) {
        case "string-list":
        case "int-list":
            return (
                <TextControl
                    key={ field.path }
                    label={ field.label }
                    help={ field.help }
                    value={ Array.isArray( value ) ? value.join( "," ) : "" }
                    onChange={ nextValue => setValue( field.type === "int-list" ? parseIntList( nextValue ) : parseStringList( nextValue ) ) }
                    __nextHasNoMarginBottom
                />
            );
        case "text":
            return (
                <TextControl
                    key={ field.path }
                    label={ field.label }
                    help={ field.help }
                    value={ value ?? "" }
                    onChange={ setValue }
                    __nextHasNoMarginBottom
                />
            );
        case "select":
            return (
                <SelectControl
                    key={ field.path }
                    label={ field.label }
                    help={ field.help }
                    value={ `${ value ?? field?.default ?? "" }` }
                    options={ Array.isArray( field?.options ) ? field.options : [] }
                    onChange={ setValue }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            );
        case "range":
            return (
                <RangeControl
                    key={ field.path }
                    label={ field.label }
                    help={ field.help }
                    value={ Number( value ?? field?.default ?? 0 ) }
                    min={ Number( field?.min ?? 0 ) }
                    max={ Number( field?.max ?? 100 ) }
                    onChange={ setValue }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            );
        default:
            return null;
    }
};

const OpenTriggerComponent = props => {
    const {
        value,
        onChange,
        locked = false,
        allowEmpty = false,
        label = __( "Open Trigger", "fooconvert" ),
        hideLabelFromVision
    } = props;

    const normalized = normalizeTriggerValue( value );
    const step = normalized?.steps?.at( 0 );
    const selectedEvent = step?.event ?? "";
    const where = step?.where ?? {};
    const once = normalized?.frequency?.mode === "once";

    const options = getTriggerOptions( allowEmpty );
    const selected = options.find( option => option.value === selectedEvent ) ?? options.at( 0 );
    const selectedDefinition = getDefinitionByEvent( selected?.value );

    const setTrigger = ( nextEvent, nextWhere = where, nextOnce = once ) => {
        if ( !isString( nextEvent, true ) ) {
            onChange( {} );
            return;
        }

        const definition = getDefinitionByEvent( nextEvent );
        if ( !definition ) {
            onChange( {} );
            return;
        }

        onChange( createTriggerConfig( definition, nextWhere, nextOnce, normalized?.lifetime ?? "page" ) );
    };

    const renderType = () => {
        if ( locked ) {
            return (
                <BaseControl
                    label={ label }
                    help={ selected?.help }
                    hideLabelFromVision={ hideLabelFromVision }
                    __nextHasNoMarginBottom
                >
                    <p>{ selected?.label }</p>
                </BaseControl>
            );
        }

        return (
            <SelectControl
                label={ label }
                hideLabelFromVision={ hideLabelFromVision }
                help={ selected?.help }
                value={ selected?.value ?? "" }
                options={ options.map( option => ({ label: option.label, value: option.value }) ) }
                onChange={ nextValue => setTrigger( nextValue, {}, undefined ) }
                __nextHasNoMarginBottom
                __next40pxDefaultSize
            />
        );
    };

    const renderData = () => {
        if ( !selectedDefinition || !Array.isArray( selectedDefinition?.fields ) || selectedDefinition.fields.length === 0 ) {
            return null;
        }

        const controls = selectedDefinition.fields
            .map( field => renderField( selectedDefinition, field, where, once, setTrigger ) )
            .filter( Boolean );

        if ( controls.length === 0 ) {
            return null;
        }

        return <Fragment>{ controls }</Fragment>;
    };

    const renderOnce = () => {
        if ( !selectedDefinition?.supportsOnce ) {
            return null;
        }

        return (
            <ToggleControl
                label={ __( "Only show once", "fooconvert" ) }
                help={ __( "Once closed will not be shown to a user again.", "fooconvert" ) }
                checked={ once ?? false }
                onChange={ nextValue => setTrigger( selectedDefinition.event, where, nextValue ) }
                __nextHasNoMarginBottom
            />
        );
    };

    return (
        <div className="fc--open-trigger-component">
            { renderType() }
            { renderData() }
            { renderOnce() }
        </div>
    );
};

export default OpenTriggerComponent;
