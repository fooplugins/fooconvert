import apiFetch from "@wordpress/api-fetch";
import domReady from "@wordpress/dom-ready";
import { Button, Modal, Notice } from "@wordpress/components";
import { createRoot, useState } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { clone, isPlainObject } from "@steveush/utils";

import { DisplayRulesLocationsControl } from "../../../editor/components/display-rules/components/locations-control";
import { DisplayRulesRolesControl } from "../../../editor/components/display-rules/components/roles-control";
import compileDisplayRules from "../../../editor/components/display-rules/utils/compileDisplayRules";
import getGroupedSelectOption from "../../../editor/components/grouped-select-control/utils/getGroupedSelectOption";

import "./index.scss";

const rootClass = "fc-display-rules-list";
const editorData = window.FC_DISPLAY_RULES || {};

const defaultRules = () => clone( editorData?.meta?.defaults ?? {
    location: [],
    exclude: [],
    users: [ "general:all_users" ],
} );

const normalizeRules = ( value ) => {
    const defaults = defaultRules();
    const source = isPlainObject( value ) ? value : {};

    return {
        location: Array.isArray( source.location ) ? source.location : defaults.location,
        exclude: Array.isArray( source.exclude ) ? source.exclude : defaults.exclude,
        users: Array.isArray( source.users ) ? source.users : defaults.users,
    };
};

const summarizeLabels = ( labels, emptyLabel ) => {
    const values = Array.from(
        new Set(
            labels
                .filter( label => typeof label === "string" && label.trim().length > 0 )
                .map( label => label.trim() )
        )
    );

    if ( values.length === 0 ) {
        return emptyLabel;
    }

    const visible = values.slice( 0, 2 );
    const remaining = values.length - visible.length;
    let summary = visible.join( ", " );

    if ( remaining > 0 ) {
        summary += sprintf( __( " +%d more", "fooconvert" ), remaining );
    }

    return summary;
};

const summarizeLocations = ( items, options, emptyLabel ) => {
    const labels = items.reduce( ( result, item ) => {
        const type = item?.type ?? "";
        if ( typeof type !== "string" || type.trim().length === 0 ) {
            return result;
        }

        const option = getGroupedSelectOption( options, type );
        const label = option?.label ?? type;

        if ( type.startsWith( "specific:" ) ) {
            const dataLabels = Array.isArray( item?.data )
                ? item.data
                    .map( dataItem => dataItem?.label )
                    .filter( dataLabel => typeof dataLabel === "string" && dataLabel.trim().length > 0 )
                : [];

            if ( dataLabels.length > 0 ) {
                result.push( `${ label }: ${ summarizeLabels( dataLabels, "" ) }` );
                return result;
            }
        }

        result.push( label );
        return result;
    }, [] );

    return summarizeLabels( labels, emptyLabel );
};

const summarizeUsers = ( items, options, emptyLabel ) => {
    const labels = items.reduce( ( result, item ) => {
        if ( typeof item !== "string" || item.trim().length === 0 ) {
            return result;
        }

        const option = getGroupedSelectOption( options, item );
        result.push( option?.label ?? item );
        return result;
    }, [] );

    return summarizeLabels( labels, emptyLabel );
};

const getSummary = ( rules ) => {
    const compiled = compileDisplayRules( rules );
    const hasAllUsers = compiled.users.length === 1 && compiled.users[0] === "general:all_users";

    return {
        location: summarizeLocations(
            compiled.location,
            editorData?.location ?? [],
            __( "Not set", "fooconvert" )
        ),
        exclude: summarizeLocations(
            compiled.exclude,
            editorData?.exclude ?? [],
            __( "None", "fooconvert" )
        ),
        users: summarizeUsers(
            compiled.users,
            editorData?.users ?? [],
            __( "Not set", "fooconvert" )
        ),
        showExclude: compiled.exclude.length > 0,
        showUsers: !hasAllUsers,
        isNotSet: compiled.location.length === 0
            && compiled.exclude.length === 0
            && ( hasAllUsers || compiled.users.length === 0 ),
        reasons: compiled.reasons,
    };
};

