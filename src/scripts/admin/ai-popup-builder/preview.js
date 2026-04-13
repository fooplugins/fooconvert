import { __ } from "@wordpress/i18n";
import { buildRootAttributes, normalizePopupType } from "./serializer";
import { normalizeDraftBlockAttributes } from "./serializer-support";

const isPlainObject = ( value ) => Boolean( value ) && Object.prototype.toString.call( value ) === "[object Object]";

const getStyleValue = ( source, ...keys ) => {
    return keys.reduce( ( value, key ) => {
        if ( !isPlainObject( value ) ) {
            return undefined;
        }

        return value[ key ];
    }, source );
};

const getContentCardStyle = ( rootAttributes ) => {
    const styles = getStyleValue( rootAttributes, "content", "styles" ) || {};
    const dimensions = styles?.dimensions || {};
    const border = styles?.border || {};
    const color = styles?.color || {};

    return {
        background: color?.background || "#ffffff",
        color: color?.text || "#111827",
        width: styles?.width || undefined,
        padding: dimensions?.padding || "32px",
        gap: dimensions?.gap || "16px",
        borderRadius: border?.radius || "22px",
        borderStyle: border?.style || "solid",
        borderColor: border?.color || "transparent",
        borderWidth: border?.width || "0px",
        boxShadow: border?.shadow || "none",
    };
};

const getFrameStyle = ( popupType, rootAttributes ) => {
    const contentStyles = getContentCardStyle( rootAttributes );
    const margin = getStyleValue( rootAttributes, "content", "styles", "dimensions", "margin" );

    if ( popupType === "bar" ) {
        return {
            alignItems: "stretch",
            justifyContent: "flex-end",
            padding: "24px",
            contentWidth: "100%",
            contentMargin: margin || "0",
            contentStyles,
        };
    }

    if ( popupType === "flyout" ) {
        return {
            alignItems: "flex-end",
            justifyContent: "center",
            padding: "28px",
            contentWidth: contentStyles.width || "420px",
            contentMargin: margin || "0",
            contentStyles,
        };
    }

    return {
        alignItems: "center",
        justifyContent: "center",
        padding: "32px",
        contentWidth: contentStyles.width || "640px",
        contentMargin: margin || "0",
        contentStyles,
    };
};

const renderHtml = ( tagName, className, html, style = {} ) => {
    const TagName = tagName;

    return (
        <TagName
            className={ className }
            style={ style }
            dangerouslySetInnerHTML={ {
                __html: html || "",
            } }
        />
    );
};

const renderSignupPreview = ( block ) => {
    const inputsSettings = getStyleValue( block, "attributes", "inputs", "settings" ) || {};
    const buttonSettings = getStyleValue( block, "attributes", "button", "settings" ) || {};
    const emailOnly = Boolean( inputsSettings?.emailOnly );

    return (
        <form className="fc-ai-popup-builder__signup-preview" onSubmit={ event => event.preventDefault() }>
            { !emailOnly && (
                <input
                    type="text"
                    value=""
                    readOnly
                    placeholder={ inputsSettings?.namePlaceholder || __( "Your name", "fooconvert" ) }
                />
            ) }
            <input
                type="email"
                value=""
                readOnly
                placeholder={ inputsSettings?.emailPlaceholder || __( "Enter your email", "fooconvert" ) }
            />
            <button type="submit">
                { buttonSettings?.text || __( "Submit", "fooconvert" ) }
            </button>
        </form>
    );
};

