import signUpMetadata from "../../../blocks/sign-up/block.json";
import barMetadata from "../../../widgets/bar/block.json";
import barOpenButtonMetadata from "../../../widgets/bar/editor/blocks/open-button/block.json";
import barContainerMetadata from "../../../widgets/bar/editor/blocks/container/block.json";
import barCloseButtonMetadata from "../../../widgets/bar/editor/blocks/container/blocks/close-button/block.json";
import barContentMetadata from "../../../widgets/bar/editor/blocks/container/blocks/content/block.json";
import flyoutMetadata from "../../../widgets/flyout/block.json";
import flyoutOpenButtonMetadata from "../../../widgets/flyout/editor/blocks/open-button/block.json";
import flyoutContainerMetadata from "../../../widgets/flyout/editor/blocks/container/block.json";
import flyoutCloseButtonMetadata from "../../../widgets/flyout/editor/blocks/container/blocks/close-button/block.json";
import flyoutContentMetadata from "../../../widgets/flyout/editor/blocks/container/blocks/content/block.json";
import popupMetadata from "../../../widgets/popup/block.json";
import popupContainerMetadata from "../../../widgets/popup/editor/blocks/container/block.json";
import popupCloseButtonMetadata from "../../../widgets/popup/editor/blocks/container/blocks/close-button/block.json";
import popupContentMetadata from "../../../widgets/popup/editor/blocks/container/blocks/content/block.json";

export const fooconvertBlockMetadata = [
    popupMetadata,
    popupContainerMetadata,
    popupCloseButtonMetadata,
    popupContentMetadata,
    flyoutMetadata,
    flyoutOpenButtonMetadata,
    flyoutContainerMetadata,
    flyoutCloseButtonMetadata,
    flyoutContentMetadata,
    barMetadata,
    barOpenButtonMetadata,
    barContainerMetadata,
    barCloseButtonMetadata,
    barContentMetadata,
    signUpMetadata,
];

export const isPlainObject = ( value ) => Boolean( value ) && Object.prototype.toString.call( value ) === "[object Object]";

export const cloneDeep = ( value ) => {
    if ( Array.isArray( value ) ) {
        return value.map( cloneDeep );
    }

    if ( isPlainObject( value ) ) {
        return Object.entries( value ).reduce( ( nextValue, [ key, item ] ) => {
            nextValue[ key ] = cloneDeep( item );
            return nextValue;
        }, {} );
    }

    return value;
};

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

export const normalizePopupType = ( value ) => {
    switch ( value ) {
        case "bar":
        case "flyout":
        case "popup":
            return value;
        default:
            return "popup";
    }
};

const getDefaultRootAttributes = ( popupType ) => {
    switch ( normalizePopupType( popupType ) ) {
        case "bar":
            return {
                template: "",
                viewState: "open",
                settings: {
                    position: "bottom",
                    transitions: true,
                },
                openButton: {
                    settings: {
                        hidden: true,
                    },
                },
                closeButton: {
                    settings: {
                        icon: {
                            slug: "default__close-small",
                            size: "24px",
                        },
                    },
                },
                content: {
                    styles: {
                        color: {
                            background: "#111827",
                            text: "#ffffff",
                        },
                        border: {
                            radius: "18px",
                            style: "solid",
                            width: "0px",
                            color: "#111827",
                        },
                        dimensions: {
                            padding: "18px 24px",
                            gap: "16px",
                            margin: "16px",
                        },
                    },
                },
            };
        case "flyout":
            return {
                template: "",
                viewState: "open",
                settings: {
                    transitions: true,
                },
                openButton: {
                    settings: {
                        hidden: true,
                    },
                },
                closeButton: {
                    settings: {
                        icon: {
                            slug: "default__close-small",
                            size: "28px",
                        },
                    },
                },
                content: {
                    styles: {
                        color: {
                            background: "#ffffff",
                            text: "#111827",
                        },
                        border: {
                            radius: "22px",
                            style: "solid",
                            width: "1px",
                            color: "#d6dae1",
                            shadow: "0 20px 48px rgba(15, 23, 42, 0.18)",
                        },
                        dimensions: {
                            padding: "28px",
                            gap: "16px",
                            margin: "18px",
                        },
                        width: "420px",
                    },
                },
            };
        case "popup":
        default:
            return {
                template: "",
                settings: {
                    transitions: true,
                    hideScrollbar: true,
                    maxOnMobile: true,
                },
                closeButton: {
                    settings: {
                        icon: {
                            slug: "default__close-small",
                            size: "32px",
                        },
                    },
                },
                content: {
                    styles: {
                        color: {
                            background: "#ffffff",
                            text: "#111827",
                        },
                        border: {
                            radius: "22px",
                            style: "solid",
                            width: "1px",
                            color: "#d6dae1",
                            shadow: "0 24px 56px rgba(15, 23, 42, 0.2)",
                        },
                        dimensions: {
                            padding: "32px",
                            gap: "18px",
                        },
                        width: "640px",
                    },
                },
            };
    }
};

