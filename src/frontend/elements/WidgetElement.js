import CustomElement from "./CustomElement";
import { logEvent } from "../utils";
import getParentWidgetElement from "../utils/getParentWidgetElement";

class WidgetElement extends CustomElement {

    constructor() {
        super();
        this.log = this.log.bind( this );
    }

    /**
     *
     * @param {string} type
     * @param {object} [data]
     * @returns {Promise<LogEventResult>}
     */
    log( type, data ) {
        if ( !this.isConfigurationInitialized ) {
            this.initializeConfiguration();
        }
        const { postId, postType, template = '' } = this.config;
        return logEvent( postId, postType, template, type, data );
    }
}

export default WidgetElement;