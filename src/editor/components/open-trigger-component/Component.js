import {
    __experimentalInputControl as InputControl,
    __experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
    BaseControl,
    Button,
    Dropdown,
    RangeControl,
    SelectControl,
    TextControl,
    ToggleControl
} from "@wordpress/components";
import { useEntityRecords } from "@wordpress/core-data";
import { __ } from "@wordpress/i18n";
import { isNumberWithin, isPlainObject, isString } from "@steveush/utils";
import { useEffect, useMemo, useState } from "@wordpress/element";
import { Icon, chevronDownSmall } from "@wordpress/icons";
import { EntityRecordControl } from "../entity-record-control";
import { createEntityRecordToken } from "../entity-record-control/utils";
import { GroupedSelectControl } from "../grouped-select-control";
import getGroupedSelectOption from "../grouped-select-control/utils/getGroupedSelectOption";
import { InspectorPopoverHeader } from "../experimental";

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

const isSelectableDefinition = ( definition, selectedEvent = "" ) => {
    if ( !isPlainObject( definition ) || !isString( definition?.event, true ) ) {
        return false;
    }

    if ( definition.multiStepOnly !== true ) {
        return true;
    }

    return definition.event === selectedEvent;
};

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
        case "entity-record-list":
            if ( Array.isArray( value ) ) {
                return value.reduce( ( ids, item ) => {
                    const numeric = Number( isPlainObject( item ) ? item?.id : item );
                    if ( Number.isInteger( numeric ) && numeric > 0 ) {
                        ids.push( numeric );
                    }
                    return ids;
                }, [] );
            }
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

const flattenField = field => {
    if ( !isPlainObject( field ) ) {
        return [];
    }

    if ( field.type === "section" ) {
        return flattenFields( field.fields );
    }

    if ( field.type === "rules" ) {
        return ( Array.isArray( field?.groups ) ? field.groups : [] ).reduce( ( result, group ) => {
            return [ ...result, ...flattenFields( group?.fields ) ];
        }, [] );
    }

    return [ field ];
};

const flattenFields = fields => {
    return ( Array.isArray( fields ) ? fields : [] ).reduce( ( result, field ) => {
        if ( !isPlainObject( field ) ) {
            return result;
        }
        return [ ...result, ...flattenField( field ) ];
    }, [] );
};

const sanitizeWhere = ( definition, where = {} ) => {
    const fields = flattenFields( definition?.fields );
    return fields.reduce( ( nextWhere, field ) => {
        if ( !isString( field?.path, true ) ) {
            return nextWhere;
        }
        return setNestedValue( nextWhere, field.path, sanitizeFieldValue( field, getNestedValue( where, field.path ) ) );
    }, {} );
};

const getDefaultFrequencyMode = definition => Boolean( definition?.defaultOnce ) ? "once" : "repeat";

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
    const frequencyMode = [ "once", "repeat" ].includes( value?.frequency?.mode )
        ? value.frequency.mode
        : getDefaultFrequencyMode( definition );
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

const getTriggerOptions = selectedEvent => {
    const definitions = getDefinitions()
        .filter( definition => isSelectableDefinition( definition, selectedEvent ) )
        .map( definition => ({
            ...definition,
            value: definition.event,
            group: isString( definition?.group, true ) ? definition.group : __( "Other", "fooconvert" )
        }) )
        .sort( ( left, right ) => {
            const groupSort = left.group.localeCompare( right.group );
            return groupSort !== 0 ? groupSort : left.label.localeCompare( right.label );
        } );

    const grouped = definitions.reduce( ( result, definition ) => {
        const existing = result.find( entry => entry.group === definition.group );
        const option = {
            label: definition.label,
            value: definition.value
        };

        if ( existing ) {
            existing.options.push( option );
            return result;
        }

        result.push( {
            group: definition.group,
            label: definition.group,
            options: [ option ]
        } );
        return result;
    }, [] );

    return grouped;
};