const buildTriggerConfig = ( trigger = {}, popupType = "popup" ) => {
    const normalizedPopupType = normalizePopupType( popupType );
    const triggerType = [ "immediate", "delay", "exit_intent", "scroll_percent" ].includes( trigger?.type )
        ? trigger.type
        : ( normalizedPopupType === "bar" ? "delay" : ( normalizedPopupType === "flyout" ? "scroll_percent" : "exit_intent" ) );
    const lifetime = [ "page", "session", "visit" ].includes( trigger?.lifetime ) ? trigger.lifetime : "page";
    const frequency = trigger?.frequency === "repeat" ? "repeat" : "once";

    const step = {
        event: "fc.immediate",
        where: {},
    };

    if ( triggerType === "delay" ) {
        step.event = "fc.timer.elapsed";
        step.where = {
            seconds: Number.isFinite( Number( trigger?.delay_seconds ) ) ? Math.max( 0, Number( trigger.delay_seconds ) ) : 4,
        };
    } else if ( triggerType === "exit_intent" ) {
        step.event = "fc.exit_intent";
        step.where = {
            delaySeconds: Number.isFinite( Number( trigger?.delay_seconds ) ) ? Math.max( 0, Number( trigger.delay_seconds ) ) : 5,
        };
    } else if ( triggerType === "scroll_percent" ) {
        step.event = "fc.scroll.percent";
        step.where = {
            percent: Number.isFinite( Number( trigger?.scroll_percent ) ) ? Math.min( 100, Math.max( 1, Number( trigger.scroll_percent ) ) ) : 20,
        };
    }

    return {
        version: 2,
        lifetime,
        frequency: {
            mode: frequency,
            cooldownSeconds: 0,
        },
        steps: [ step ],
    };
};

export const buildRootAttributes = ( draft, templatesBySlug = {} ) => {
    const popupType = normalizePopupType( draft?.popup_type );
    const template = typeof draft?.template_slug === "string" ? templatesBySlug[ draft.template_slug ] : null;

    let rootAttributes = deepMerge( {}, getDefaultRootAttributes( popupType ) );

    if ( template?.attributes ) {
        rootAttributes = deepMerge( rootAttributes, template.attributes );
    }

    if ( isPlainObject( draft?.root_attributes ) ) {
        rootAttributes = deepMerge( rootAttributes, draft.root_attributes );
    }

    rootAttributes.template = typeof draft?.template_slug === "string" ? draft.template_slug : ( rootAttributes.template || "" );
    rootAttributes.settings = deepMerge(
        rootAttributes.settings || {},
        {
            trigger: buildTriggerConfig( draft?.trigger, popupType ),
        }
    );

    if ( popupType !== "popup" ) {
        rootAttributes.viewState = "open";
        rootAttributes.openButton = deepMerge(
            {
                settings: {
                    hidden: true,
                },
            },
            rootAttributes.openButton || {}
        );
    } else {
        delete rootAttributes.openButton;
    }

    return rootAttributes;
};

