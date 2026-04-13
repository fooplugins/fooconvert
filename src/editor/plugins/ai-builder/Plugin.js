import { useSelect } from "@wordpress/data";
import { ExternalLink, Flex, FlexBlock } from "@wordpress/components";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { useMemo } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";

import editorData from "./editorData";
import {
    formatAiBuilderDate,
    hasAiBuilderMetadata,
    normalizeAiBuilderMetadata,
    truncateAiBuilderText,
} from "./utils";
import "./Plugin.scss";

const rootClass = "fc--ai-builder-panel";

const TranscriptMessage = ( { role, content } ) => (
    <div className={ `${ rootClass }__message ${ rootClass }__message--${ role }` }>
        <div className={ `${ rootClass }__message-label` }>
            { role === "assistant" ? __( "AI strategist", "fooconvert" ) : __( "You", "fooconvert" ) }
        </div>
        <div className={ `${ rootClass }__message-body` }>{ truncateAiBuilderText( content, 220 ) }</div>
    </div>
);

const GuidanceList = ( { title, items } ) => {
    if ( !Array.isArray( items ) || items.length === 0 ) {
        return null;
    }

    return (
        <div className={ `${ rootClass }__section` }>
            <h3>{ title }</h3>
            <ul className={ `${ rootClass }__list` }>
                { items.map( item => (
                    <li key={ item }>{ item }</li>
                ) ) }
            </ul>
        </div>
    );
};

const MediaItemCard = ( { item } ) => (
    <div className={ `${ rootClass }__media-item` }>
        { item.previewUrl && (
            <div className={ `${ rootClass }__media-preview` }>
                <img src={ item.previewUrl } alt={ item.alt || item.title || "" } />
            </div>
        ) }
        <div className={ `${ rootClass }__media-copy` }>
            <strong>{ item.title || __( "Generated popup image", "fooconvert" ) }</strong>
            { item.prompt && <p>{ truncateAiBuilderText( item.prompt ) }</p> }
            { item.editUrl && <ExternalLink href={ item.editUrl }>{ __( "Edit media", "fooconvert" ) }</ExternalLink> }
        </div>
    </div>
);

const AiBuilderPlugin = () => {
    const { currentPostType, metadataValue } = useSelect( select => {
        const editor = select( editorStore );
        const meta = editor?.getEditedPostAttribute( "meta" ) || {};

        return {
            currentPostType: editor?.getCurrentPostType() || "",
            metadataValue: meta?.[ editorData.meta.key ],
        };
    }, [] );

    const metadata = useMemo( () => normalizeAiBuilderMetadata( metadataValue ), [ metadataValue ] );

    if ( currentPostType !== "fc-popup" || !hasAiBuilderMetadata( metadata ) ) {
        return null;
    }

    const response = metadata.response;
    const popupDraft = response?.popup_draft;
    const validation = response?.validation;
    const formattedSavedAt = formatAiBuilderDate( metadata.saved_at );
    const transcript = metadata.messages.slice( -4 );
    const summaryRows = [
        popupDraft?.popup_type ? {
            label: __( "Type", "fooconvert" ),
            value: editorData?.labels?.[ popupDraft.popup_type ] || popupDraft.popup_type,
        } : null,
        popupDraft?.goal ? {
            label: __( "Goal", "fooconvert" ),
            value: popupDraft.goal,
        } : null,
        popupDraft?.audience ? {
            label: __( "Audience", "fooconvert" ),
            value: popupDraft.audience,
        } : null,
        popupDraft?.offer ? {
            label: __( "Offer", "fooconvert" ),
            value: popupDraft.offer,
        } : null,
        popupDraft?.template_slug ? {
            label: __( "Template", "fooconvert" ),
            value: popupDraft.template_slug,
        } : null,
    ].filter( Boolean );

    return (
        <PluginDocumentSettingPanel name="fc-ai-builder" title={ __( "AI Builder", "fooconvert" ) } className={ rootClass }>
            <div className={ `${ rootClass }__stack` }>
                <div className={ `${ rootClass }__section` }>
                    <Flex align="flex-start" justify="space-between">
                        <FlexBlock>
                            <strong>{ __( "Generated with AI Popup Builder", "fooconvert" ) }</strong>
                            { formattedSavedAt && (
                                <p className={ `${ rootClass }__muted` }>
                                    { sprintf( __( "Saved %s", "fooconvert" ), formattedSavedAt ) }
                                </p>
                            ) }
                        </FlexBlock>
                        { editorData?.builderUrl && (
                            <ExternalLink href={ editorData.builderUrl }>{ __( "Open builder", "fooconvert" ) }</ExternalLink>
                        ) }
                    </Flex>
                </div>

                { validation?.score !== null && validation?.score !== undefined && (
                    <div className={ `${ rootClass }__score` }>
                        <span>{ __( "Conversion score", "fooconvert" ) }</span>
                        <strong>{ `${ validation.score }/100` }</strong>
                    </div>
                ) }

                { summaryRows.length > 0 && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Saved strategy", "fooconvert" ) }</h3>
                        <dl className={ `${ rootClass }__summary` }>
                            { summaryRows.map( row => (
                                <div key={ row.label } className={ `${ rootClass }__summary-row` }>
                                    <dt>{ row.label }</dt>
                                    <dd>{ row.value }</dd>
                                </div>
                            ) ) }
                        </dl>
                    </div>
                ) }

                { response?.assistant_message && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Builder summary", "fooconvert" ) }</h3>
                        <p>{ response.assistant_message }</p>
                    </div>
                ) }

                { response?.clarifying_question && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Clarifying question", "fooconvert" ) }</h3>
                        <p>{ response.clarifying_question }</p>
                    </div>
                ) }

                <GuidanceList title={ __( "Strengths", "fooconvert" ) } items={ validation?.strengths } />
                <GuidanceList title={ __( "Watchouts", "fooconvert" ) } items={ validation?.warnings } />
                <GuidanceList title={ __( "Suggestions", "fooconvert" ) } items={ validation?.suggestions } />

                { transcript.length > 0 && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Recent transcript", "fooconvert" ) }</h3>
                        <div className={ `${ rootClass }__transcript` }>
                            { transcript.map( ( message, index ) => (
                                <TranscriptMessage key={ `${ message.role }-${ index }` } role={ message.role } content={ message.content } />
                            ) ) }
                        </div>
                    </div>
                ) }

                { response?.suggested_prompts?.length > 0 && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Suggested next prompts", "fooconvert" ) }</h3>
                        <div className={ `${ rootClass }__prompt-list` }>
                            { response.suggested_prompts.map( prompt => (
                                <span key={ prompt } className={ `${ rootClass }__prompt-chip` }>
                                    { prompt }
                                </span>
                            ) ) }
                        </div>
                    </div>
                ) }

                { response?.media_items?.length > 0 && (
                    <div className={ `${ rootClass }__section` }>
                        <h3>{ __( "Generated media", "fooconvert" ) }</h3>
                        <div className={ `${ rootClass }__media-grid` }>
                            { response.media_items.map( item => (
                                <MediaItemCard key={ `${ item.id || item.url }` } item={ item } />
                            ) ) }
                        </div>
                    </div>
                ) }
            </div>
        </PluginDocumentSettingPanel>
    );
};

export default AiBuilderPlugin;
