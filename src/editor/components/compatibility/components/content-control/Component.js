import { __ } from "@wordpress/i18n";
import classnames from "classnames";

import "./Component.scss";
import { ToggleControl } from "@wordpress/components";

const rootClass = 'fc--compatibility__content-control';

const CompatibilityContentControl = ( props ) => {
    const {
        compatibility,
        setCompatibility,
        className,
    } = props;

    const { required = false, enabled = false } = compatibility ?? {};

    const warn = required && !enabled;

    const setEnabled = value => setCompatibility( { required, enabled: value } );

    return (
        <div className={ classnames( rootClass, className ) }>
            { warn && (
                <div className={ `${ rootClass }__notice is-warning` }>
                    <p>{ __( 'A content block attempted to render an inline script.', 'fooconvert' ) }</p>
                </div>
            ) }
            <ToggleControl
                label={ __( 'Allow inline scripts', 'fooconvert' ) }
                checked={ enabled }
                onChange={ setEnabled }
                __nextHasNoMarginBottom
            />
        </div>
    );
};

export default CompatibilityContentControl;