const renderField = ( definition, field, where, once, setTrigger ) => {
    const value = getNestedValue( where, field.path );
    const setValue = nextValue => setTrigger( definition.event, setNestedValue( where, field.path, nextValue ), once );
    const isLocked = definition?.locked === true;

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
                    disabled={ isLocked }
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
                    disabled={ isLocked }
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
                    disabled={ isLocked }
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
                    disabled={ isLocked }
                    __nextHasNoMarginBottom
                    __next40pxDefaultSize
                />
            );
        default:
            return null;
    }
};

const CompactNumberField = ( props ) => {
    const {
        definition,
        field,
        where,
        once,
        setTrigger,
        rowOperator = ""
    } = props;

    const committedValue = Number( getNestedValue( where, field.path ) ?? field?.default ?? 0 );
    const [ draftValue, setDraftValue ] = useState( `${ committedValue }` );

    useEffect( () => {
        setDraftValue( `${ committedValue }` );
    }, [ committedValue ] );

    const setValue = nextValue => setTrigger( definition.event, setNestedValue( where, field.path, nextValue ), once );

    const commitValue = () => {
        const sanitized = sanitizeFieldValue( field, draftValue );
        setDraftValue( `${ sanitized }` );
        setValue( sanitized );
    };

    return (
        <div className="fc--open-trigger-component__rule-row">
            <label className="fc--open-trigger-component__rule-label" htmlFor={ `fc-open-trigger-${ field.path }` }>
                { isString( rowOperator, true ) ? <span className="fc--open-trigger-component__rule-row-operator">{ rowOperator }</span> : null }
                <span className="fc--open-trigger-component__rule-label-text">{ field.label }</span>
            </label>
            <InputControl
                id={ `fc-open-trigger-${ field.path }` }
                className="fc--open-trigger-component__rule-input"
                label={ field.label }
                hideLabelFromVision
                size="compact"
                value={ draftValue }
                onChange={ nextValue => setDraftValue( `${ nextValue ?? "" }`.replace( /[^\d]/g, "" ) ) }
                onBlur={ commitValue }
                onKeyDown={ event => {
                    if ( event.key === "Enter" ) {
                        event.preventDefault();
                        commitValue();
                        event.currentTarget.blur();
                    }
                } }
                disabled={ definition?.locked === true }
                aria-describedby={ isString( field?.help, true ) ? `fc-open-trigger-${ field.path }-help` : undefined }
                suffix={ isString( field?.unit, true ) ? (
                    <InputControlSuffixWrapper variant="control">
                        { field.unit }
                    </InputControlSuffixWrapper>
                ) : null }
                __next40pxDefaultSize
            />
            { isString( field?.help, true ) ? (
                <p id={ `fc-open-trigger-${ field.path }-help` } className="fc--open-trigger-component__rule-help">
                    { field.help }
                </p>
            ) : null }
        </div>
    );
};

const EntityRecordListField = ( props ) => {
    const {
        definition,
        field,
        where,
        once,
        setTrigger
    } = props;

    const ids = parseIntList( getNestedValue( where, field.path ) );
    const queryArgs = useMemo( () => {
        if ( ids.length === 0 ) {
            return {};
        }
        return {
            ...( isPlainObject( field?.queryArgs ) ? field.queryArgs : {} ),
            include: ids,
            per_page: ids.length
        };
    }, [ field?.queryArgs, ids ] );

    const query = useEntityRecords(
        field?.kind ?? "",
        field?.name ?? "",
        queryArgs,
        { enabled: ids.length > 0 }
    );

    const tokens = useMemo( () => {
        const records = Array.isArray( query?.records ) ? query.records : [];
        const tokenMap = records.reduce( ( result, record ) => {
            const token = createEntityRecordToken( field?.kind ?? "", field?.name ?? "", record );
            if ( token ) {
                result.set( token.id, token );
            }
            return result;
        }, new Map() );

        return ids.map( id => tokenMap.get( id ) ?? { id, label: `#${ id }` } );
    }, [ field?.kind, field?.name, ids, query?.records ] );

    const setValue = nextTokens => {
        setTrigger(
            definition.event,
            setNestedValue(
                where,
                field.path,
                sanitizeFieldValue( field, nextTokens )
            ),
            once
        );
    };

    return (
        <BaseControl
            key={ field.path }
            label={ field.label }
            help={ field.help }
            __nextHasNoMarginBottom
        >
            <EntityRecordControl
                kind={ field?.kind ?? "" }
                name={ field?.name ?? "" }
                queryArgs={ isPlainObject( field?.queryArgs ) ? field.queryArgs : {} }
                tokens={ tokens }
                onChange={ setValue }
                placeholder={ field?.placeholder ?? "" }
                __next40pxDefaultSize
            />
        </BaseControl>
    );
};

