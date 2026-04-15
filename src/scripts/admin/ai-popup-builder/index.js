import apiFetch from "@wordpress/api-fetch";
import domReady from "@wordpress/dom-ready";
import {
    Button,
    Card,
    CardBody,
    CardHeader,
    CheckboxControl,
    Flex,
    FlexBlock,
    Modal,
    Notice,
    SelectControl,
    Spinner,
    TabPanel,
    TextControl,
    TextareaControl,
} from "@wordpress/components";
import { createRoot, Fragment, startTransition, useEffect, useMemo, useRef, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { copySmall, external } from "@wordpress/icons";
import { applyMediaItemToDraft, removeMediaItemFromDraft } from "./media-support";
import { cloneDeep, isPlainObject } from "./serializer-support";
import { flattenBlocks, normalizePopupType, serializeDraftToMarkup } from "./serializer";

import "./index.scss";

const rootClass = "fc-ai-popup-builder";
const config = window.FC_AI_POPUP_BUILDER || {};

if ( !window.FC_AI_POPUP_BUILDER_API_FETCH_READY ) {
    if ( typeof config?.restRoot === "string" && config.restRoot.length > 0 ) {
        apiFetch.use( apiFetch.createRootURLMiddleware( config.restRoot ) );
    }

    if ( typeof config?.restNonce === "string" && config.restNonce.length > 0 ) {
        apiFetch.use( apiFetch.createNonceMiddleware( config.restNonce ) );
    }

    window.FC_AI_POPUP_BUILDER_API_FETCH_READY = true;
}

const templatesBySlug = Array.isArray( config?.templates )
    ? config.templates.reduce( ( nextTemplates, template ) => {
        if ( isPlainObject( template ) && typeof template?.slug === "string" && template.slug.length > 0 ) {
            nextTemplates[ template.slug ] = template;
        }

        return nextTemplates;
    }, {} )
    : {};

const pendingActivitySteps = [
    {
        type: "status",
        label: __( "Preparing popup context", "fooconvert" ),
        summary: __( "Packing the current brand, draft, media, and conversation into the request.", "fooconvert" ),
    },
    {
        type: "status",
        label: __( "Calling AI model", "fooconvert" ),
        summary: __( "The model may request template, block, validation, or media abilities before it answers.", "fooconvert" ),
    },
    {
        type: "status",
        label: __( "Building popup draft", "fooconvert" ),
        summary: __( "Waiting for the popup response and any tool-call results to complete.", "fooconvert" ),
    },
];

const tabDefinitions = [
    {
        name: "context",
        title: __( "Context", "fooconvert" ),
    },
    {
        name: "chat",
        title: __( "Chat", "fooconvert" ),
    },
    {
        name: "details",
        title: __( "Popup Details", "fooconvert" ),
    },
    {
        name: "media",
        title: __( "Media", "fooconvert" ),
    },
];

const createEmptyBrand = () => ( {
    brandOverview: "",
    colorScheme: "light",
    colors: {
        primary: "",
        secondary: "",
        accent: "",
        background: "",
        textPrimary: "",
        textSecondary: "",
    },
    typography: {
        fontFamilies: {
            primary: "",
            heading: "",
        },
        fontSizes: {
            h1: {
                value: "",
                min: "",
                max: "",
            },
            h2: {
                value: "",
                min: "",
                max: "",
            },
            h3: {
                value: "",
                min: "",
                max: "",
            },
            body: {
                value: "",
                min: "",
                max: "",
            },
        },
        fontWeights: {
            regular: 400,
            medium: 500,
            bold: 700,
        },
    },
    spacing: {
        baseUnit: "",
        borderRadius: "",
    },
    components: {
        buttonPrimary: {
            background: "",
            textColor: "",
            borderRadius: "",
        },
        buttonSecondary: {
            background: "",
            textColor: "",
            borderColor: "",
            borderRadius: "",
        },
    },
} );

const deepMerge = ( base, overrides ) => {
    if ( Array.isArray( overrides ) ) {
        return overrides.map( cloneDeep );
    }

    if ( !isPlainObject( base ) || !isPlainObject( overrides ) ) {
        return cloneDeep( overrides );
    }

    const merged = { ...cloneDeep( base ) };

    Object.entries( overrides ).forEach( ( [ key, value ] ) => {
        if ( isPlainObject( value ) && isPlainObject( merged[ key ] ) ) {
            merged[ key ] = deepMerge( merged[ key ], value );
            return;
        }

        merged[ key ] = cloneDeep( value );
    } );

    return merged;
};

const normalizeBrand = ( brand ) => {
    const defaultBrand = isPlainObject( config?.brand?.defaultBrand ) ? config.brand.defaultBrand : {};
    let nextBrand = deepMerge( createEmptyBrand(), defaultBrand );

    if ( isPlainObject( brand ) ) {
        nextBrand = deepMerge( nextBrand, brand );
    }

    return nextBrand;
};

const serializeComparable = ( value ) => JSON.stringify( normalizeBrand( value ) );

const setNestedValue = ( source, path, value ) => {
    const nextValue = cloneDeep( source );
    const keys = String( path || "" )
        .split( "." )
        .map( segment => segment.trim() )
        .filter( Boolean );

    if ( keys.length === 0 ) {
        return nextValue;
    }

    let current = nextValue;

    keys.forEach( ( key, index ) => {
        if ( index === keys.length - 1 ) {
            current[ key ] = value;
            return;
        }

        if ( !isPlainObject( current[ key ] ) ) {
            current[ key ] = {};
        }

        current = current[ key ];
    } );

    return nextValue;
};

const countByName = ( blocks, blockName ) => blocks.filter( block => block?.name === blockName ).length;

const getActionSummary = ( draft ) => {
    const blocks = flattenBlocks( Array.isArray( draft?.content_blocks ) ? draft.content_blocks : [] );
    const buttonCount = countByName( blocks, "core/button" );
    const signupCount = countByName( blocks, "fc/sign-up" );

    if ( signupCount > 0 ) {
        return __( "Lead capture form included", "fooconvert" );
    }

    if ( buttonCount > 0 ) {
        return __( "Primary CTA button included", "fooconvert" );
    }

    return __( "No CTA yet", "fooconvert" );
};

const getTriggerSummary = ( draft ) => {
    const trigger = isPlainObject( draft?.trigger ) ? draft.trigger : {};

    switch ( trigger?.type ) {
        case "delay":
            return sprintf(
                __( "%ds delay", "fooconvert" ),
                Number.isFinite( Number( trigger?.delay_seconds ) ) ? Number( trigger.delay_seconds ) : 4
            );
        case "scroll_percent":
            return sprintf(
                __( "%d%% scroll", "fooconvert" ),
                Number.isFinite( Number( trigger?.scroll_percent ) ) ? Number( trigger.scroll_percent ) : 20
            );
        case "immediate":
            return __( "Immediate", "fooconvert" );
        case "exit_intent":
        default:
            return __( "Exit intent", "fooconvert" );
    }
};

const truncateText = ( value, maxLength = 160 ) => {
    const text = String( value || "" ).trim();

    if ( text.length <= maxLength ) {
        return text;
    }

    return `${ text.slice( 0, maxLength - 1 ).trim() }…`;
};

const getPreviewValue = ( value, fallback = __( "Not set", "fooconvert" ) ) => {
    const text = String( value || "" ).trim();
    return text.length > 0 ? text : fallback;
};

const getColorSchemeLabel = ( value ) => (
    value === "dark"
        ? __( "Dark", "fooconvert" )
        : __( "Light", "fooconvert" )
);

const getButtonPreviewStyle = ( button, fallbackBorderColor ) => {
    const borderColor = button?.borderColor || button?.background || fallbackBorderColor || "#1d2327";

    return {
        background: button?.background || "transparent",
        color: button?.textColor || "#1d2327",
        borderRadius: button?.borderRadius || "999px",
        border: `1px solid ${ borderColor }`,
    };
};

const createBrandSectionState = () => ( {
    overview: false,
    palette: false,
    typography: false,
    controls: false,
} );

const buildLastAssistantMessage = ( messages ) => {
    const lastAssistantEntry = [ ...( Array.isArray( messages ) ? messages : [] ) ]
        .reverse()
        .find( message => message?.role === "assistant" );

    return lastAssistantEntry?.content || "";
};

const buildPreviewUrl = ( previewUrl ) => {
    if ( typeof previewUrl !== "string" || previewUrl.length === 0 ) {
        return "";
    }

    try {
        const nextUrl = new URL( previewUrl, window.location.origin );
        nextUrl.searchParams.set( "fc_embed", "1" );
        return nextUrl.toString();
    } catch ( exception ) {
        return previewUrl;
    }
};

const PromptChip = ( { label, onClick, disabled } ) => (
    <button type="button" className={ `${ rootClass }__prompt-chip` } onClick={ onClick } disabled={ disabled }>
        { label }
    </button>
);

const MessageBubble = ( { role, content } ) => (
    <div className={ `${ rootClass }__message ${ rootClass }__message--${ role }` }>
        <div className={ `${ rootClass }__message-label` }>
            { role === "assistant" ? __( "AI strategist", "fooconvert" ) : __( "You", "fooconvert" ) }
        </div>
        <div className={ `${ rootClass }__message-body` }>
            { content }
        </div>
    </div>
);

const ConversionChecklist = ( { validation } ) => {
    if ( !isPlainObject( validation ) ) {
        return null;
    }

    const strengths = Array.isArray( validation?.strengths ) ? validation.strengths : [];
    const warnings = Array.isArray( validation?.warnings ) ? validation.warnings : [];
    const suggestions = Array.isArray( validation?.suggestions ) ? validation.suggestions : [];

    return (
        <div className={ `${ rootClass }__checklist` }>
            <div className={ `${ rootClass }__score` }>
                <span>{ __( "Conversion score", "fooconvert" ) }</span>
                <strong>{ Number.isFinite( Number( validation?.score ) ) ? `${ validation.score }/100` : "–" }</strong>
            </div>
            { strengths.length > 0 && (
                <div>
                    <h4>{ __( "Strengths", "fooconvert" ) }</h4>
                    <ul>
                        { strengths.map( item => <li key={ item }>{ item }</li> ) }
                    </ul>
                </div>
            ) }
            { warnings.length > 0 && (
                <div>
                    <h4>{ __( "Watchouts", "fooconvert" ) }</h4>
                    <ul>
                        { warnings.map( item => <li key={ item }>{ item }</li> ) }
                    </ul>
                </div>
            ) }
            { suggestions.length > 0 && (
                <div>
                    <h4>{ __( "Suggestions", "fooconvert" ) }</h4>
                    <ul>
                        { suggestions.map( item => <li key={ item }>{ item }</li> ) }
                    </ul>
                </div>
            ) }
        </div>
    );
};

const GuidanceList = ( { title, items } ) => {
    const rows = Array.isArray( items ) ? items.filter( Boolean ) : [];

    if ( rows.length === 0 ) {
        return null;
    }

    return (
        <div className={ `${ rootClass }__guidance-section` }>
            <h4>{ title }</h4>
            <ul className={ `${ rootClass }__plain-list` }>
                { rows.map( item => <li key={ item }>{ item }</li> ) }
            </ul>
        </div>
    );
};

const BrandColorControl = ( { label, value, onChange, help } ) => (
    <div className={ `${ rootClass }__color-control` }>
        <TextControl
            label={ label }
            value={ value }
            onChange={ onChange }
            help={ help }
            placeholder="#000000"
            __nextHasNoMarginBottom
            __next40pxDefaultSize
        />
        <span
            className={ `${ rootClass }__color-swatch` }
            aria-hidden="true"
            style={ {
                background: value || "transparent",
            } }
        />
    </div>
);

const BrandPreviewList = ( { rows } ) => {
    const items = Array.isArray( rows ) ? rows.filter( row => row?.label ) : [];

    if ( items.length === 0 ) {
        return null;
    }

    return (
        <div className={ `${ rootClass }__preview-list` }>
            { items.map( row => (
                <div key={ row.label } className={ `${ rootClass }__preview-row` }>
                    <span>{ row.label }</span>
                    <strong>{ row.value }</strong>
                </div>
            ) ) }
        </div>
    );
};

const BrandSectionCard = ( { title, isEditing, onToggle, preview, children } ) => (
    <Card className={ `${ rootClass }__brand-card ${ isEditing ? `${ rootClass }__brand-card--editing` : "" }` }>
        <CardHeader>
            <Flex justify="space-between" align="center">
                <FlexBlock>
                    <h3>{ title }</h3>
                </FlexBlock>
                <Button variant={ isEditing ? "primary" : "secondary" } onClick={ onToggle }>
                    { isEditing ? __( "Save", "fooconvert" ) : __( "Edit", "fooconvert" ) }
                </Button>
            </Flex>
        </CardHeader>
        <CardBody>
            { isEditing ? children : preview }
        </CardBody>
    </Card>
);

const ContextSummaryCard = ( { title, summary, preview, onOpen, actionLabel = __( "Open", "fooconvert" ) } ) => (
    <Card className={ `${ rootClass }__context-card` }>
        <CardHeader>
            <Flex justify="space-between" align="center">
                <FlexBlock>
                    <div className={ `${ rootClass }__context-card-head` }>
                        <h3>{ title }</h3>
                        { summary && <p className={ `${ rootClass }__muted-copy` }>{ summary }</p> }
                    </div>
                </FlexBlock>
                <Button variant="secondary" onClick={ onOpen }>
                    { actionLabel }
                </Button>
            </Flex>
        </CardHeader>
        <CardBody>
            <div className={ `${ rootClass }__context-card-preview` }>
                { preview }
            </div>
        </CardBody>
    </Card>
);

const ReadOnlyTextField = ( { label, value, rows = 12 } ) => (
    <TextareaControl
        label={ label }
        value={ value }
        onChange={ () => {} }
        readOnly
        rows={ rows }
        __nextHasNoMarginBottom
        __next40pxDefaultSize
    />
);

const ContextChipRow = ( { items, limit = 6 } ) => {
    const rows = Array.isArray( items ) ? items.filter( Boolean ).slice( 0, limit ) : [];

    if ( rows.length === 0 ) {
        return null;
    }

    return (
        <div className={ `${ rootClass }__context-chip-row` }>
            { rows.map( ( item, index ) => (
                <span key={ `${ item }-${ index }` } className={ `${ rootClass }__context-chip` }>
                    { item }
                </span>
            ) ) }
        </div>
    );
};

const ContextCodePreview = ( { content } ) => {
    const text = String( content || "" ).trim();

    if ( text.length === 0 ) {
        return (
            <p className={ `${ rootClass }__muted-copy` }>
                { __( "No context available yet.", "fooconvert" ) }
            </p>
        );
    }

    return (
        <pre className={ `${ rootClass }__context-code` }>
            { text }
        </pre>
    );
};

const formatJsonValue = ( value ) => JSON.stringify( value ?? null, null, 2 ) || "";

const ActivityTimeline = ( { items, isSending, activeIndex } ) => {
    const rows = Array.isArray( items ) ? items.filter( Boolean ) : [];

    if ( rows.length === 0 ) {
        return (
            <p className={ `${ rootClass }__muted-copy` }>
                { __( "Tool calls, validation runs, and builder status updates will appear here for each response.", "fooconvert" ) }
            </p>
        );
    }

    return (
        <div className={ `${ rootClass }__activity-list` }>
            { rows.map( ( item, index ) => {
                const type = item?.type || "status";
                const state = isSending
                    ? ( index < activeIndex ? "complete" : ( index === activeIndex ? "current" : "pending" ) )
                    : "complete";

                return (
                    <div key={ `${ type }-${ item?.label || "step" }-${ index }` } className={ `${ rootClass }__activity-item ${ rootClass }__activity-item--${ state }` }>
                        <div className={ `${ rootClass }__activity-marker` }>
                            { isSending && state === "current" ? <Spinner /> : null }
                        </div>
                        <div className={ `${ rootClass }__activity-copy` }>
                            <div className={ `${ rootClass }__activity-label-row` }>
                                <strong>{ item?.label || __( "Working", "fooconvert" ) }</strong>
                                <span>{ type.replace( "_", " " ) }</span>
                            </div>
                            { item?.summary && (
                                <p>{ item.summary }</p>
                            ) }
                        </div>
                    </div>
                );
            } ) }
        </div>
    );
};

const App = () => {
    const initialBrand = normalizeBrand( config?.brand?.savedBrand || config?.brand?.defaultBrand || {} );
    const initialSavedBrand = config?.brand?.hasSavedBrand
        ? normalizeBrand( config?.brand?.savedBrand || {} )
        : createEmptyBrand();

    const [ messages, setMessages ] = useState( [] );
    const [ input, setInput ] = useState( "" );
    const [ draft, setDraft ] = useState( null );
    const [ validation, setValidation ] = useState( null );
    const [ mediaItems, setMediaItems ] = useState( Array.isArray( config?.mediaItems ) ? config.mediaItems : [] );
    const [ lastResponse, setLastResponse ] = useState( null );
    const [ generateImagesOnSubmit, setGenerateImagesOnSubmit ] = useState( false );
    const [ mediaInstructions, setMediaInstructions ] = useState( "" );
    const [ suggestedPrompts, setSuggestedPrompts ] = useState( Array.isArray( config?.starterPrompts ) ? config.starterPrompts : [] );
    const [ saveTitle, setSaveTitle ] = useState( "" );
    const [ titleTouched, setTitleTouched ] = useState( false );
    const [ isSending, setSending ] = useState( false );
    const [ isSavingDraft, setSavingDraft ] = useState( false );
    const [ deletingMediaId, setDeletingMediaId ] = useState( 0 );
    const [ error, setError ] = useState( "" );
    const [ statusNotice, setStatusNotice ] = useState( null );
    const [ savedPopup, setSavedPopup ] = useState( null );
    const [ previewOpen, setPreviewOpen ] = useState( false );
    const [ previewLoading, setPreviewLoading ] = useState( true );
    const [ activityLog, setActivityLog ] = useState( [] );
    const [ pendingActivityIndex, setPendingActivityIndex ] = useState( 0 );
    const [ brand, setBrand ] = useState( initialBrand );
    const [ savedBrandSnapshot, setSavedBrandSnapshot ] = useState( initialSavedBrand );
    const [ editingBrandSections, setEditingBrandSections ] = useState( createBrandSectionState() );
    const [ isExtractingBrand, setExtractingBrand ] = useState( false );
    const [ isSavingBrand, setSavingBrand ] = useState( false );
    const [ remoteBrandUrl, setRemoteBrandUrl ] = useState( "" );
    const [ showRemoteBrandInput, setShowRemoteBrandInput ] = useState( false );
    const [ contextModal, setContextModal ] = useState( "" );
    const chatEndRef = useRef( null );

    const generatedMarkup = useMemo( () => {
        if ( !draft ) {
            return "";
        }

        try {
            return serializeDraftToMarkup( draft, templatesBySlug, Array.isArray( config?.blockCatalog ) ? config.blockCatalog : [] );
        } catch ( exception ) {
            return "";
        }
    }, [ draft ] );

    const summaryRows = useMemo( () => {
        if ( !draft ) {
            return [];
        }

        return [
            {
                label: __( "Type", "fooconvert" ),
                value: config?.labels?.[ normalizePopupType( draft.popup_type ) ] || draft.popup_type,
            },
            {
                label: __( "Goal", "fooconvert" ),
                value: draft.goal || __( "Not set", "fooconvert" ),
            },
            {
                label: __( "Offer", "fooconvert" ),
                value: draft.offer || __( "Not set", "fooconvert" ),
            },
            {
                label: __( "Action", "fooconvert" ),
                value: getActionSummary( draft ),
            },
            {
                label: __( "Trigger", "fooconvert" ),
                value: getTriggerSummary( draft ),
            },
        ];
    }, [ draft ] );

    const lastAssistantMessage = useMemo( () => buildLastAssistantMessage( messages ), [ messages ] );
    const brandIsDirty = useMemo(
        () => serializeComparable( brand ) !== serializeComparable( savedBrandSnapshot ),
        [ brand, savedBrandSnapshot ]
    );
    const displayActivityLog = isSending ? pendingActivitySteps : activityLog;
    const previewUrl = savedPopup?.previewUrl || "";
    const embeddedPreviewUrl = useMemo( () => buildPreviewUrl( previewUrl ), [ previewUrl ] );
    const conversionRationale = Array.isArray( draft?.conversion_rationale ) ? draft.conversion_rationale.filter( Boolean ) : [];
    const implementationNotes = Array.isArray( draft?.notes ) ? draft.notes.filter( Boolean ) : [];
    const brandPalette = [
        {
            label: __( "Primary", "fooconvert" ),
            value: brand?.colors?.primary,
        },
        {
            label: __( "Secondary", "fooconvert" ),
            value: brand?.colors?.secondary,
        },
        {
            label: __( "Accent", "fooconvert" ),
            value: brand?.colors?.accent,
        },
        {
            label: __( "Background", "fooconvert" ),
            value: brand?.colors?.background,
        },
        {
            label: __( "Primary text", "fooconvert" ),
            value: brand?.colors?.textPrimary,
        },
        {
            label: __( "Secondary text", "fooconvert" ),
            value: brand?.colors?.textSecondary,
        },
    ].filter( color => typeof color.value === "string" && color.value.length > 0 );
    const primaryButtonPreviewStyle = useMemo(
        () => getButtonPreviewStyle( brand?.components?.buttonPrimary, brand?.colors?.primary ),
        [ brand ]
    );
    const secondaryButtonPreviewStyle = useMemo(
        () => getButtonPreviewStyle( brand?.components?.buttonSecondary, brand?.colors?.primary ),
        [ brand ]
    );
    const blockCatalog = Array.isArray( config?.blockCatalog ) ? config.blockCatalog : [];
    const templateLibrary = Array.isArray( config?.templates ) ? config.templates : [];
    const conversionPlaybook = isPlainObject( config?.playbook ) ? config.playbook : {};
    const abilityNames = Array.isArray( config?.abilities ) ? config.abilities : [];
    const hasSavedBrandProfile = config?.brand?.hasSavedBrand || serializeComparable( savedBrandSnapshot ) !== serializeComparable( createEmptyBrand() );
    const blockSourceCounts = useMemo( () => (
        blockCatalog.reduce( ( counts, block ) => {
            const blockName = String( block?.name || "" );

            if ( blockName.startsWith( "fc/" ) ) {
                counts.fooconvert += 1;
            } else if ( blockName.startsWith( "woocommerce/" ) ) {
                counts.woocommerce += 1;
            } else if ( blockName.startsWith( "core/" ) ) {
                counts.core += 1;
            } else {
                counts.other += 1;
            }

            if ( block?.supports_children ) {
                counts.containers += 1;
            }

            return counts;
        }, {
            core: 0,
            fooconvert: 0,
            woocommerce: 0,
            other: 0,
            containers: 0,
        } )
    ), [ blockCatalog ] );
    const templateCounts = useMemo( () => (
        templateLibrary.reduce( ( counts, template ) => {
            const popupType = normalizePopupType( template?.popup_type );
            counts[ popupType || "other" ] = ( counts[ popupType || "other" ] || 0 ) + 1;
            return counts;
        }, {} )
    ), [ templateLibrary ] );
    const playbookPrinciples = Array.isArray( conversionPlaybook?.principles ) ? conversionPlaybook.principles : [];
    const playbookCopyTactics = Array.isArray( conversionPlaybook?.copy_tactics ) ? conversionPlaybook.copy_tactics : [];
    const playbookPopupTypes = isPlainObject( conversionPlaybook?.popup_types ) ? conversionPlaybook.popup_types : {};
    const abilityPreviewLabels = abilityNames.map( ability => String( ability ).replace( "fooconvert/", "" ) );
    const initialBuilderTab = hasSavedBrandProfile && !brandIsDirty ? "chat" : "context";
    const liveRequestSummaryRows = [
        {
            label: __( "Messages", "fooconvert" ),
            value: sprintf( __( "%d turns", "fooconvert" ), messages.length ),
        },
        {
            label: __( "Draft", "fooconvert" ),
            value: draft ? __( "Included", "fooconvert" ) : __( "None yet", "fooconvert" ),
        },
        {
            label: __( "Media", "fooconvert" ),
            value: sprintf( __( "%d items", "fooconvert" ), mediaItems.length ),
        },
        {
            label: __( "Brand", "fooconvert" ),
            value: __( "Always attached separately", "fooconvert" ),
        },
    ];

    useEffect( () => {
        if ( draft?.title && !titleTouched ) {
            setSaveTitle( draft.title );
        }
    }, [ draft, titleTouched ] );

    useEffect( () => {
        chatEndRef.current?.scrollIntoView( {
            block: "end",
        } );
    }, [ messages, isSending ] );

    useEffect( () => {
        if ( !isSending ) {
            setPendingActivityIndex( 0 );
            return undefined;
        }

        const intervalId = window.setInterval( () => {
            setPendingActivityIndex( currentIndex => Math.min( currentIndex + 1, pendingActivitySteps.length - 1 ) );
        }, 3000 );

        return () => {
            window.clearInterval( intervalId );
        };
    }, [ isSending ] );

    useEffect( () => {
        if ( config?.brand?.hasSavedBrand ) {
            return;
        }

        const extractBrand = async() => {
            setExtractingBrand( true );

            try {
                const response = await apiFetch( {
                    path: config?.api?.extractBrandPath || "/fooconvert/v1/ai-popup-builder/brand/extract",
                    method: "POST",
                    data: {
                        mode: "local",
                    },
                } );

                const nextBrand = normalizeBrand( response?.brand );

                startTransition( () => {
                    setBrand( nextBrand );
                    setEditingBrandSections( createBrandSectionState() );
                    setStatusNotice( {
                        status: "info",
                        message: __( "A starter brand profile was extracted from the current site so the builder can style popups from the site itself.", "fooconvert" ),
                    } );
                } );
            } catch ( exception ) {
                setError( exception?.message || __( "Brand extraction failed. You can fill in the brand details manually and save them.", "fooconvert" ) );
            } finally {
                setExtractingBrand( false );
            }
        };

        extractBrand();
    }, [] );

    const openEditInNewTab = () => {
        if ( typeof savedPopup?.editUrl !== "string" || savedPopup.editUrl.length === 0 ) {
            return;
        }

        window.open( savedPopup.editUrl, "_blank", "noopener,noreferrer" );
    };

    const persistDraft = async( {
        nextDraft = draft,
        nextValidation = validation,
        nextMediaItems = mediaItems,
        nextMessages = messages,
        nextResponse = lastResponse,
        nextSuggestedPrompts = suggestedPrompts,
        nextTitle = saveTitle,
        options = {
            generate_images: generateImagesOnSubmit,
            force_image_generation: false,
        },
    } = {} ) => {
        if ( !nextDraft ) {
            return null;
        }

        let nextMarkup = "";

        try {
            nextMarkup = serializeDraftToMarkup(
                nextDraft,
                templatesBySlug,
                Array.isArray( config?.blockCatalog ) ? config.blockCatalog : []
            );
        } catch ( exception ) {
            nextMarkup = "";
        }

        if ( !nextMarkup ) {
            setError( __( "The popup draft could not be serialized into blocks.", "fooconvert" ) );
            return null;
        }

        setSavingDraft( true );

        try {
            const response = await apiFetch( {
                path: config?.api?.savePath || "/fooconvert/v1/ai-popup-builder/save",
                method: "POST",
                data: {
                    post_id: Number.isFinite( Number( savedPopup?.postId ) ) ? Number( savedPopup.postId ) : undefined,
                    title: nextTitle || nextDraft.title,
                    popup_type: nextDraft.popup_type,
                    post_content: nextMarkup,
                    ai_metadata: {
                        messages: nextMessages,
                        assistant_message: nextResponse
                            ? ( nextResponse?.assistant_message || "" )
                            : buildLastAssistantMessage( nextMessages ),
                        clarifying_question: nextResponse
                            ? ( nextResponse?.clarifying_question || "" )
                            : "",
                        suggested_prompts: nextSuggestedPrompts,
                        popup_draft: nextDraft,
                        validation: nextValidation,
                        media_items: nextMediaItems,
                        options,
                    },
                },
            } );

            setSavedPopup( response );
            setStatusNotice( {
                status: "success",
                message: response?.updatedExisting
                    ? __( "Draft popup updated automatically. Preview or edit it whenever you want.", "fooconvert" )
                    : __( "Draft popup created automatically. Preview it or open it in the editor.", "fooconvert" ),
            } );

            return response;
        } catch ( exception ) {
            setError( exception?.message || __( "The popup draft could not be saved.", "fooconvert" ) );
            return null;
        } finally {
            setSavingDraft( false );
        }
    };

    const sendPrompt = async( promptText, options = {} ) => {
        const prompt = String( promptText || "" ).trim();
        const shouldGenerateImages = options?.generateImages ?? generateImagesOnSubmit;
        const shouldForceImageGeneration = Boolean( options?.forceImageGeneration );

        if ( prompt.length === 0 || isSending || !config?.aiClientAvailable || isExtractingBrand ) {
            return;
        }

        const nextMessages = [ ...messages, { role: "user", content: prompt } ];

        setMessages( nextMessages );
        setInput( "" );
        setSending( true );
        setError( "" );
        setStatusNotice( null );

        try {
            const response = await apiFetch( {
                path: config?.api?.chatPath || "/fooconvert/v1/ai-popup-builder/chat",
                method: "POST",
                data: {
                    messages: nextMessages,
                    popup_draft: draft || undefined,
                    generate_images: shouldGenerateImages,
                    force_image_generation: shouldForceImageGeneration,
                    brand,
                },
            } );

            const assistantMessage = response?.clarifying_question
                || response?.assistant_message
                || __( "I prepared a popup direction for you.", "fooconvert" );
            const nextConversation = [ ...nextMessages, { role: "assistant", content: assistantMessage } ];
            const nextDraft = isPlainObject( response?.popup_draft ) ? response.popup_draft : null;
            const nextValidation = isPlainObject( response?.validation ) ? response.validation : null;
            const nextMediaItems = Array.isArray( response?.media_items ) ? response.media_items : [];
            const nextActivityLog = Array.isArray( response?.activity_log ) ? response.activity_log : [];
            const nextPrompts = Array.isArray( response?.suggested_prompts ) ? response.suggested_prompts : [];

            startTransition( () => {
                setMessages( nextConversation );
                setSuggestedPrompts( nextPrompts );
                setDraft( nextDraft );
                setValidation( nextValidation );
                setMediaItems( nextMediaItems );
                setLastResponse( isPlainObject( response ) ? response : null );
                setActivityLog( nextActivityLog );
            } );

            if ( nextDraft ) {
                const nextTitle = titleTouched ? ( saveTitle || nextDraft.title ) : nextDraft.title;

                await persistDraft( {
                    nextDraft,
                    nextValidation,
                    nextMediaItems,
                    nextMessages: nextConversation,
                    nextResponse: response,
                    nextSuggestedPrompts: nextPrompts,
                    nextTitle,
                    options: {
                        generate_images: shouldGenerateImages,
                        force_image_generation: shouldForceImageGeneration,
                    },
                } );
            }
        } catch ( exception ) {
            setError( exception?.message || __( "The AI popup builder could not complete that request.", "fooconvert" ) );
        } finally {
            setSending( false );
        }
    };

    const handleSubmit = async( event ) => {
        event.preventDefault();
        await sendPrompt( input );
    };

    const copyMarkup = async() => {
        if ( !generatedMarkup ) {
            return;
        }

        try {
            await navigator.clipboard.writeText( generatedMarkup );
            setStatusNotice( {
                status: "success",
                message: __( "Popup block HTML copied to the clipboard.", "fooconvert" ),
            } );
        } catch ( exception ) {
            setError( __( "Could not copy the block HTML to the clipboard.", "fooconvert" ) );
        }
    };

    const extractBrand = async( mode = "local" ) => {
        const remoteUrl = String( remoteBrandUrl || "" ).trim();

        if ( mode === "remote" && remoteUrl.length === 0 ) {
            setError( __( "Enter a remote URL before starting remote brand extraction.", "fooconvert" ) );
            return;
        }

        setExtractingBrand( true );
        setError( "" );

        try {
            const response = await apiFetch( {
                path: config?.api?.extractBrandPath || "/fooconvert/v1/ai-popup-builder/brand/extract",
                method: "POST",
                data: mode === "remote"
                    ? {
                        mode: "remote",
                        url: remoteUrl,
                    }
                    : {
                        mode: "local",
                    },
            } );

            const nextBrand = normalizeBrand( response?.brand );

            startTransition( () => {
                setBrand( nextBrand );
                setEditingBrandSections( createBrandSectionState() );
                if ( mode === "remote" ) {
                    setRemoteBrandUrl( "" );
                    setShowRemoteBrandInput( false );
                }
                setStatusNotice( {
                    status: "info",
                    message: mode === "remote"
                        ? __( "Remote brand extraction completed. The extracted values are ready to review and save.", "fooconvert" )
                        : __( "Brand extraction completed. The extracted values are ready to review and save.", "fooconvert" ),
                } );
            } );
        } catch ( exception ) {
            setError( exception?.message || __( "Brand extraction failed.", "fooconvert" ) );
        } finally {
            setExtractingBrand( false );
        }
    };

    const saveBrandProfile = async() => {
        setSavingBrand( true );
        setError( "" );

        try {
            const response = await apiFetch( {
                path: config?.api?.brandPath || "/fooconvert/v1/ai-popup-builder/brand",
                method: "POST",
                data: {
                    brand,
                },
            } );

            const nextBrand = normalizeBrand( response?.brand || brand );

            startTransition( () => {
                setBrand( nextBrand );
                setSavedBrandSnapshot( nextBrand );
                setEditingBrandSections( createBrandSectionState() );
                setStatusNotice( {
                    status: "success",
                    message: __( "Brand saved for reuse. The AI builder will now use it as the main styling source.", "fooconvert" ),
                } );
            } );
        } catch ( exception ) {
            setError( exception?.message || __( "The brand profile could not be saved.", "fooconvert" ) );
        } finally {
            setSavingBrand( false );
        }
    };

    const updateBrandField = ( path, value ) => {
        setBrand( currentBrand => setNestedValue( currentBrand, path, value ) );
    };

    const toggleBrandSection = ( section ) => {
        setEditingBrandSections( currentSections => ( {
            ...currentSections,
            [ section ]: !currentSections?.[ section ],
        } ) );
    };

    const generatePopupImage = async() => {
        if ( !draft || isSending || !config?.imageGenerationAvailable ) {
            return;
        }

        const prompt = mediaInstructions.trim().length > 0
            ? mediaInstructions
            : __( "Generate a new popup image that fits this popup and incorporate it into the draft.", "fooconvert" );

        await sendPrompt( prompt, {
            generateImages: true,
            forceImageGeneration: true,
        } );

        setMediaInstructions( "" );
    };

    const insertMediaIntoDraft = async( mediaItem ) => {
        if ( !draft ) {
            return;
        }

        const nextDraft = applyMediaItemToDraft( draft, mediaItem );

        startTransition( () => {
            setDraft( nextDraft );
        } );

        await persistDraft( {
            nextDraft,
        } );
    };

    const deleteMediaItem = async( mediaItem ) => {
        const mediaId = Number( mediaItem?.id );

        if ( !Number.isFinite( mediaId ) || mediaId <= 0 || deletingMediaId > 0 ) {
            return;
        }

        const confirmed = window.confirm( __( "Delete this generated image from the media library?", "fooconvert" ) );
        if ( !confirmed ) {
            return;
        }

        setDeletingMediaId( mediaId );
        setError( "" );

        try {
            const response = await apiFetch( {
                path: `${ config?.api?.deleteMediaPath || "/fooconvert/v1/ai-popup-builder/media" }/${ mediaId }`,
                method: "DELETE",
            } );

            const nextMediaItems = Array.isArray( response?.media_items )
                ? response.media_items
                : mediaItems.filter( item => Number( item?.id ) !== mediaId );
            const nextDraft = draft ? removeMediaItemFromDraft( draft, mediaItem ) : null;

            startTransition( () => {
                setMediaItems( nextMediaItems );
                setDraft( nextDraft );
            } );

            if ( nextDraft ) {
                await persistDraft( {
                    nextDraft,
                    nextMediaItems,
                } );
            }
        } catch ( exception ) {
            setError( exception?.message || __( "The generated image could not be deleted.", "fooconvert" ) );
        } finally {
            setDeletingMediaId( 0 );
        }
    };

    const syncTitleToDraft = async() => {
        if ( !draft ) {
            return;
        }

        const nextTitle = String( saveTitle || draft.title || "" ).trim();

        if ( nextTitle.length === 0 || nextTitle === draft?.title ) {
            return;
        }

        const nextDraft = {
            ...draft,
            title: nextTitle,
        };

        startTransition( () => {
            setDraft( nextDraft );
        } );

        await persistDraft( {
            nextDraft,
            nextTitle,
        } );
    };

    const brandEditorContent = (
        <div className={ `${ rootClass }__stack` }>
            <div className={ `${ rootClass }__tab-intro` }>
                <div>
                    <p>
                        { __( "The AI uses this brand data first for colors, typography, spacing, and button styling. Templates are only structural guides.", "fooconvert" ) }
                    </p>
                </div>
                <div className={ `${ rootClass }__tab-actions` }>
                    <Button
                        variant="secondary"
                        onClick={ () => extractBrand( "local" ) }
                        disabled={ isExtractingBrand }
                    >
                        { isExtractingBrand ? __( "Extracting…", "fooconvert" ) : __( "Extract Current Site", "fooconvert" ) }
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={ () => setShowRemoteBrandInput( current => !current ) }
                        disabled={ isExtractingBrand }
                    >
                        { showRemoteBrandInput ? __( "Hide Remote URL", "fooconvert" ) : __( "Extract Remote URL", "fooconvert" ) }
                    </Button>
                    <Button
                        variant="primary"
                        onClick={ saveBrandProfile }
                        disabled={ isSavingBrand || !brandIsDirty }
                    >
                        { isSavingBrand ? __( "Saving…", "fooconvert" ) : __( "Save Brand", "fooconvert" ) }
                    </Button>
                </div>
            </div>

            { showRemoteBrandInput && (
                <Card>
                    <CardBody>
                        <div className={ `${ rootClass }__remote-extract-panel` }>
                            <div className={ `${ rootClass }__remote-extract-row` }>
                                <TextControl
                                    label={ __( "Remote URL", "fooconvert" ) }
                                    value={ remoteBrandUrl }
                                    onChange={ setRemoteBrandUrl }
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                />
                                <div className={ `${ rootClass }__inline-actions` }>
                                    <Button
                                        variant="secondary"
                                        onClick={ () => extractBrand( "remote" ) }
                                        disabled={ isExtractingBrand || remoteBrandUrl.trim().length === 0 }
                                    >
                                        { isExtractingBrand ? __( "Extracting…", "fooconvert" ) : __( "Run Remote Extract", "fooconvert" ) }
                                    </Button>
                                </div>
                            </div>
                            <p className={ `${ rootClass }__muted-copy` }>
                                { __( "Optional. Use this when you want to extract brand details from another live URL instead of the current site.", "fooconvert" ) }
                            </p>
                        </div>
                    </CardBody>
                </Card>
            ) }

            <div className={ `${ rootClass }__brand-grid` }>
                <BrandSectionCard
                    title={ __( "Brand Overview", "fooconvert" ) }
                    isEditing={ !!editingBrandSections?.overview }
                    onToggle={ () => toggleBrandSection( "overview" ) }
                    preview={
                        <div className={ `${ rootClass }__preview-stack` }>
                            <div className={ `${ rootClass }__overview-preview` }>
                                <p>
                                    { truncateText( brand?.brandOverview, 220 ) || __( "Add a short brand overview so the AI has tone and positioning context.", "fooconvert" ) }
                                </p>
                            </div>
                        </div>
                    }
                >
                    <div className={ `${ rootClass }__field-grid` }>
                        <TextareaControl
                            label={ __( "Brand Overview", "fooconvert" ) }
                            value={ brand?.brandOverview || "" }
                            onChange={ value => updateBrandField( "brandOverview", value ) }
                            help={ __( "This starts from the site tagline on first run and gives the AI tone and positioning context.", "fooconvert" ) }
                            rows={ 5 }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </div>
                </BrandSectionCard>

                <BrandSectionCard
                    title={ __( "Palette", "fooconvert" ) }
                    isEditing={ !!editingBrandSections?.palette }
                    onToggle={ () => toggleBrandSection( "palette" ) }
                    preview={
                        brandPalette.length > 0 ? (
                            <div className={ `${ rootClass }__preview-stack` }>
                                <div className={ `${ rootClass }__brand-meta-row` }>
                                    <span className={ `${ rootClass }__meta-pill` }>
                                        { sprintf(
                                            __( "%s scheme", "fooconvert" ),
                                            getColorSchemeLabel( brand?.colorScheme )
                                        ) }
                                    </span>
                                </div>
                                <div className={ `${ rootClass }__swatch-row` }>
                                    { brandPalette.map( color => (
                                        <div key={ color.label } className={ `${ rootClass }__swatch-chip` }>
                                            <span
                                                aria-hidden="true"
                                                style={ {
                                                    background: color.value,
                                                } }
                                            />
                                            <strong>{ color.label }</strong>
                                            <small>{ color.value }</small>
                                        </div>
                                    ) ) }
                                </div>
                            </div>
                        ) : (
                            <p className={ `${ rootClass }__muted-copy` }>
                                { __( "Extract or set the core brand colors to guide the popup styling.", "fooconvert" ) }
                            </p>
                        )
                    }
                >
                    <div className={ `${ rootClass }__field-grid` }>
                        <BrandColorControl
                            label={ __( "Primary", "fooconvert" ) }
                            value={ brand?.colors?.primary || "" }
                            onChange={ value => updateBrandField( "colors.primary", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Secondary", "fooconvert" ) }
                            value={ brand?.colors?.secondary || "" }
                            onChange={ value => updateBrandField( "colors.secondary", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Accent", "fooconvert" ) }
                            value={ brand?.colors?.accent || "" }
                            onChange={ value => updateBrandField( "colors.accent", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Background", "fooconvert" ) }
                            value={ brand?.colors?.background || "" }
                            onChange={ value => updateBrandField( "colors.background", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Primary text", "fooconvert" ) }
                            value={ brand?.colors?.textPrimary || "" }
                            onChange={ value => updateBrandField( "colors.textPrimary", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Secondary text", "fooconvert" ) }
                            value={ brand?.colors?.textSecondary || "" }
                            onChange={ value => updateBrandField( "colors.textSecondary", value ) }
                        />
                    </div>
                    <div className={ `${ rootClass }__compact-control` }>
                        <SelectControl
                            label={ __( "Color scheme", "fooconvert" ) }
                            value={ brand?.colorScheme || "light" }
                            onChange={ value => updateBrandField( "colorScheme", value ) }
                            options={ [
                                {
                                    label: __( "Light", "fooconvert" ),
                                    value: "light",
                                },
                                {
                                    label: __( "Dark", "fooconvert" ),
                                    value: "dark",
                                },
                            ] }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </div>
                </BrandSectionCard>

                <BrandSectionCard
                    title={ __( "Typography", "fooconvert" ) }
                    isEditing={ !!editingBrandSections?.typography }
                    onToggle={ () => toggleBrandSection( "typography" ) }
                    preview={
                        <div className={ `${ rootClass }__preview-stack` }>
                            <div className={ `${ rootClass }__type-specimen` }>
                                <div
                                    className={ `${ rootClass }__type-specimen-heading` }
                                    style={ {
                                        fontFamily: brand?.typography?.fontFamilies?.heading || brand?.typography?.fontFamilies?.primary || undefined,
                                        fontSize: brand?.typography?.fontSizes?.h1?.value || undefined,
                                        fontWeight: brand?.typography?.fontWeights?.bold || undefined,
                                    } }
                                >
                                    { __( "Headline Sample", "fooconvert" ) }
                                </div>
                                <div
                                    className={ `${ rootClass }__type-specimen-body` }
                                    style={ {
                                        fontFamily: brand?.typography?.fontFamilies?.primary || undefined,
                                        fontSize: brand?.typography?.fontSizes?.body?.value || undefined,
                                        fontWeight: brand?.typography?.fontWeights?.regular || undefined,
                                    } }
                                >
                                    { __( "Body copy sample for popup descriptions, proof points, and CTA support text.", "fooconvert" ) }
                                </div>
                            </div>
                            <BrandPreviewList
                                rows={ [
                                    {
                                        label: __( "Primary", "fooconvert" ),
                                        value: getPreviewValue( brand?.typography?.fontFamilies?.primary ),
                                    },
                                    {
                                        label: __( "Heading", "fooconvert" ),
                                        value: getPreviewValue( brand?.typography?.fontFamilies?.heading ),
                                    },
                                    {
                                        label: __( "H1 size", "fooconvert" ),
                                        value: getPreviewValue( brand?.typography?.fontSizes?.h1?.value ),
                                    },
                                    {
                                        label: __( "Body size", "fooconvert" ),
                                        value: getPreviewValue( brand?.typography?.fontSizes?.body?.value ),
                                    },
                                    {
                                        label: __( "Weights", "fooconvert" ),
                                        value: `${ brand?.typography?.fontWeights?.regular || 400 } / ${ brand?.typography?.fontWeights?.bold || 700 }`,
                                    },
                                ] }
                            />
                        </div>
                    }
                >
                    <div className={ `${ rootClass }__field-grid` }>
                        <TextControl
                            label={ __( "Primary font family", "fooconvert" ) }
                            value={ brand?.typography?.fontFamilies?.primary || "" }
                            onChange={ value => updateBrandField( "typography.fontFamilies.primary", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "Heading font family", "fooconvert" ) }
                            value={ brand?.typography?.fontFamilies?.heading || "" }
                            onChange={ value => updateBrandField( "typography.fontFamilies.heading", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "H1 size", "fooconvert" ) }
                            value={ brand?.typography?.fontSizes?.h1?.value || "" }
                            onChange={ value => updateBrandField( "typography.fontSizes.h1.value", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "Body size", "fooconvert" ) }
                            value={ brand?.typography?.fontSizes?.body?.value || "" }
                            onChange={ value => updateBrandField( "typography.fontSizes.body.value", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "Regular weight", "fooconvert" ) }
                            type="number"
                            value={ String( brand?.typography?.fontWeights?.regular || "" ) }
                            onChange={ value => updateBrandField( "typography.fontWeights.regular", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "Bold weight", "fooconvert" ) }
                            type="number"
                            value={ String( brand?.typography?.fontWeights?.bold || "" ) }
                            onChange={ value => updateBrandField( "typography.fontWeights.bold", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </div>
                </BrandSectionCard>

                <BrandSectionCard
                    title={ __( "Buttons & Spacing", "fooconvert" ) }
                    isEditing={ !!editingBrandSections?.controls }
                    onToggle={ () => toggleBrandSection( "controls" ) }
                    preview={
                        <div className={ `${ rootClass }__preview-stack` }>
                            <div className={ `${ rootClass }__button-preview-row` }>
                                <button type="button" className={ `${ rootClass }__button-preview` } style={ primaryButtonPreviewStyle }>
                                    { __( "Primary CTA", "fooconvert" ) }
                                </button>
                                <button type="button" className={ `${ rootClass }__button-preview` } style={ secondaryButtonPreviewStyle }>
                                    { __( "Secondary CTA", "fooconvert" ) }
                                </button>
                            </div>
                            <BrandPreviewList
                                rows={ [
                                    {
                                        label: __( "Base unit", "fooconvert" ),
                                        value: getPreviewValue(
                                            brand?.spacing?.baseUnit ? `${ brand.spacing.baseUnit }px` : "",
                                            __( "Not set", "fooconvert" )
                                        ),
                                    },
                                    {
                                        label: __( "Radius", "fooconvert" ),
                                        value: getPreviewValue( brand?.spacing?.borderRadius ),
                                    },
                                    {
                                        label: __( "Primary fill", "fooconvert" ),
                                        value: getPreviewValue( brand?.components?.buttonPrimary?.background ),
                                    },
                                    {
                                        label: __( "Secondary border", "fooconvert" ),
                                        value: getPreviewValue( brand?.components?.buttonSecondary?.borderColor ),
                                    },
                                ] }
                            />
                        </div>
                    }
                >
                    <div className={ `${ rootClass }__field-grid` }>
                        <TextControl
                            label={ __( "Base spacing unit", "fooconvert" ) }
                            value={ String( brand?.spacing?.baseUnit || "" ) }
                            onChange={ value => updateBrandField( "spacing.baseUnit", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <TextControl
                            label={ __( "Global border radius", "fooconvert" ) }
                            value={ brand?.spacing?.borderRadius || "" }
                            onChange={ value => updateBrandField( "spacing.borderRadius", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <BrandColorControl
                            label={ __( "Primary button background", "fooconvert" ) }
                            value={ brand?.components?.buttonPrimary?.background || "" }
                            onChange={ value => updateBrandField( "components.buttonPrimary.background", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Primary button text", "fooconvert" ) }
                            value={ brand?.components?.buttonPrimary?.textColor || "" }
                            onChange={ value => updateBrandField( "components.buttonPrimary.textColor", value ) }
                        />
                        <TextControl
                            label={ __( "Primary button radius", "fooconvert" ) }
                            value={ brand?.components?.buttonPrimary?.borderRadius || "" }
                            onChange={ value => updateBrandField( "components.buttonPrimary.borderRadius", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                        <BrandColorControl
                            label={ __( "Secondary button background", "fooconvert" ) }
                            value={ brand?.components?.buttonSecondary?.background || "" }
                            onChange={ value => updateBrandField( "components.buttonSecondary.background", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Secondary button text", "fooconvert" ) }
                            value={ brand?.components?.buttonSecondary?.textColor || "" }
                            onChange={ value => updateBrandField( "components.buttonSecondary.textColor", value ) }
                        />
                        <BrandColorControl
                            label={ __( "Secondary button border", "fooconvert" ) }
                            value={ brand?.components?.buttonSecondary?.borderColor || "" }
                            onChange={ value => updateBrandField( "components.buttonSecondary.borderColor", value ) }
                        />
                        <TextControl
                            label={ __( "Secondary button radius", "fooconvert" ) }
                            value={ brand?.components?.buttonSecondary?.borderRadius || "" }
                            onChange={ value => updateBrandField( "components.buttonSecondary.borderRadius", value ) }
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                        />
                    </div>
                </BrandSectionCard>
            </div>
        </div>
    );

    const renderContextModal = () => {
        if ( !contextModal ) {
            return null;
        }

        if ( "brand" === contextModal ) {
            return (
                <Modal
                    title={ __( "Brand Context", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
                    shouldCloseOnClickOutside={ true }
                >
                    { brandEditorContent }
                </Modal>
            );
        }

        if ( "blocks" === contextModal ) {
            return (
                <Modal
                    title={ __( "Supported Blocks", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "This list is generated from the supported core, FooConvert, and WooCommerce blocks currently registered on the site. The AI accesses it through abilities when it needs block rules or examples.", "fooconvert" ) }
                        </p>
                        <BrandPreviewList
                            rows={ [
                                {
                                    label: __( "Total", "fooconvert" ),
                                    value: String( blockCatalog.length ),
                                },
                                {
                                    label: __( "Core", "fooconvert" ),
                                    value: String( blockSourceCounts.core ),
                                },
                                {
                                    label: __( "FooConvert", "fooconvert" ),
                                    value: String( blockSourceCounts.fooconvert ),
                                },
                                {
                                    label: __( "WooCommerce", "fooconvert" ),
                                    value: String( blockSourceCounts.woocommerce ),
                                },
                                {
                                    label: __( "Containers", "fooconvert" ),
                                    value: String( blockSourceCounts.containers ),
                                },
                            ] }
                        />
                        <div className={ `${ rootClass }__context-list` }>
                            { blockCatalog.map( block => (
                                <Card key={ block?.name || block?.label }>
                                    <CardBody>
                                        <div className={ `${ rootClass }__context-item` }>
                                            <div className={ `${ rootClass }__context-item-head` }>
                                                <div>
                                                    <h3>{ block?.label || block?.name }</h3>
                                                    <p className={ `${ rootClass }__muted-copy` }>{ block?.name }</p>
                                                </div>
                                                <ContextChipRow
                                                    items={ [
                                                        block?.supports_children ? __( "Supports children", "fooconvert" ) : __( "Leaf block", "fooconvert" ),
                                                        Array.isArray( block?.parent ) && block.parent.length > 0 ? __( "Has parent rules", "fooconvert" ) : "",
                                                    ] }
                                                    limit={ 3 }
                                                />
                                            </div>
                                            { block?.description && (
                                                <p className={ `${ rootClass }__muted-copy` }>{ block.description }</p>
                                            ) }
                                            <BrandPreviewList
                                                rows={ [
                                                    {
                                                        label: __( "Allowed children", "fooconvert" ),
                                                        value: Array.isArray( block?.allowed_children ) && block.allowed_children.length > 0
                                                            ? block.allowed_children.join( ", " )
                                                            : __( "None", "fooconvert" ),
                                                    },
                                                    {
                                                        label: __( "Parents", "fooconvert" ),
                                                        value: Array.isArray( block?.parent ) && block.parent.length > 0
                                                            ? block.parent.join( ", " )
                                                            : __( "Any", "fooconvert" ),
                                                    },
                                                ] }
                                            />
                                        </div>
                                    </CardBody>
                                </Card>
                            ) ) }
                        </div>
                    </div>
                </Modal>
            );
        }

        if ( "templates" === contextModal ) {
            return (
                <Modal
                    title={ __( "Structural Templates", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "Templates stay secondary to the brand. The AI can request these bundled FooConvert patterns when it needs a structural guide for bars, flyouts, or popups.", "fooconvert" ) }
                        </p>
                        <BrandPreviewList
                            rows={ [
                                {
                                    label: __( "Total", "fooconvert" ),
                                    value: String( templateLibrary.length ),
                                },
                                {
                                    label: __( "Popups", "fooconvert" ),
                                    value: String( templateCounts.popup || 0 ),
                                },
                                {
                                    label: __( "Flyouts", "fooconvert" ),
                                    value: String( templateCounts.flyout || 0 ),
                                },
                                {
                                    label: __( "Bars", "fooconvert" ),
                                    value: String( templateCounts.bar || 0 ),
                                },
                            ] }
                        />
                        <div className={ `${ rootClass }__context-list` }>
                            { templateLibrary.map( template => (
                                <Card key={ template?.slug || template?.title }>
                                    <CardBody>
                                        <div className={ `${ rootClass }__context-item` }>
                                            <div className={ `${ rootClass }__context-item-head` }>
                                                <div>
                                                    <h3>{ template?.title || template?.slug }</h3>
                                                    <p className={ `${ rootClass }__muted-copy` }>{ template?.slug }</p>
                                                </div>
                                                <ContextChipRow
                                                    items={ [
                                                        config?.labels?.[ normalizePopupType( template?.popup_type ) ] || template?.popup_type,
                                                    ] }
                                                    limit={ 2 }
                                                />
                                            </div>
                                            { template?.description && (
                                                <p className={ `${ rootClass }__muted-copy` }>{ template.description }</p>
                                            ) }
                                            <BrandPreviewList
                                                rows={ [
                                                    {
                                                        label: __( "Sample blocks", "fooconvert" ),
                                                        value: Array.isArray( template?.sample_block_names ) && template.sample_block_names.length > 0
                                                            ? template.sample_block_names.join( ", " )
                                                            : __( "None listed", "fooconvert" ),
                                                    },
                                                ] }
                                            />
                                        </div>
                                    </CardBody>
                                </Card>
                            ) ) }
                        </div>
                    </div>
                </Modal>
            );
        }

        if ( "playbook" === contextModal ) {
            return (
                <Modal
                    title={ __( "Conversion Playbook", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "This playbook is available to the AI through abilities when it needs conversion heuristics, popup-type fit, or copy tactics.", "fooconvert" ) }
                        </p>
                        <Card>
                            <CardHeader>
                                <h3>{ __( "Principles", "fooconvert" ) }</h3>
                            </CardHeader>
                            <CardBody>
                                <ul className={ `${ rootClass }__plain-list` }>
                                    { playbookPrinciples.map( principle => <li key={ principle }>{ principle }</li> ) }
                                </ul>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardHeader>
                                <h3>{ __( "Popup Type Guidance", "fooconvert" ) }</h3>
                            </CardHeader>
                            <CardBody>
                                <div className={ `${ rootClass }__context-list` }>
                                    { Object.entries( playbookPopupTypes ).map( ( [ popupType, details ] ) => (
                                        <div key={ popupType } className={ `${ rootClass }__context-inline-card` }>
                                            <strong>{ config?.labels?.[ normalizePopupType( popupType ) ] || popupType }</strong>
                                            <p><strong>{ __( "Best for:", "fooconvert" ) }</strong> { details?.best_for || __( "Not set", "fooconvert" ) }</p>
                                            <p><strong>{ __( "Watchouts:", "fooconvert" ) }</strong> { details?.watchouts || __( "Not set", "fooconvert" ) }</p>
                                        </div>
                                    ) ) }
                                </div>
                            </CardBody>
                        </Card>
                        <Card>
                            <CardHeader>
                                <h3>{ __( "Copy Tactics", "fooconvert" ) }</h3>
                            </CardHeader>
                            <CardBody>
                                <ul className={ `${ rootClass }__plain-list` }>
                                    { playbookCopyTactics.map( tactic => <li key={ tactic }>{ tactic }</li> ) }
                                </ul>
                            </CardBody>
                        </Card>
                    </div>
                </Modal>
            );
        }

        if ( "system-prompt" === contextModal ) {
            return (
                <Modal
                    title={ __( "System Prompt", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "This is the default builder instruction. Per-turn image rules are appended automatically depending on whether AI image generation is enabled for that request.", "fooconvert" ) }
                        </p>
                        <ReadOnlyTextField
                            label={ __( "Builder system instruction", "fooconvert" ) }
                            value={ String( config?.systemPrompt || "" ) }
                            rows={ 20 }
                        />
                    </div>
                </Modal>
            );
        }

        if ( "abilities" === contextModal ) {
            return (
                <Modal
                    title={ __( "Abilities", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "These are the tool calls the AI can use while building or validating a popup. The builder can request them on demand during a chat turn.", "fooconvert" ) }
                        </p>
                        <BrandPreviewList
                            rows={ [
                                {
                                    label: __( "Abilities API", "fooconvert" ),
                                    value: config?.abilitiesAvailable ? __( "Available", "fooconvert" ) : __( "Unavailable", "fooconvert" ),
                                },
                                {
                                    label: __( "Allowed tools", "fooconvert" ),
                                    value: String( abilityNames.length ),
                                },
                            ] }
                        />
                        <div className={ `${ rootClass }__context-list` }>
                            { abilityNames.map( ability => (
                                <div key={ ability } className={ `${ rootClass }__context-inline-card` }>
                                    <strong>{ ability }</strong>
                                </div>
                            ) ) }
                        </div>
                    </div>
                </Modal>
            );
        }

        if ( "request" === contextModal ) {
            return (
                <Modal
                    title={ __( "Current Request Context", "fooconvert" ) }
                    onRequestClose={ () => setContextModal( "" ) }
                    className={ `${ rootClass }__context-modal ${ rootClass }__context-modal--wide` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__stack` }>
                        <p className={ `${ rootClass }__muted-copy` }>
                            { __( "On each turn the latest user message is augmented with the current popup draft, generated media, and brand JSON. The recent message history is also sent directly.", "fooconvert" ) }
                        </p>
                        <BrandPreviewList rows={ liveRequestSummaryRows } />
                        <ReadOnlyTextField
                            label={ __( "Conversation", "fooconvert" ) }
                            value={ messages.length > 0
                                ? messages.map( message => `[${ message.role }] ${ message.content }` ).join( "\n\n" )
                                : __( "No messages yet.", "fooconvert" ) }
                            rows={ 14 }
                        />
                        <ReadOnlyTextField
                            label={ __( "Current popup draft JSON", "fooconvert" ) }
                            value={ draft ? formatJsonValue( draft ) : __( "No popup draft yet.", "fooconvert" ) }
                            rows={ 16 }
                        />
                        <ReadOnlyTextField
                            label={ __( "Current media JSON", "fooconvert" ) }
                            value={ mediaItems.length > 0 ? formatJsonValue( mediaItems ) : __( "No generated media yet.", "fooconvert" ) }
                            rows={ 12 }
                        />
                    </div>
                </Modal>
            );
        }

        return null;
    };

    return (
        <div className={ rootClass }>
            <Card className={ `${ rootClass }__header-card` }>
                <CardBody>
                    <div className={ `${ rootClass }__header` }>
                        <div className={ `${ rootClass }__header-main` }>
                            <h1>{ __( "AI Popup Builder", "fooconvert" ) }</h1>
                            <p>
                                { __( "Use the site brand as the primary styling source, then let the builder assemble a popup with FooConvert, WooCommerce, and supported core blocks.", "fooconvert" ) }
                            </p>
                        </div>

                        <div className={ `${ rootClass }__header-actions` }>
                            <Button
                                variant="secondary"
                                onClick={ () => {
                                    setPreviewLoading( true );
                                    setPreviewOpen( true );
                                } }
                                disabled={ !savedPopup?.previewUrl || isSavingDraft }
                            >
                                { __( "See Preview", "fooconvert" ) }
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={ openEditInNewTab }
                                icon={ external }
                                disabled={ !savedPopup?.editUrl }
                            >
                                { __( "Edit In New Tab", "fooconvert" ) }
                            </Button>
                            <div className={ `${ rootClass }__header-status` }>
                                { isSavingDraft
                                    ? __( "Saving draft…", "fooconvert" )
                                    : ( savedPopup?.postId
                                        ? __( "Draft ready for preview and editing.", "fooconvert" )
                                        : __( "A draft popup will be created automatically after the AI responds.", "fooconvert" ) ) }
                            </div>
                        </div>
                    </div>
                </CardBody>
            </Card>

            { !config?.aiClientAvailable && (
                <Notice status="warning" isDismissible={ false }>
                    { __( "The WordPress AI client is not configured on this site yet, so chat-based popup generation is currently unavailable.", "fooconvert" ) }
                </Notice>
            ) }

            { error && (
                <Notice status="error" isDismissible={ true } onRemove={ () => setError( "" ) }>
                    { error }
                </Notice>
            ) }

            { statusNotice?.message && (
                <Notice
                    status={ statusNotice.status || "info" }
                    isDismissible={ true }
                    onRemove={ () => setStatusNotice( null ) }
                >
                    { statusNotice.message }
                </Notice>
            ) }

            <Card className={ `${ rootClass }__tabs-card` }>
                <CardBody>
                    <TabPanel
                        className={ `${ rootClass }__tabs` }
                        activeClass="is-active"
                        initialTabName={ initialBuilderTab }
                        tabs={ tabDefinitions }
                    >
                        { ( tab ) => (
                            <div className={ `${ rootClass }__tab-panel` }>
                                { tab.name === "context" && (
                                    <div className={ `${ rootClass }__stack` }>
                                        <div className={ `${ rootClass }__tab-intro` }>
                                            <div>
                                                <h2>{ __( "AI Context", "fooconvert" ) }</h2>
                                                <p>
                                                    { __( "This is the context available to the builder. Brand and live request data are passed directly, while blocks, templates, the playbook, and media tooling are available through abilities when the model needs them.", "fooconvert" ) }
                                                </p>
                                            </div>
                                        </div>
                                        <div className={ `${ rootClass }__context-grid` }>
                                            <ContextSummaryCard
                                                title={ __( "Brand", "fooconvert" ) }
                                                summary={ __( "Direct context. Saved brand data is attached to every builder request and should drive the popup styling first.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "brand" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList
                                                            rows={ [
                                                                {
                                                                    label: __( "Status", "fooconvert" ),
                                                                    value: brandIsDirty
                                                                        ? __( "Unsaved changes", "fooconvert" )
                                                                        : ( hasSavedBrandProfile ? __( "Saved", "fooconvert" ) : __( "Draft only", "fooconvert" ) ),
                                                                },
                                                                {
                                                                    label: __( "Overview", "fooconvert" ),
                                                                    value: truncateText(
                                                                        brand?.brandOverview,
                                                                        90
                                                                    ) || __( "Not set", "fooconvert" ),
                                                                },
                                                                {
                                                                    label: __( "Primary font", "fooconvert" ),
                                                                    value: getPreviewValue( brand?.typography?.fontFamilies?.primary ),
                                                                },
                                                            ] }
                                                        />
                                                        <div className={ `${ rootClass }__swatch-row` }>
                                                            { brandPalette.slice( 0, 4 ).map( color => (
                                                                <div key={ color.label } className={ `${ rootClass }__swatch-chip` }>
                                                                    <span aria-hidden="true" style={ { background: color.value } } />
                                                                    <strong>{ color.label }</strong>
                                                                </div>
                                                            ) ) }
                                                        </div>
                                                    </div>
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "Blocks", "fooconvert" ) }
                                                summary={ __( "Ability-backed. The AI can inspect the installed content blocks and nesting rules before composing advanced layouts.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "blocks" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList
                                                            rows={ [
                                                                {
                                                                    label: __( "Total", "fooconvert" ),
                                                                    value: String( blockCatalog.length ),
                                                                },
                                                                {
                                                                    label: __( "FooConvert", "fooconvert" ),
                                                                    value: String( blockSourceCounts.fooconvert ),
                                                                },
                                                                {
                                                                    label: __( "WooCommerce", "fooconvert" ),
                                                                    value: String( blockSourceCounts.woocommerce ),
                                                                },
                                                            ] }
                                                        />
                                                        <ContextChipRow
                                                            items={ blockCatalog.map( block => block?.label || block?.name ) }
                                                            limit={ 5 }
                                                        />
                                                    </div>
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "Templates", "fooconvert" ) }
                                                summary={ __( "Ability-backed. Bundled FooConvert templates provide structure only, not the primary styling direction.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "templates" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList
                                                            rows={ [
                                                                {
                                                                    label: __( "Total", "fooconvert" ),
                                                                    value: String( templateLibrary.length ),
                                                                },
                                                                {
                                                                    label: __( "Popups", "fooconvert" ),
                                                                    value: String( templateCounts.popup || 0 ),
                                                                },
                                                                {
                                                                    label: __( "Flyouts", "fooconvert" ),
                                                                    value: String( templateCounts.flyout || 0 ),
                                                                },
                                                            ] }
                                                        />
                                                        <ContextChipRow
                                                            items={ templateLibrary.map( template => template?.title ) }
                                                            limit={ 4 }
                                                        />
                                                    </div>
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "Playbook", "fooconvert" ) }
                                                summary={ __( "Ability-backed. Conversion heuristics, popup-type fit, and copy tactics the model can request while planning.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "playbook" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList
                                                            rows={ [
                                                                {
                                                                    label: __( "Principles", "fooconvert" ),
                                                                    value: String( playbookPrinciples.length ),
                                                                },
                                                                {
                                                                    label: __( "Copy tactics", "fooconvert" ),
                                                                    value: String( playbookCopyTactics.length ),
                                                                },
                                                            ] }
                                                        />
                                                        <ul className={ `${ rootClass }__plain-list ${ rootClass }__context-list-preview` }>
                                                            { playbookPrinciples.slice( 0, 2 ).map( principle => <li key={ principle }>{ principle }</li> ) }
                                                        </ul>
                                                    </div>
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "System Prompt", "fooconvert" ) }
                                                summary={ __( "Direct context. Builder rules that shape how the AI should think, validate, and respond.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "system-prompt" ) }
                                                preview={
                                                    <ContextCodePreview
                                                        content={ truncateText( String( config?.systemPrompt || "" ), 220 ) }
                                                    />
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "Abilities", "fooconvert" ) }
                                                summary={ __( "Available tool calls the model can use for templates, blocks, validation, media, and image generation.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "abilities" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList
                                                            rows={ [
                                                                {
                                                                    label: __( "API", "fooconvert" ),
                                                                    value: config?.abilitiesAvailable ? __( "Enabled", "fooconvert" ) : __( "Unavailable", "fooconvert" ),
                                                                },
                                                                {
                                                                    label: __( "Tools", "fooconvert" ),
                                                                    value: String( abilityNames.length ),
                                                                },
                                                            ] }
                                                        />
                                                        <ContextChipRow items={ abilityPreviewLabels } limit={ 5 } />
                                                    </div>
                                                }
                                            />

                                            <ContextSummaryCard
                                                title={ __( "Current Request", "fooconvert" ) }
                                                summary={ __( "Direct context. Recent messages are sent each turn, and the latest user prompt is augmented with the current draft and media state.", "fooconvert" ) }
                                                onOpen={ () => setContextModal( "request" ) }
                                                preview={
                                                    <div className={ `${ rootClass }__preview-stack` }>
                                                        <BrandPreviewList rows={ liveRequestSummaryRows } />
                                                        <p className={ `${ rootClass }__muted-copy` }>
                                                            { truncateText(
                                                                lastAssistantMessage || __( "No assistant response yet.", "fooconvert" ),
                                                                110
                                                            ) }
                                                        </p>
                                                    </div>
                                                }
                                            />
                                        </div>
                                    </div>
                                ) }

                                { tab.name === "chat" && (
                                    <div className={ `${ rootClass }__chat-grid` }>
                                        <Card>
                                            <CardHeader>
                                                <Flex justify="space-between" align="center">
                                                    <FlexBlock>
                                                        <div>
                                                            <h2>{ __( "Chat Builder", "fooconvert" ) }</h2>
                                                            <p className={ `${ rootClass }__muted-copy` }>
                                                                { __( "Describe the goal, offer, audience, and any structural direction. Brand data is always included automatically.", "fooconvert" ) }
                                                            </p>
                                                        </div>
                                                    </FlexBlock>
                                                    { isSending && <Spinner /> }
                                                </Flex>
                                            </CardHeader>
                                            <CardBody>
                                                <div className={ `${ rootClass }__messages` }>
                                                    { messages.length === 0 ? (
                                                        <div className={ `${ rootClass }__empty-state` }>
                                                            <p>{ __( "Start with a natural-language brief. Mention the conversion goal, target audience, offer, popup type if you know it, and any constraints. The builder will style from the brand first and use templates only for structure.", "fooconvert" ) }</p>
                                                            <div className={ `${ rootClass }__prompt-grid` }>
                                                                { ( Array.isArray( config?.starterPrompts ) ? config.starterPrompts : [] ).map( prompt => (
                                                                    <button
                                                                        key={ prompt }
                                                                        type="button"
                                                                        className={ `${ rootClass }__starter-card` }
                                                                        onClick={ () => sendPrompt( prompt ) }
                                                                        disabled={ isSending || isExtractingBrand }
                                                                    >
                                                                        <span>{ prompt }</span>
                                                                    </button>
                                                                ) ) }
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <Fragment>
                                                            { messages.map( ( message, index ) => (
                                                                <MessageBubble key={ `${ message.role }-${ index }` } role={ message.role } content={ message.content } />
                                                            ) ) }
                                                            { isSending && (
                                                                <div className={ `${ rootClass }__message ${ rootClass }__message--assistant` }>
                                                                    <div className={ `${ rootClass }__message-label` }>{ __( "AI strategist", "fooconvert" ) }</div>
                                                                    <div className={ `${ rootClass }__message-body` }>
                                                                        <Spinner />
                                                                    </div>
                                                                </div>
                                                            ) }
                                                        </Fragment>
                                                    ) }
                                                    <div ref={ chatEndRef } />
                                                </div>

                                                <form className={ `${ rootClass }__composer` } onSubmit={ handleSubmit }>
                                                    <TextareaControl
                                                        label={ __( "Describe the popup you want", "fooconvert" ) }
                                                        value={ input }
                                                        onChange={ setInput }
                                                        disabled={ !config?.aiClientAvailable || isSending || isExtractingBrand }
                                                        help={
                                                            isExtractingBrand
                                                                ? __( "Brand extraction is still running. Wait for it to finish before sending the next request.", "fooconvert" )
                                                                : __( "Tip: mention the audience, offer, trigger, and tone. Use Ctrl/Command + Enter to send.", "fooconvert" )
                                                        }
                                                        __nextHasNoMarginBottom
                                                        __next40pxDefaultSize
                                                        onKeyDown={ async event => {
                                                            if ( ( event.metaKey || event.ctrlKey ) && event.key === "Enter" ) {
                                                                event.preventDefault();
                                                                await sendPrompt( input );
                                                            }
                                                        } }
                                                    />

                                                    <CheckboxControl
                                                        label={ __( "Allow AI image generation on submit", "fooconvert" ) }
                                                        checked={ generateImagesOnSubmit }
                                                        onChange={ setGenerateImagesOnSubmit }
                                                        disabled={ isSending || !config?.imageGenerationAvailable }
                                                        help={
                                                            config?.imageGenerationAvailable
                                                                ? __( "Disabled by default. Turn it on only when you want the AI to generate new popup imagery during the chat flow.", "fooconvert" )
                                                                : __( "Image generation requires media upload permission and a connected AI provider with image support.", "fooconvert" )
                                                        }
                                                        __nextHasNoMarginBottom
                                                    />

                                                    <div className={ `${ rootClass }__composer-actions` }>
                                                        <div className={ `${ rootClass }__prompt-strip` }>
                                                            { suggestedPrompts.map( prompt => (
                                                                <PromptChip
                                                                    key={ prompt }
                                                                    label={ prompt }
                                                                    onClick={ () => sendPrompt( prompt ) }
                                                                    disabled={ isSending || isExtractingBrand }
                                                                />
                                                            ) ) }
                                                        </div>
                                                        <Button
                                                            variant="primary"
                                                            type="submit"
                                                            disabled={ isSending || isExtractingBrand || !config?.aiClientAvailable || input.trim().length === 0 }
                                                        >
                                                            { __( "Generate Popup", "fooconvert" ) }
                                                        </Button>
                                                    </div>
                                                </form>
                                            </CardBody>
                                        </Card>

                                        <Card>
                                            <CardHeader>
                                                <h2>{ __( "Activity", "fooconvert" ) }</h2>
                                            </CardHeader>
                                            <CardBody>
                                                <p className={ `${ rootClass }__muted-copy` }>
                                                    { __( "While the builder is working you will see its current stage here. Completed responses also show the actual ability and tool activity used during that turn.", "fooconvert" ) }
                                                </p>
                                                <ActivityTimeline
                                                    items={ displayActivityLog }
                                                    isSending={ isSending }
                                                    activeIndex={ pendingActivityIndex }
                                                />
                                            </CardBody>
                                        </Card>
                                    </div>
                                ) }

                                { tab.name === "details" && (
                                    <div className={ `${ rootClass }__stack` }>
                                        <div className={ `${ rootClass }__tab-intro` }>
                                            <div>
                                                <h2>{ __( "Popup Details", "fooconvert" ) }</h2>
                                                <p>
                                                    { __( "Review the strategy, checklist, AI guidance, and the exact popup HTML that will be stored in the draft.", "fooconvert" ) }
                                                </p>
                                            </div>
                                        </div>

                                        { !draft ? (
                                            <Notice status="info" isDismissible={ false }>
                                                { __( "Generate a popup in the chat tab first. The draft popup will be created automatically and its strategy details will appear here.", "fooconvert" ) }
                                            </Notice>
                                        ) : (
                                            <div className={ `${ rootClass }__details-grid` }>
                                                <Card>
                                                    <CardHeader>
                                                        <h3>{ __( "Strategy Summary", "fooconvert" ) }</h3>
                                                    </CardHeader>
                                                    <CardBody>
                                                        <TextControl
                                                            label={ __( "Draft title", "fooconvert" ) }
                                                            value={ saveTitle }
                                                            onChange={ value => {
                                                                setTitleTouched( true );
                                                                setSaveTitle( value );
                                                            } }
                                                            onBlur={ syncTitleToDraft }
                                                            help={ __( "The draft is saved automatically. Title changes are synced when this field loses focus.", "fooconvert" ) }
                                                            __nextHasNoMarginBottom
                                                            __next40pxDefaultSize
                                                        />

                                                        <div className={ `${ rootClass }__summary` }>
                                                            { summaryRows.map( row => (
                                                                <div key={ row.label } className={ `${ rootClass }__summary-row` }>
                                                                    <span>{ row.label }</span>
                                                                    <strong>{ row.value }</strong>
                                                                </div>
                                                            ) ) }
                                                        </div>

                                                        { draft?.template_slug && templatesBySlug?.[ draft.template_slug ] && (
                                                            <div className={ `${ rootClass }__template-chip` }>
                                                                { sprintf(
                                                                    __( "Structural template guide: %s", "fooconvert" ),
                                                                    templatesBySlug[ draft.template_slug ].title
                                                                ) }
                                                            </div>
                                                        ) }
                                                    </CardBody>
                                                </Card>

                                                <Card>
                                                    <CardHeader>
                                                        <h3>{ __( "Conversion Checklist", "fooconvert" ) }</h3>
                                                    </CardHeader>
                                                    <CardBody>
                                                        { validation ? (
                                                            <ConversionChecklist validation={ validation } />
                                                        ) : (
                                                            <p className={ `${ rootClass }__muted-copy` }>
                                                                { __( "Validation results will appear here once the popup draft has been scored.", "fooconvert" ) }
                                                            </p>
                                                        ) }
                                                    </CardBody>
                                                </Card>

                                                <Card>
                                                    <CardHeader>
                                                        <h3>{ __( "AI Guidance", "fooconvert" ) }</h3>
                                                    </CardHeader>
                                                    <CardBody>
                                                        <GuidanceList title={ __( "Why this should convert", "fooconvert" ) } items={ conversionRationale } />
                                                        <GuidanceList title={ __( "Implementation notes", "fooconvert" ) } items={ implementationNotes } />

                                                        { conversionRationale.length === 0 && implementationNotes.length === 0 && (
                                                            <p className={ `${ rootClass }__muted-copy` }>
                                                                { __( "Ask the AI to explain the conversion strategy or refine the popup for another audience, trigger, or offer.", "fooconvert" ) }
                                                            </p>
                                                        ) }
                                                    </CardBody>
                                                </Card>

                                                <Card>
                                                    <CardHeader>
                                                        <Flex justify="space-between" align="center">
                                                            <FlexBlock>
                                                                <h3>{ __( "Popup HTML", "fooconvert" ) }</h3>
                                                            </FlexBlock>
                                                            <Button variant="secondary" icon={ copySmall } onClick={ copyMarkup } disabled={ !generatedMarkup }>
                                                                { __( "Copy", "fooconvert" ) }
                                                            </Button>
                                                        </Flex>
                                                    </CardHeader>
                                                    <CardBody>
                                                        <TextareaControl
                                                            value={ generatedMarkup }
                                                            onChange={ () => {} }
                                                            readOnly
                                                            rows={ 14 }
                                                            __nextHasNoMarginBottom
                                                            __next40pxDefaultSize
                                                        />
                                                    </CardBody>
                                                </Card>
                                            </div>
                                        ) }
                                    </div>
                                ) }

                                { tab.name === "media" && (
                                    <div className={ `${ rootClass }__stack` }>
                                        <div className={ `${ rootClass }__tab-intro` }>
                                            <div>
                                                <h2>{ __( "Media", "fooconvert" ) }</h2>
                                                <p>
                                                    { __( "Generate supporting images, apply them to the current popup draft, or remove them from the media library.", "fooconvert" ) }
                                                </p>
                                            </div>
                                            <div className={ `${ rootClass }__tab-actions` }>
                                                <Button
                                                    variant="secondary"
                                                    onClick={ generatePopupImage }
                                                    disabled={ !draft || isSending || !config?.imageGenerationAvailable }
                                                >
                                                    { __( "Generate Image", "fooconvert" ) }
                                                </Button>
                                            </div>
                                        </div>

                                        <Card>
                                            <CardBody>
                                                { config?.canUploadMedia ? (
                                                    <Fragment>
                                                        <TextControl
                                                            label={ __( "New image direction", "fooconvert" ) }
                                                            value={ mediaInstructions }
                                                            onChange={ setMediaInstructions }
                                                            help={ __( "Optional. Describe a product shot, scene, mood, or art direction for the next generated popup image.", "fooconvert" ) }
                                                            disabled={ isSending || !draft || !config?.imageGenerationAvailable }
                                                            __nextHasNoMarginBottom
                                                            __next40pxDefaultSize
                                                        />

                                                        { mediaItems.length > 0 ? (
                                                            <div className={ `${ rootClass }__media-grid` }>
                                                                { mediaItems.map( mediaItem => (
                                                                    <div key={ mediaItem.id || mediaItem.url } className={ `${ rootClass }__media-card` }>
                                                                        <div className={ `${ rootClass }__media-preview` }>
                                                                            <img src={ mediaItem.previewUrl || mediaItem.url } alt={ mediaItem.alt || mediaItem.title || "" } />
                                                                        </div>
                                                                        <div className={ `${ rootClass }__media-body` }>
                                                                            <strong>{ mediaItem.title || __( "Generated popup image", "fooconvert" ) }</strong>
                                                                            { mediaItem.prompt && (
                                                                                <p>{ truncateText( mediaItem.prompt ) }</p>
                                                                            ) }
                                                                            <div className={ `${ rootClass }__media-actions` }>
                                                                                <Button
                                                                                    variant="secondary"
                                                                                    onClick={ () => insertMediaIntoDraft( mediaItem ) }
                                                                                    disabled={ !draft || isSavingDraft }
                                                                                >
                                                                                    { __( "Use In Popup", "fooconvert" ) }
                                                                                </Button>
                                                                                { mediaItem.editUrl && (
                                                                                    <Button variant="tertiary" href={ mediaItem.editUrl } icon={ external }>
                                                                                        { __( "Edit", "fooconvert" ) }
                                                                                    </Button>
                                                                                ) }
                                                                                <Button
                                                                                    variant="tertiary"
                                                                                    isDestructive
                                                                                    onClick={ () => deleteMediaItem( mediaItem ) }
                                                                                    disabled={ deletingMediaId === Number( mediaItem.id ) }
                                                                                >
                                                                                    { deletingMediaId === Number( mediaItem.id ) ? __( "Deleting…", "fooconvert" ) : __( "Delete", "fooconvert" ) }
                                                                                </Button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                ) ) }
                                                            </div>
                                                        ) : (
                                                            <p className={ `${ rootClass }__muted-copy` }>
                                                                { draft
                                                                    ? __( "Generate a popup image here, or let the AI create one during chat when image generation is enabled.", "fooconvert" )
                                                                    : __( "Generate a popup draft first, then create matching images for it here.", "fooconvert" ) }
                                                            </p>
                                                        ) }
                                                    </Fragment>
                                                ) : (
                                                    <p className={ `${ rootClass }__muted-copy` }>
                                                        { __( "This user account cannot upload media, so popup image generation and import are unavailable.", "fooconvert" ) }
                                                    </p>
                                                ) }
                                            </CardBody>
                                        </Card>
                                    </div>
                                ) }
                            </div>
                        ) }
                    </TabPanel>
                </CardBody>
            </Card>

            { renderContextModal() }

            { previewOpen && (
                <Modal
                    title={ __( "Live Popup Preview", "fooconvert" ) }
                    onRequestClose={ () => setPreviewOpen( false ) }
                    className={ `${ rootClass }__preview-modal` }
                    shouldCloseOnClickOutside={ true }
                >
                    <div className={ `${ rootClass }__preview-modal-toolbar` }>
                        <Button
                            variant="secondary"
                            onClick={ () => {
                                setPreviewLoading( true );
                            } }
                            href={ previewUrl || undefined }
                            target="_blank"
                        >
                            { __( "Open Preview In New Tab", "fooconvert" ) }
                        </Button>
                        <Button variant="secondary" onClick={ openEditInNewTab } disabled={ !savedPopup?.editUrl }>
                            { __( "Edit Draft", "fooconvert" ) }
                        </Button>
                    </div>

                    <div className={ `${ rootClass }__preview-frame-wrap` }>
                        { previewLoading && (
                            <div className={ `${ rootClass }__preview-loading` }>
                                <Spinner />
                                <span>{ __( "Loading live preview…", "fooconvert" ) }</span>
                            </div>
                        ) }
                        { embeddedPreviewUrl && (
                            <iframe
                                title={ __( "Popup preview", "fooconvert" ) }
                                src={ embeddedPreviewUrl }
                                onLoad={ () => setPreviewLoading( false ) }
                            />
                        ) }
                    </div>
                </Modal>
            ) }
        </div>
    );
};

domReady( () => {
    const container = document.getElementById( "fc-ai-popup-builder-root" );
    if ( !container ) {
        return;
    }

    createRoot( container ).render( <App /> );
} );