const renderPreviewBlock = ( block ) => {
    if ( !isPlainObject( block ) ) {
        return null;
    }

    const attributes = normalizeDraftBlockAttributes( block.name, block?.attributes );
    const children = Array.isArray( block?.inner_blocks ) ? block.inner_blocks.map( renderPreviewBlock ).filter( Boolean ) : [];

    switch ( block.name ) {
        case "core/heading": {
            const level = Number.isFinite( Number( attributes?.level ) ) ? Math.min( 6, Math.max( 1, Number( attributes.level ) ) ) : 2;
            const style = {
                textAlign: attributes?.textAlign || undefined,
            };
            return renderHtml( `h${ level }`, "fc-ai-popup-builder__preview-heading", attributes?.content || "", style );
        }
        case "core/paragraph":
            return renderHtml(
                "p",
                "fc-ai-popup-builder__preview-paragraph",
                attributes?.content || "",
                {
                    textAlign: attributes?.align || undefined,
                }
            );
        case "core/list":
            return (
                <ul
                    className="fc-ai-popup-builder__preview-list"
                    dangerouslySetInnerHTML={ {
                        __html: attributes?.values || ( Array.isArray( attributes?.items ) ? attributes.items.map( item => `<li>${ item }</li>` ).join( "" ) : "" ),
                    } }
                />
            );
        case "core/buttons":
            return (
                <div className="fc-ai-popup-builder__preview-buttons">
                    { children }
                </div>
            );
        case "core/button":
            return (
                <a className="fc-ai-popup-builder__preview-button" href={ attributes?.url || "#" }>
                    { attributes?.text || attributes?.content || __( "Call to action", "fooconvert" ) }
                </a>
            );
        case "core/group":
            return (
                <div className="fc-ai-popup-builder__preview-group">
                    { children }
                </div>
            );
        case "core/columns":
            return (
                <div className="fc-ai-popup-builder__preview-columns">
                    { children }
                </div>
            );
        case "core/column":
            return (
                <div className="fc-ai-popup-builder__preview-column">
                    { children }
                </div>
            );
        case "core/image":
            return (
                <div className="fc-ai-popup-builder__preview-image">
                    { attributes?.url ? (
                        <img src={ attributes.url } alt={ attributes?.alt || "" } />
                    ) : (
                        <div className="fc-ai-popup-builder__preview-image-placeholder">
                            { __( "Image placeholder", "fooconvert" ) }
                        </div>
                    ) }
                </div>
            );
        case "core/separator":
            return <hr className="fc-ai-popup-builder__preview-separator" />;
        case "core/spacer":
            return <div aria-hidden="true" style={ { height: attributes?.height || "24px" } } />;
        case "fc/sign-up":
            return renderSignupPreview( block );
        default:
            return null;
    }
};

export const PopupPreview = ( { draft, templatesBySlug } ) => {
    if ( !draft ) {
        return (
            <div className="fc-ai-popup-builder__preview-empty">
                <p>{ __( "Your popup preview will appear here as soon as the AI produces a draft.", "fooconvert" ) }</p>
            </div>
        );
    }

    const popupType = normalizePopupType( draft?.popup_type );
    const rootAttributes = buildRootAttributes( draft, templatesBySlug );
    const frameStyle = getFrameStyle( popupType, rootAttributes );
    const contentStyles = frameStyle.contentStyles;

    return (
        <div className={ `fc-ai-popup-builder__preview fc-ai-popup-builder__preview--${ popupType }` }>
            <div
                className="fc-ai-popup-builder__preview-stage"
                style={ {
                    alignItems: frameStyle.alignItems,
                    justifyContent: frameStyle.justifyContent,
                    padding: frameStyle.padding,
                } }
            >
                <div
                    className="fc-ai-popup-builder__preview-card"
                    style={ {
                        width: frameStyle.contentWidth,
                        margin: frameStyle.contentMargin,
                        background: contentStyles.background,
                        color: contentStyles.color,
                        padding: contentStyles.padding,
                        gap: contentStyles.gap,
                        borderRadius: contentStyles.borderRadius,
                        borderStyle: contentStyles.borderStyle,
                        borderColor: contentStyles.borderColor,
                        borderWidth: contentStyles.borderWidth,
                        boxShadow: contentStyles.boxShadow,
                    } }
                >
                    <button type="button" className="fc-ai-popup-builder__preview-close" aria-label={ __( "Close", "fooconvert" ) }>
                        ×
                    </button>
                    { Array.isArray( draft?.content_blocks ) && draft.content_blocks.map( renderPreviewBlock ) }
                </div>
            </div>
        </div>
    );
};
