import { __, sprintf } from "@wordpress/i18n";
import { Icon, info } from "@wordpress/icons";
import classnames from "classnames";

import { DisplayRulesLocationsControl } from "../locations-control";
import { DisplayRulesRolesControl } from "../roles-control";
import editorData from "../../editorData";

import "./Component.scss";
import usePostTypeLabels from "../../../../hooks/usePostTypeLabels";

const rootClass = 'fc--display-rules__content-control';

const DisplayRulesContentControl = ( props ) => {
    const {
        rules,
        setRules,
        compiledRules,
        className,
        showDescription = false,
    } = props;

    const updateRules = value => {
        setRules( { ...rules, ...value } );
    };

    const noLocation = compiledRules.location.length === 0;
    const noUsers = compiledRules.users.length === 0;

    const requiresOneLabel = ( text, isEmpty, message ) => {
        if ( isEmpty ) {
            return (
                <>
                    { text }
                    { isEmpty && (
                        <span className={ `${ rootClass }__requires-one` } title={ message }>
                            <Icon icon={ info }/>
                        </span>
                    ) }
                </>
            );
        }
        return text;
    };

    const locationsLabel = () => requiresOneLabel(
        __( 'Locations', 'fooconvert' ),
        noLocation,
        __( 'Add at least one location', 'fooconvert' )
    );

    const usersLabel = () => requiresOneLabel(
        __( 'Users', 'fooconvert' ),
        noUsers,
        __( 'Add at least one role', 'fooconvert' )
    );

    const labels = usePostTypeLabels() ?? { singular_name: '' };
    const description = sprintf( __( 'Set where and to whom this %s will be visible.', 'fooconvert' ), labels.singular_name );

    return (
        <div className={ classnames( rootClass, className ) }>
            { showDescription && (
                <span className={ `${ rootClass }__description` }>{ description }</span>
            ) }
            <DisplayRulesLocationsControl
                label={ locationsLabel() }
                options={ editorData?.location }
                items={ rules?.location }
                onChange={ value => updateRules( { location: value } ) }
                noItemsLabel={ __( 'None', 'fooconvert' ) }
                addItemLabel={ __( 'Add location', 'fooconvert' ) }
                removeItemLabel={ __( 'Remove location', 'fooconvert' ) }
            />
            <DisplayRulesLocationsControl
                label={ __( 'Exclusions', 'fooconvert' ) }
                options={ editorData?.exclude }
                items={ rules?.exclude }
                onChange={ value => updateRules( { exclude: value } ) }
                noItemsLabel={ __( 'None', 'fooconvert' ) }
                addItemLabel={ __( 'Add exclusion', 'fooconvert' ) }
                removeItemLabel={ __( 'Remove exclusion', 'fooconvert' ) }
            />
            <DisplayRulesRolesControl
                label={ usersLabel() }
                options={ editorData?.users }
                items={ rules?.users }
                onChange={ value => updateRules( { users: value } ) }
                noItemsLabel={ __( 'None', 'fooconvert' ) }
                addItemLabel={ __( 'Add role', 'fooconvert' ) }
                removeItemLabel={ __( 'Remove role', 'fooconvert' ) }
            />
        </div>
    );
};

export default DisplayRulesContentControl;