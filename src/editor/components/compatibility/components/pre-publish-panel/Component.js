import { PluginPrePublishPanel } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { PanelRow } from "@wordpress/components";
import classnames from "classnames";

import "./Component.scss";
import { CompatibilityContentControl } from "../../components";
import { useCompatibilityMeta } from "../../hooks";
import { getCompatibilityStatus } from "../../utils";
import { Icon } from "@wordpress/icons";

const rootClass = 'fc--compatibility__pre-publish-panel';

const CompatibilityPrePublishPanel = () => {

    const [ compatibility, setCompatibility ] = useCompatibilityMeta();

    const { value, text, icon } = getCompatibilityStatus( compatibility );

    const renderTitle = () => (
        <span className={ `${ rootClass }__title` }>
            <span className={ `${ rootClass }__text` }>
                { __( 'Compatibility', 'fooconvert' ) }:
            </span>
            <span className={ `${ rootClass }__status` }>
                { icon && (
                    <Icon
                        className={ `${ rootClass }__status-icon` }
                        icon={ icon }
                    />
                ) }
                <span className={ `${ rootClass }__status-text` }>{ text }</span>
            </span>
        </span>
    );

    return (
        <PluginPrePublishPanel
            className={ classnames( rootClass, `is-${ value }` ) }
            title={ renderTitle() }
            initialOpen={ false }
            icon={ icon }>
            <PanelRow>
                <CompatibilityContentControl
                    compatibility={ compatibility }
                    setCompatibility={ setCompatibility }
                />
            </PanelRow>
        </PluginPrePublishPanel>
    );
};

export default CompatibilityPrePublishPanel;