const renderRulesField = ( definition, field, where, once, setTrigger, key ) => {
    const groups = Array.isArray( field?.groups ) ? field.groups : [];
    const renderedGroups = groups.reduce( ( result, group, groupIndex ) => {
        const groupFields = Array.isArray( group?.fields ) ? group.fields : [];
        const rows = groupFields.map( ( groupField, rowIndex ) => {
            if ( groupField?.appearance === "rule-number" ) {
                return (
                    <CompactNumberField
                        key={ `${ key }-group-${ groupIndex }-row-${ rowIndex }` }
                        definition={ definition }
                        field={ groupField }
                        where={ where }
                        once={ once }
                        setTrigger={ setTrigger }
                        rowOperator={ rowIndex > 0 ? group?.operatorBetween : "" }
                    />
                );
            }

            return renderField( definition, groupField, where, once, setTrigger );
        } ).filter( Boolean );

        if ( rows.length === 0 ) {
            return result;
        }

        if ( groupIndex > 0 && isString( group?.groupOperator, true ) ) {
            result.push(
                <div key={ `${ key }-group-operator-${ groupIndex }` } className="fc--open-trigger-component__rules-group-operator">
                    <span>{ group.groupOperator }</span>
                </div>
            );
        }

        result.push(
            <div key={ `${ key }-group-${ groupIndex }` } className="fc--open-trigger-component__rule-group">
                { rows }
            </div>
        );

        return result;
    }, [] );

    if ( renderedGroups.length === 0 ) {
        return null;
    }

    return (
        <div key={ key } className="fc--open-trigger-component__rules-field">
            { isString( field?.label, true ) ? <p className="fc--open-trigger-component__rules-label">{ field.label }</p> : null }
            { isString( field?.help, true ) ? <p className="fc--open-trigger-component__rules-help">{ field.help }</p> : null }
            <div className="fc--open-trigger-component__rules">{ renderedGroups }</div>
        </div>
    );
};

const renderDefinitionField = ( definition, field, where, once, setTrigger, key ) => {
    if ( field?.type === "section" ) {
        const childFields = Array.isArray( field?.fields ) ? field.fields : [];
        const children = childFields
            .map( ( childField, index ) => renderDefinitionField( definition, childField, where, once, setTrigger, `${ key }-${ index }` ) )
            .filter( Boolean );

        if ( children.length === 0 ) {
            return null;
        }

        return (
            <div
                key={ key }
                className={ `fc--open-trigger-component__section${ isString( field?.layout, true ) ? ` is-${ field.layout }` : "" }` }
            >
                { isString( field?.label, true ) ? <p className="fc--open-trigger-component__section-label">{ field.label }</p> : null }
                { isString( field?.help, true ) ? <p className="fc--open-trigger-component__section-help">{ field.help }</p> : null }
                { children }
            </div>
        );
    }

    if ( field?.type === "rules" ) {
        return renderRulesField( definition, field, where, once, setTrigger, key );
    }

    if ( field?.type === "entity-record-list" ) {
        return (
            <EntityRecordListField
                key={ key }
                definition={ definition }
                field={ field }
                where={ where }
                once={ once }
                setTrigger={ setTrigger }
            />
        );
    }

    if ( field?.appearance === "rule-number" ) {
        return (
            <CompactNumberField
                key={ key }
                definition={ definition }
                field={ field }
                where={ where }
                once={ once }
                setTrigger={ setTrigger }
            />
        );
    }

    return renderField( definition, field, where, once, setTrigger, key );
};

