import { PluginPrePublishPanel } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { published, info } from "@wordpress/icons";
import { PanelRow } from "@wordpress/components";
import classnames from "classnames";

import "./Component.scss";
import { DisplayRulesContentControl } from "../../components";
import { useDisplayRulesMeta } from "../../hooks";
import compileDisplayRules from "../../utils/compileDisplayRules";

const rootClass = 'fc--display-rules__pre-publish-panel';

const DisplayRulesPrePublishPanel = () => {

    const [ rules, setRules ] = useDisplayRulesMeta();
    const compiledRules = compileDisplayRules( rules );

    const panelTitle = () => {
        const status = compiledRules.success ? __( 'Set', 'fooconvert' ) : __( 'Not set', 'fooconvert' );
        return ( <>{ __( 'Display rules', 'fooconvert' ) }:<span className={ `${ rootClass }__status` }>{ status }</span></> );
    };

    return (
        <PluginPrePublishPanel
            className={ classnames( rootClass, { 'is-not-set': !compiledRules.success } ) }
            title={ panelTitle() }
            initialOpen={ false }
            icon={ compiledRules.success ? published : info }>
            <PanelRow>
                <DisplayRulesContentControl
                    rules={ rules }
                    setRules={ setRules }
                    compiledRules={ compiledRules }
                />
            </PanelRow>
        </PluginPrePublishPanel>
    );
};

export default DisplayRulesPrePublishPanel;