export const extractListItems = ( attributes ) => {
    if ( Array.isArray( attributes?.items ) && attributes.items.length > 0 ) {
        return attributes.items
            .map( item => String( item || "" ).trim() )
            .filter( Boolean );
    }

    if ( Array.isArray( attributes?.values ) && attributes.values.length > 0 ) {
        return attributes.values
            .map( item => String( item || "" ).trim() )
            .filter( Boolean );
    }

    if ( typeof attributes?.values !== "string" || attributes.values.trim().length === 0 ) {
        return [];
    }

    const matches = [ ...attributes.values.matchAll( /<li\b[^>]*>([\s\S]*?)<\/li>/gi ) ];
    if ( matches.length === 0 ) {
        return [ attributes.values.trim() ];
    }

    return matches
        .map( ( [ , item ] ) => String( item || "" ).trim() )
        .filter( Boolean );
};

export const normalizeDraftBlockAttributes = ( blockName, attributes ) => {
    const nextAttributes = isPlainObject( attributes ) ? cloneDeep( attributes ) : {};

    switch ( blockName ) {
        case "core/list":
            if ( !Array.isArray( nextAttributes.items ) ) {
                nextAttributes.items = extractListItems( nextAttributes );
            }
            return nextAttributes;
        case "core/button":
            if ( typeof nextAttributes?.text !== "string" && typeof nextAttributes?.content === "string" ) {
                nextAttributes.text = nextAttributes.content;
            }
            return nextAttributes;
        case "fc/sign-up":
            nextAttributes.settings = isPlainObject( nextAttributes.settings ) ? nextAttributes.settings : {};
            nextAttributes.inputs = isPlainObject( nextAttributes.inputs ) ? nextAttributes.inputs : {};
            nextAttributes.inputs.settings = isPlainObject( nextAttributes.inputs.settings ) ? nextAttributes.inputs.settings : {};
            nextAttributes.button = isPlainObject( nextAttributes.button ) ? nextAttributes.button : {};
            nextAttributes.button.settings = isPlainObject( nextAttributes.button.settings ) ? nextAttributes.button.settings : {};

            if ( typeof nextAttributes.buttonText === "string" && typeof nextAttributes.button.settings.text !== "string" ) {
                nextAttributes.button.settings.text = nextAttributes.buttonText;
            }

            if ( typeof nextAttributes.successMessage === "string" && typeof nextAttributes.settings.successMessage !== "string" ) {
                nextAttributes.settings.successMessage = nextAttributes.successMessage;
            }

            if ( typeof nextAttributes.closeOnSuccess === "boolean" && typeof nextAttributes.settings.closeOnSuccess !== "boolean" ) {
                nextAttributes.settings.closeOnSuccess = nextAttributes.closeOnSuccess;
            }

            if ( typeof nextAttributes.emailOnly === "boolean" && typeof nextAttributes.inputs.settings.emailOnly !== "boolean" ) {
                nextAttributes.inputs.settings.emailOnly = nextAttributes.emailOnly;
            }

            if ( typeof nextAttributes.emailPlaceholder === "string" && typeof nextAttributes.inputs.settings.emailPlaceholder !== "string" ) {
                nextAttributes.inputs.settings.emailPlaceholder = nextAttributes.emailPlaceholder;
            }

            if ( typeof nextAttributes.namePlaceholder === "string" && typeof nextAttributes.inputs.settings.namePlaceholder !== "string" ) {
                nextAttributes.inputs.settings.namePlaceholder = nextAttributes.namePlaceholder;
            }

            return nextAttributes;
        default:
            return nextAttributes;
    }
};

export const flattenBlocks = ( blocks = [] ) => {
    return blocks.reduce( ( nextBlocks, block ) => {
        if ( !isPlainObject( block ) ) {
            return nextBlocks;
        }

        const childBlocks = Array.isArray( block.inner_blocks ) ? flattenBlocks( block.inner_blocks ) : [];
        return [ ...nextBlocks, block, ...childBlocks ];
    }, [] );
};