const DisplayRulesListApp = ( { config } ) => {
    const postId = Number.parseInt( config?.postId, 10 );
    const postTitle = typeof config?.postTitle === "string" ? config.postTitle : "";
    const canEdit = Boolean( config?.canEdit );
    const showSummary = config?.showSummary !== false;
    const lockedMessage = typeof config?.lockedMessage === "string" ? config.lockedMessage : "";
    /* translators: %s is the popup title shown in the list table. */
    const editDisplayRulesLabelText = __( "Edit display rules for %s", "fooconvert" );
    const editDisplayRulesLabel = sprintf(
        editDisplayRulesLabelText,
        postTitle || __( "this popup", "fooconvert" )
    );
    /* translators: %s is the popup title shown in the modal header. */
    const modalTitleText = __( "Display Rules: %s", "fooconvert" );
    const modalTitle = sprintf(
        modalTitleText,
        postTitle || __( "Popup", "fooconvert" )
    );

    const [ rules, setRules ] = useState( () => normalizeRules( config?.rules ) );
    const [ draftRules, setDraftRules ] = useState( () => normalizeRules( config?.rules ) );
    const [ isOpen, setOpen ] = useState( false );
    const [ isSaving, setSaving ] = useState( false );
    const [ error, setError ] = useState( "" );

    const summary = getSummary( rules );
    const draftSummary = getSummary( draftRules );
    const isDirty = JSON.stringify( rules ) !== JSON.stringify( draftRules );

    const openModal = () => {
        setDraftRules( clone( rules ) );
        setError( "" );
        setOpen( true );
    };

    const closeModal = () => {
        if ( isSaving ) {
            return;
        }

        setDraftRules( clone( rules ) );
        setError( "" );
        setOpen( false );
    };

    const saveRules = async () => {
        if ( !Number.isInteger( postId ) || postId <= 0 ) {
            return;
        }

        const nextRules = normalizeRules( draftRules );
        setSaving( true );
        setError( "" );

        try {
            await apiFetch( {
                path: `/wp/v2/${ editorData?.postType ?? "fc-popup" }/${ postId }`,
                method: "POST",
                data: {
                    meta: {
                        [ editorData?.meta?.key ]: nextRules,
                    },
                },
            } );

            setRules( nextRules );
            setDraftRules( clone( nextRules ) );
            setOpen( false );
        } catch ( exception ) {
            setError(
                exception?.message || __( "Could not save display rules.", "fooconvert" )
            );
        } finally {
            setSaving( false );
        }
    };

    const summaryRows = [
        {
            key: "location",
            label: __( "Show on", "fooconvert" ),
            value: summary.location,
            isVisible: true,
        },
        {
            key: "exclude",
            label: __( "Hide from", "fooconvert" ),
            value: summary.exclude,
            isVisible: summary.showExclude,
        },
        {
            key: "users",
            label: __( "Users", "fooconvert" ),
            value: summary.users,
            isVisible: summary.showUsers,
        },
    ].filter( ( row ) => row.isVisible );

    const renderSummary = () => (
        <div className={ `${ rootClass }__summary` }>
            { summary.isNotSet ? (
                <div className={ `${ rootClass }__summary-empty` }>
                    <span className={ `${ rootClass }__summary-empty-icon dashicons dashicons-warning` } aria-hidden="true"></span>
                    <span className={ `${ rootClass }__summary-empty-text` }>{ __( "Not set", "fooconvert" ) }</span>
                </div>
            ) : (
                summaryRows.map( ( row ) => (
                    <div key={ row.key } className={ `${ rootClass }__summary-row` }>
                        <span className={ `${ rootClass }__summary-label` }>{ row.label }</span>
                        <span className={ `${ rootClass }__summary-value` }>{ row.value }</span>
                    </div>
                ) )
            ) }
        </div>
    );

    return (
        <div className={ rootClass }>
            { canEdit ? (
                <button
                    type="button"
                    className={ `${ rootClass }__summary-button` }
                    onClick={ openModal }
                    aria-label={ editDisplayRulesLabel }
                >
                    { renderSummary() }
                    <span className={ `${ rootClass }__summary-action` }>
                        { __( "Edit display rules", "fooconvert" ) }
                    </span>
                </button>
            ) : showSummary ? (
                <div className={ `${ rootClass }__summary-card` }>
                    { renderSummary() }
                </div>
            ) : lockedMessage ? (
                <p className={ `${ rootClass }__locked-message` }>{ lockedMessage }</p>
            ) : null }

            { !canEdit && showSummary && lockedMessage && (
                <p className={ `${ rootClass }__locked-message` }>{ lockedMessage }</p>
            ) }

            { isOpen && canEdit && (
                <Modal
                    className={ `${ rootClass }__modal` }
                    title={ modalTitle }
                    onRequestClose={ closeModal }
                >
                    <div className={ `${ rootClass }__modal-body` }>
                        <p className={ `${ rootClass }__modal-description` }>
                            { __( "Update where this popup appears and who can see it without leaving the listing.", "fooconvert" ) }
                        </p>

                        { draftSummary.reasons.length > 0 && (
                            <Notice status="warning" isDismissible={ false }>
                                { draftSummary.reasons.map( ( reason ) => (
                                    <p key={ reason.source }>{ reason.message }</p>
                                ) ) }
                            </Notice>
                        ) }

                        { error && (
                            <Notice status="error" isDismissible={ false }>
                                <p>{ error }</p>
                            </Notice>
                        ) }

                        <div className={ `${ rootClass }__sections` }>
                            <div className={ `${ rootClass }__section` }>
                                <DisplayRulesLocationsControl
                                    label={ __( "Display on", "fooconvert" ) }
                                    help={ __( "Choose where this popup should appear.", "fooconvert" ) }
                                    options={ editorData?.location ?? [] }
                                    items={ draftRules.location }
                                    onChange={ ( value ) => setDraftRules( { ...draftRules, location: value } ) }
                                    noItemsLabel={ __( "No display locations added.", "fooconvert" ) }
                                    addItemLabel={ __( "Add display location", "fooconvert" ) }
                                    removeItemLabel={ __( "Remove display location", "fooconvert" ) }
                                />
                            </div>

                            <div className={ `${ rootClass }__section` }>
                                <DisplayRulesLocationsControl
                                    label={ __( "Exclude from", "fooconvert" ) }
                                    help={ __( "Choose where this popup should stay hidden.", "fooconvert" ) }
                                    options={ editorData?.exclude ?? [] }
                                    items={ draftRules.exclude }
                                    onChange={ ( value ) => setDraftRules( { ...draftRules, exclude: value } ) }
                                    noItemsLabel={ __( "No exclusions added.", "fooconvert" ) }
                                    addItemLabel={ __( "Add exclusion", "fooconvert" ) }
                                    removeItemLabel={ __( "Remove exclusion", "fooconvert" ) }
                                />
                            </div>

                            <div className={ `${ rootClass }__section` }>
                                <DisplayRulesRolesControl
                                    label={ __( "Users", "fooconvert" ) }
                                    help={ __( "Choose which visitors can see this popup.", "fooconvert" ) }
                                    options={ editorData?.users ?? [] }
                                    items={ draftRules.users }
                                    onChange={ ( value ) => setDraftRules( { ...draftRules, users: value } ) }
                                    noItemsLabel={ __( "No audience rules added.", "fooconvert" ) }
                                    addItemLabel={ __( "Add audience rule", "fooconvert" ) }
                                    removeItemLabel={ __( "Remove audience rule", "fooconvert" ) }
                                />
                            </div>
                        </div>
                    </div>

                    <div className={ `${ rootClass }__modal-footer` }>
                        <Button variant="secondary" onClick={ closeModal } disabled={ isSaving }>
                            { __( "Cancel", "fooconvert" ) }
                        </Button>
                        <Button
                            variant="primary"
                            onClick={ saveRules }
                            disabled={ !isDirty || isSaving }
                            isBusy={ isSaving }
                        >
                            { __( "Save", "fooconvert" ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </div>
    );
};

const parseConfig = ( node ) => {
    try {
        const config = JSON.parse( node.dataset.config ?? "{}" );
        return isPlainObject( config ) ? config : null;
    } catch ( exception ) {
        console.error( "Invalid display rules config.", exception );
        return null;
    }
};

domReady( () => {
    document.querySelectorAll( ".fc-display-rules-list__app" ).forEach( ( node ) => {
        const config = parseConfig( node );
        if ( !config ) {
            return;
        }

        createRoot( node ).render( <DisplayRulesListApp config={ config } /> );
    } );
} );
