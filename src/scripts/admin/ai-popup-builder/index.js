import apiFetch from "@wordpress/api-fetch";
import domReady from "@wordpress/dom-ready";
import {
    Button,
    Card,
    CardBody,
    CardHeader,
    Flex,
    FlexBlock,
    Notice,
    Spinner,
    TextControl,
    TextareaControl,
} from "@wordpress/components";
import { createRoot, Fragment, startTransition, useEffect, useMemo, useRef, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { Icon, copySmall, external, plusCircleFilled, tip } from "@wordpress/icons";
import { PopupPreview } from "./preview";
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

const isPlainObject = ( value ) => Boolean( value ) && Object.prototype.toString.call( value ) === "[object Object]";

const templatesBySlug = Array.isArray( config?.templates )
    ? config.templates.reduce( ( nextTemplates, template ) => {
        if ( isPlainObject( template ) && typeof template?.slug === "string" && template.slug.length > 0 ) {
            nextTemplates[ template.slug ] = template;
        }

        return nextTemplates;
    }, {} )
    : {};

const countByName = ( blocks, blockName ) => {
    return blocks.filter( block => block?.name === blockName ).length;
};

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
            return sprintf( __( "%ds delay", "fooconvert" ), Number.isFinite( Number( trigger?.delay_seconds ) ) ? Number( trigger.delay_seconds ) : 4 );
        case "scroll_percent":
            return sprintf( __( "%d%% scroll", "fooconvert" ), Number.isFinite( Number( trigger?.scroll_percent ) ) ? Number( trigger.scroll_percent ) : 20 );
        case "immediate":
            return __( "Immediate", "fooconvert" );
        case "exit_intent":
        default:
            return __( "Exit intent", "fooconvert" );
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

const App = () => {
    const [ messages, setMessages ] = useState( [] );
    const [ input, setInput ] = useState( "" );
    const [ draft, setDraft ] = useState( null );
    const [ validation, setValidation ] = useState( null );
    const [ suggestedPrompts, setSuggestedPrompts ] = useState( Array.isArray( config?.starterPrompts ) ? config.starterPrompts : [] );
    const [ generatedMarkup, setGeneratedMarkup ] = useState( "" );
    const [ saveTitle, setSaveTitle ] = useState( "" );
    const [ titleTouched, setTitleTouched ] = useState( false );
    const [ isSending, setSending ] = useState( false );
    const [ isSaving, setSaving ] = useState( false );
    const [ error, setError ] = useState( "" );
    const [ savedPopup, setSavedPopup ] = useState( null );
    const chatEndRef = useRef( null );

    useEffect( () => {
        if ( !draft ) {
            setGeneratedMarkup( "" );
            return;
        }

        try {
            setGeneratedMarkup( serializeDraftToMarkup( draft, templatesBySlug ) );
        } catch ( exception ) {
            setGeneratedMarkup( "" );
        }
    }, [ draft ] );

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

    const conversionRationale = Array.isArray( draft?.conversion_rationale ) ? draft.conversion_rationale.filter( Boolean ) : [];
    const implementationNotes = Array.isArray( draft?.notes ) ? draft.notes.filter( Boolean ) : [];

    const sendPrompt = async( promptText ) => {
        const prompt = String( promptText || "" ).trim();

        if ( prompt.length === 0 || isSending || !config?.aiClientAvailable ) {
            return;
        }

        const nextMessages = [ ...messages, { role: "user", content: prompt } ];

        setMessages( nextMessages );
        setInput( "" );
        setSending( true );
        setError( "" );
        setSavedPopup( null );

        try {
            const response = await apiFetch( {
                path: config?.api?.chatPath || "/fooconvert/v1/ai-popup-builder/chat",
                method: "POST",
                data: {
                    messages: nextMessages,
                    popup_draft: draft || undefined,
                },
            } );

            const assistantMessage = response?.clarifying_question || response?.assistant_message || __( "I prepared a popup direction for you.", "fooconvert" );

            startTransition( () => {
                setMessages( [ ...nextMessages, { role: "assistant", content: assistantMessage } ] );
                setSuggestedPrompts( Array.isArray( response?.suggested_prompts ) ? response.suggested_prompts : [] );
                setDraft( isPlainObject( response?.popup_draft ) ? response.popup_draft : null );
                setValidation( isPlainObject( response?.validation ) ? response.validation : null );
            } );
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
        } catch ( exception ) {
            setError( __( "Could not copy the block HTML to the clipboard.", "fooconvert" ) );
        }
    };

    const savePopup = async() => {
        if ( !draft || !generatedMarkup || isSaving ) {
            return;
        }

        setSaving( true );
        setError( "" );

        try {
            const response = await apiFetch( {
                path: config?.api?.savePath || "/fooconvert/v1/ai-popup-builder/save",
                method: "POST",
                data: {
                    title: saveTitle || draft.title,
                    popup_type: draft.popup_type,
                    post_content: generatedMarkup,
                },
            } );

            setSavedPopup( response );
        } catch ( exception ) {
            setError( exception?.message || __( "The popup could not be saved.", "fooconvert" ) );
        } finally {
            setSaving( false );
        }
    };

    return (
        <div className={ rootClass }>
            <div className={ `${ rootClass }__hero` }>
                <div>
                    <div className={ `${ rootClass }__eyebrow` }>
                        <Icon icon={ tip } />
                        { __( "Experimental AI Popup Builder", "fooconvert" ) }
                    </div>
                    <h1>{ __( "Create high-converting popups in minutes", "fooconvert" ) }</h1>
                    <p>
                        { __( "Describe the offer, audience, tone, and goal. Fooconvert will pull from its templates, supported blocks, and conversion playbook to draft a popup you can save straight into the editor.", "fooconvert" ) }
                    </p>
                </div>
                <div className={ `${ rootClass }__hero-stats` }>
                    <div>
                        <strong>{ Array.isArray( config?.templates ) ? config.templates.length : 0 }</strong>
                        <span>{ __( "bundled templates", "fooconvert" ) }</span>
                    </div>
                    <div>
                        <strong>{ Array.isArray( config?.blockCatalog ) ? config.blockCatalog.length : 0 }</strong>
                        <span>{ __( "supported blocks", "fooconvert" ) }</span>
                    </div>
                    <div>
                        <strong>{ __( "AI + tools", "fooconvert" ) }</strong>
                        <span>{ __( "draft + validation loop", "fooconvert" ) }</span>
                    </div>
                </div>
            </div>

            { !config?.aiClientAvailable && (
                <Notice status="warning" isDismissible={ false }>
                    { __( "The WordPress AI client is not configured on this site yet, so the chat builder is currently unavailable.", "fooconvert" ) }
                </Notice>
            ) }

            { error && (
                <Notice status="error" isDismissible={ true } onRemove={ () => setError( "" ) }>
                    { error }
                </Notice>
            ) }

            { savedPopup?.editUrl && (
                <Notice status="success" isDismissible={ true } onRemove={ () => setSavedPopup( null ) }>
                    <Flex align="center" justify="space-between">
                        <FlexBlock>
                            { __( "Popup saved as a draft. Open it in the editor to fine-tune the block layout or publish it.", "fooconvert" ) }
                        </FlexBlock>
                        <Button variant="primary" href={ savedPopup.editUrl } icon={ external }>
                            { __( "Open In Editor", "fooconvert" ) }
                        </Button>
                    </Flex>
                </Notice>
            ) }

            <div className={ `${ rootClass }__layout` }>
                <Card className={ `${ rootClass }__panel ${ rootClass }__panel--chat` }>
                    <CardHeader>
                        <Flex justify="space-between" align="center">
                            <FlexBlock>
                                <h2>{ __( "Chat Builder", "fooconvert" ) }</h2>
                            </FlexBlock>
                            { isSending && <Spinner /> }
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        <div className={ `${ rootClass }__messages` }>
                            { messages.length === 0 ? (
                                <div className={ `${ rootClass }__empty-state` }>
                                    <p>{ __( "Start with a natural-language brief. Include the goal, audience, offer, popup type if you know it, and any tone or design references.", "fooconvert" ) }</p>
                                    <div className={ `${ rootClass }__prompt-grid` }>
                                        { ( Array.isArray( config?.starterPrompts ) ? config.starterPrompts : [] ).map( prompt => (
                                            <button key={ prompt } type="button" className={ `${ rootClass }__starter-card` } onClick={ () => sendPrompt( prompt ) }>
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
                                disabled={ !config?.aiClientAvailable || isSending }
                                help={ __( "Tip: mention the audience, offer, trigger, and tone. Use Ctrl/Command + Enter to send.", "fooconvert" ) }
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                                onKeyDown={ async event => {
                                    if ( ( event.metaKey || event.ctrlKey ) && event.key === "Enter" ) {
                                        event.preventDefault();
                                        await sendPrompt( input );
                                    }
                                } }
                            />
                            <div className={ `${ rootClass }__composer-actions` }>
                                <div className={ `${ rootClass }__prompt-strip` }>
                                    { suggestedPrompts.map( prompt => (
                                        <PromptChip key={ prompt } label={ prompt } onClick={ () => sendPrompt( prompt ) } disabled={ isSending } />
                                    ) ) }
                                </div>
                                <Button variant="primary" type="submit" icon={ plusCircleFilled } disabled={ isSending || !config?.aiClientAvailable || input.trim().length === 0 }>
                                    { __( "Generate Popup", "fooconvert" ) }
                                </Button>
                            </div>
                        </form>
                    </CardBody>
                </Card>

                <div className={ `${ rootClass }__sidebar` }>
                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <h2>{ __( "Live Preview", "fooconvert" ) }</h2>
                        </CardHeader>
                        <CardBody>
                            <PopupPreview draft={ draft } templatesBySlug={ templatesBySlug } />
                        </CardBody>
                    </Card>

                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <h2>{ __( "Strategy Summary", "fooconvert" ) }</h2>
                        </CardHeader>
                        <CardBody>
                            { draft ? (
                                <div className={ `${ rootClass }__summary` }>
                                    { summaryRows.map( row => (
                                        <div key={ row.label } className={ `${ rootClass }__summary-row` }>
                                            <span>{ row.label }</span>
                                            <strong>{ row.value }</strong>
                                        </div>
                                    ) ) }
                                    { draft?.template_slug && templatesBySlug?.[ draft.template_slug ] && (
                                        <div className={ `${ rootClass }__template-chip` }>
                                            { templatesBySlug[ draft.template_slug ].title }
                                        </div>
                                    ) }
                                </div>
                            ) : (
                                <p>{ __( "When the AI produces a draft, you will see the recommended popup type, offer focus, and conversion direction here.", "fooconvert" ) }</p>
                            ) }
                        </CardBody>
                    </Card>

                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <h2>{ __( "Conversion Checklist", "fooconvert" ) }</h2>
                        </CardHeader>
                        <CardBody>
                            { validation ? (
                                <ConversionChecklist validation={ validation } />
                            ) : (
                                <p>{ __( "Once a draft is generated, Fooconvert scores it for CTA focus, offer clarity, and popup-type fit.", "fooconvert" ) }</p>
                            ) }
                        </CardBody>
                    </Card>

                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <h2>{ __( "AI Guidance", "fooconvert" ) }</h2>
                        </CardHeader>
                        <CardBody>
                            { draft ? (
                                <Fragment>
                                    <GuidanceList title={ __( "Why this should convert", "fooconvert" ) } items={ conversionRationale } />
                                    <GuidanceList title={ __( "Implementation notes", "fooconvert" ) } items={ implementationNotes } />
                                    { conversionRationale.length === 0 && implementationNotes.length === 0 && (
                                        <p>{ __( "Ask the AI to explain the strategy or refine the popup for a different audience, trigger, or tone.", "fooconvert" ) }</p>
                                    ) }
                                </Fragment>
                            ) : (
                                <p>{ __( "The AI can explain the conversion strategy and note anything worth adjusting before you save.", "fooconvert" ) }</p>
                            ) }
                        </CardBody>
                    </Card>

                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <Flex justify="space-between" align="center">
                                <FlexBlock>
                                    <h2>{ __( "Block HTML", "fooconvert" ) }</h2>
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
                                rows={ 10 }
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                        </CardBody>
                    </Card>

                    <Card className={ `${ rootClass }__panel` }>
                        <CardHeader>
                            <h2>{ __( "Save Draft", "fooconvert" ) }</h2>
                        </CardHeader>
                        <CardBody>
                            <TextControl
                                label={ __( "Popup title", "fooconvert" ) }
                                value={ saveTitle }
                                onChange={ value => {
                                    setTitleTouched( true );
                                    setSaveTitle( value );
                                } }
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                            />
                            <div className={ `${ rootClass }__save-actions` }>
                                <Button variant="primary" onClick={ savePopup } disabled={ !draft || !generatedMarkup || isSaving }>
                                    { isSaving ? __( "Saving…", "fooconvert" ) : __( "Save Popup Draft", "fooconvert" ) }
                                </Button>
                                { savedPopup?.editUrl && (
                                    <Button variant="secondary" href={ savedPopup.editUrl } icon={ external }>
                                        { __( "Open In Editor", "fooconvert" ) }
                                    </Button>
                                ) }
                            </div>
                        </CardBody>
                    </Card>
                </div>
            </div>
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
