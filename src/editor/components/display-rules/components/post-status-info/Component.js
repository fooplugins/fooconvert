import { PluginPostStatusInfo } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { published, info } from "@wordpress/icons";
import { Button } from "@wordpress/components";
import { useMemo, useState } from "@wordpress/element";
import classnames from "classnames";

import "./Component.scss";
import { DisplayRulesDropdown } from "../../components";

const rootClass = 'fc--display-rules__post-status-info';

const DisplayRulesPostStatusInfo = () => {

    const [ popoverAnchor, setPopoverAnchor ] = useState( null );
    // Memoize popoverProps to avoid returning a new object every time.
    const popoverProps = useMemo(
        () => ( {
            // Anchor the popover to the middle of the entire row so that it doesn't
            // move around when the label changes.
            anchor: popoverAnchor,
            'aria-label': __( 'Display rules', 'fooconvert' ),
            headerTitle: __( 'Display rules', 'fooconvert' ),
            placement: 'left-start',
            offset: 36,
            shift: true,
        } ),
        [ popoverAnchor ]
    );

    return (
        <PluginPostStatusInfo className={ rootClass }>
            <div className={ `${ rootClass }__label` } ref={ setPopoverAnchor }>{ __( 'Display rules', 'fooconvert' ) }</div>
            <div className={ `${ rootClass }__control` }>
                <DisplayRulesDropdown
                    focusOnMount={ true }
                    popoverProps={ popoverProps }
                    renderToggle={ ( { isOpen, onToggle, compiledRules } ) => (
                        <Button
                            className={ classnames( `${ rootClass }__dropdown-button`, { 'is-not-set': !compiledRules.success } ) }
                            variant="tertiary"
                            size="compact"
                            onClick={ onToggle }
                            aria-expanded={ isOpen }
                            text={ compiledRules.success ? __( 'Set', 'fooconvert' ) : __( 'Not set', 'fooconvert' ) }
                            icon={ compiledRules.success ? published : info }
                        />
                    ) }
                />
            </div>
        </PluginPostStatusInfo>
    );
};

export default DisplayRulesPostStatusInfo;