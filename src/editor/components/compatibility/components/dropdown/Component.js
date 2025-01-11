import { Dropdown } from "@wordpress/components";
import classnames from "classnames";

import "./Component.scss";
import { useCompatibilityMeta } from "../../hooks";
import { CompatibilityContentControl } from "../content-control";
import { InspectorPopoverHeader } from "../../../experimental";
import { __ } from "@wordpress/i18n";

const rootClass = 'fc--compatibility__dropdown';

const CompatibilityDropdown = ( props ) => {
    const {
        renderToggle,
        className,
        contentClassName,
        popoverProps = { placement: 'left-start', offset: 40 },
        ...restProps
    } = props;

    const [ compatibility, setCompatibility ] = useCompatibilityMeta();

    const _renderToggle = ( { isOpen, onToggle, onClose } ) => renderToggle( {
        isOpen,
        onToggle,
        onClose,
        compatibility,
        setCompatibility
    } );

    return (
        <Dropdown
            className={ classnames( rootClass, className ) }
            contentClassName={ classnames( `${ rootClass }__content`, contentClassName ) }
            popoverProps={ popoverProps }
            renderContent={ ({ onClose }) => (
                <>
                    <InspectorPopoverHeader
                        title={ __( 'Compatibility', 'fooconvert' ) }
                        onClose={ onClose }
                    />
                    <CompatibilityContentControl
                        compatibility={ compatibility }
                        setCompatibility={ setCompatibility }
                        showDescription={ true }
                    />
                </>
            ) }
            renderToggle={ _renderToggle }
            { ...restProps }
        />
    );
};

export default CompatibilityDropdown;