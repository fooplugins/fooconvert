import { Dropdown } from "@wordpress/components";
import classnames from "classnames";

import "./Component.scss";
import { useDisplayRulesMeta } from "../../hooks";
import compileDisplayRules from "../../utils/compileDisplayRules";
import { DisplayRulesContentControl } from "../content-control";
import { InspectorPopoverHeader } from "../../../experimental";
import { __ } from "@wordpress/i18n";

const rootClass = 'fc--display-rules__dropdown';

const DisplayRulesDropdown = ( props ) => {
    const {
        renderToggle,
        className,
        contentClassName,
        popoverProps = { placement: 'left-start', offset: 40 },
        ...restProps
    } = props;

    const [ rules, setRules ] = useDisplayRulesMeta();
    const compiledRules = compileDisplayRules( rules );

    const _renderToggle = ( { isOpen, onToggle, onClose } ) => renderToggle( {
        isOpen,
        onToggle,
        onClose,
        rules,
        setRules,
        compiledRules
    } );

    return (
        <Dropdown
            className={ classnames( rootClass, className ) }
            contentClassName={ classnames( `${ rootClass }__content`, contentClassName ) }
            popoverProps={ popoverProps }
            renderContent={ ({ onClose }) => (
                <>
                    <InspectorPopoverHeader
                        title={ __( 'Display rules', 'fooconvert' ) }
                        onClose={ onClose }
                    />
                    <DisplayRulesContentControl
                        rules={ rules }
                        setRules={ setRules }
                        compiledRules={ compiledRules }
                        showDescription={ true }
                    />
                </>
            ) }
            renderToggle={ _renderToggle }
            { ...restProps }
        />
    );
};

export default DisplayRulesDropdown;