const OpenTriggerContent = ( props ) => {
    const {
        options,
        selected,
        selectedDefinition,
        where,
        once,
        setTrigger,
        allowEmpty
    } = props;

    const renderData = () => {
        if ( !selectedDefinition || !Array.isArray( selectedDefinition?.fields ) || selectedDefinition.fields.length === 0 ) {
            return null;
        }

        const content = selectedDefinition.fields
            .map( ( field, index ) => renderDefinitionField( selectedDefinition, field, where, once, setTrigger, `field-${ index }` ) )
            .filter( Boolean );

        if ( content.length === 0 ) {
            return null;
        }

        return <div className="fc--open-trigger-component__fields">{ content }</div>;
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
        <div
            className="fc--open-trigger-component__content"
            style={ {
                "--fc-open-trigger-content-width": `${ Number( selectedDefinition?.popoverWidth ?? 320 ) }px`
            } }
        >
            <GroupedSelectControl
                label={ __( "Trigger", "fooconvert" ) }
                help={ selectedDefinition?.help }
                value={ selected?.value ?? "" }
                options={ options }
                placeholder={ allowEmpty ? EMPTY_TRIGGER.label : null }
                onChange={ nextValue => setTrigger( nextValue, {}, undefined ) }
                __nextHasNoMarginBottom
            />
            { renderData() }
            { renderOnce() }
        </div>
    );
};

const OpenTriggerComponent = props => {
    const {
        value,
        onChange,
        locked = false,
        allowEmpty = false,
        label = __( "Open Trigger", "fooconvert" ),
        help = __( "Select how this popup is opened.", "fooconvert" ),
        showDivider = false,
        hideLabelFromVision
    } = props;

    const normalized = normalizeTriggerValue( value );
    const step = normalized?.steps?.at( 0 );
    const selectedEvent = step?.event ?? "";
    const where = step?.where ?? {};
    const once = normalized?.frequency?.mode === "once";

    const options = getTriggerOptions( selectedEvent );
    const selected = getGroupedSelectOption( options, selectedEvent ) ?? ( allowEmpty ? EMPTY_TRIGGER : getGroupedSelectOption( options, options?.[0]?.options?.[0]?.value ) );
    const selectedDefinition = getDefinitionByEvent( selected?.value );
    const selectedLabel = selected?.label ?? EMPTY_TRIGGER.label;

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

    if ( locked ) {
        return (
            <BaseControl
                label={ label }
                help={ help }
                hideLabelFromVision={ hideLabelFromVision }
                __nextHasNoMarginBottom
            >
                <p>{ selectedLabel }</p>
            </BaseControl>
        );
    }

    return (
        <BaseControl
            className={ `fc--open-trigger-component${ showDivider ? " has-divider" : "" }` }
            label={ label }
            help={ help }
            hideLabelFromVision={ hideLabelFromVision }
            __nextHasNoMarginBottom
        >
            <div className="fc--open-trigger-component__control">
                <Dropdown
                    className="fc--open-trigger-component__dropdown"
                    contentClassName="fc--open-trigger-component__dropdown-content"
                    popoverProps={ {
                        placement: "left-start",
                        offset: 40
                    } }
                    renderToggle={ ( { isOpen, onToggle } ) => (
                        <Button
                            className="fc--open-trigger-component__toggle"
                            onClick={ onToggle }
                            aria-expanded={ isOpen }
                        >
                            <span className="fc--open-trigger-component__toggle-label">{ selectedLabel }</span>
                            <Icon icon={ chevronDownSmall } />
                        </Button>
                    ) }
                    renderContent={ ( { onClose } ) => (
                        <>
                            <InspectorPopoverHeader
                                title={ __( "Open Trigger", "fooconvert" ) }
                                onClose={ onClose }
                            />
                            <OpenTriggerContent
                                options={ options }
                                selected={ selected }
                                selectedDefinition={ selectedDefinition }
                                where={ where }
                                once={ once }
                                setTrigger={ setTrigger }
                                allowEmpty={ allowEmpty }
                            />
                        </>
                    ) }
                />
            </div>
        </BaseControl>
    );
};

export default OpenTriggerComponent;
