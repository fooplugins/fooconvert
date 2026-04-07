import { initCoreAdapter, observeVisibilityIds } from "./adapters/core";
import "./subscribers/core";

const initAdapters = () => {
    initCoreAdapter();
};

if ( document.readyState === "loading" ) {
    document.addEventListener( "DOMContentLoaded", initAdapters, { once: true } );
} else {
    initAdapters();
}

export { getEventBus } from "./eventBus";
export { observeVisibilityIds };
export {
    getTriggerSubscriber,
    hasPassthroughTrigger,
    hasTriggerSubscriber,
    onTriggerSubscriberRegistered,
    registerPassthroughTrigger,
    registerTriggerSubscriber,
    subscribeTrigger
} from "./registry";
