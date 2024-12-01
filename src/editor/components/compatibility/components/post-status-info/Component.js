import { PluginPostStatusInfo } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { published, info } from "@wordpress/icons";
import { Button } from "@wordpress/components";
import { useMemo, useState } from "@wordpress/element";
import classnames from "classnames";

import "./Component.scss";
import { CompatibilityDropdown } from "../dropdown";
import { getCompatibilityStatus } from "../../utils";

const rootClass = 'fc--compatibility__post-status-info';

const CompatibilityPostStatusInfo = () => {

    const [ popoverAnchor, setPopoverAnchor ] = useState( null );
    // Memoize popoverProps to avoid returning a new object every time.
    const popoverProps = useMemo(
        () => ( {
            // Anchor the popover to the middle of the entire row so that it doesn't
            // move around when the label changes.
            anchor: popoverAnchor,
            'aria-label': __( 'Compatibility', 'fooconvert' ),
            headerTitle: __( 'Compatibility', 'fooconvert' ),
            placement: 'left-start',
            offset: 36,
            shift: true,
        } ),
        [ popoverAnchor ]
    );

    return (
        <PluginPostStatusInfo className={ rootClass }>
            <div className={ `${ rootClass }__label` }
                 ref={ setPopoverAnchor }>{ __( 'Compatibility', 'fooconvert' ) }</div>
            <div className={ `${ rootClass }__control` }>
                <CompatibilityDropdown
                    focusOnMount={ true }
                    popoverProps={ popoverProps }
                    renderToggle={ ( { isOpen, onToggle, compatibility } ) => {
                        const { value, text, icon } = getCompatibilityStatus( compatibility );
                        return (
                            <Button
                                className={ classnames( `${ rootClass }__dropdown-button`, `is-${ value }` ) }
                                variant="tertiary"
                                size="compact"
                                onClick={ onToggle }
                                aria-expanded={ isOpen }
                                text={ text }
                                icon={ icon }
                            />
                        );
                    } }
                />
            </div>
        </PluginPostStatusInfo>
    );
};

export default CompatibilityPostStatusInfo;