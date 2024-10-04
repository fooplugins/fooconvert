// External Imports
import {
    Button, Modal, TabPanel
} from "@wordpress/components";
import { useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { Icon, seen, unseen, warning } from "@wordpress/icons";

// Internal Imports
import "./Component.scss";

import { DisplayRulesLocationsControl, DisplayRulesRolesControl, DisplayRulesSummary } from "./components";
import editorData from "./editorData";
import { useDisplayRulesMeta } from "./hooks";
import noop from "../../utils/noop";
import classnames from "classnames";
import { clone } from "@steveush/utils";
import compileDisplayRules from "./utils/compileDisplayRules";

const rootClass = 'fc-display-rules-button';

/**
 * @typedef DisplayRulesButtonProps
 * @property {string} label
 * @property {() => void} [onOpen]
 * @property {() => void} [onClose]
 * @property {( value: DisplayRulesMeta, previousValue: DisplayRulesMeta ) => void} [onChange]
 * @property {( value: DisplayRulesMeta ) => void} [onSave]
 */

/**
 *
 * @param {DisplayRulesButtonProps} props
 * @returns {JSX.Element}
 */
const DisplayRulesButton = ( {
                                 label,
                                 onOpen = noop,
                                 onClose = noop,
                                 onChange = noop,
                                 onSave = noop,
                                 ...buttonProps
                             } ) => {

    const [ isOpen, setOpen ] = useState( false );
    const [ isDirty, setDirty ] = useState( false );
    const [ meta, setMeta ] = useDisplayRulesMeta();
    const [ rules, setRules ] = useState( clone( meta ) );

    const compiledMeta = compileDisplayRules( meta );
    const compiledRules = compileDisplayRules( rules );

    const openModal = () => {
        setOpen( true );
        onOpen();
    };
    const closeModal = () => {
        if ( isDirty ) {
            setRules( clone( meta ) );
            setDirty( false );
        }
        setOpen( false );
        onClose();
    };
    const saveModal = () => {
        setMeta( rules );
        setDirty( false );
        onSave( rules );
        setOpen( false );
        onClose();
    };
    const changeRules = value => {
        const newValue = { ...rules, ...value };
        setRules( newValue );
        setDirty( true );
        onChange( newValue, rules );
    };

    const renderTabTitle = ( name, title ) => {
        if ( compiledRules.reasons.some( r => r.source === name ) ? warning : undefined ) {
            return (
                <>
                    <span>{ title }</span>
                    <Icon icon={ warning }/>
                </>
            );
        } else {
            return title;
        }
    };

    const tabs = [
        {
            name: 'location',
            title: renderTabTitle( 'location', __( 'Location', 'fooconvert' ) ),
            className: 'fc-display-rules__modal-tab fc-display-rules__modal-tab-location'
        },
        {
            name: 'exclude',
            title: renderTabTitle( 'exclude', __( 'Exclude', 'fooconvert' ) ),
            className: 'fc-display-rules__modal-tab fc-display-rules__modal-tab-exclude'
        },
        {
            name: 'users',
            title: renderTabTitle( 'users', __( 'Users', 'fooconvert' ) ),
            className: 'fc-display-rules__modal-tab fc-display-rules__modal-tab-users'
        }
    ];

    const renderTab = tab => {
        switch ( tab.name ) {
            case "location":
                return (
                    <>
                        <p>{ __( 'Choose where you want to display the modal.', 'fooconvert' ) }</p>
                        <DisplayRulesLocationsControl
                            options={ editorData?.location }
                            items={ rules?.location }
                            onChange={ value => changeRules( { location: value } ) }
                        />
                    </>
                );
            case "exclude":
                return (
                    <>
                        <p>{ __( 'Choose where you do not want to display the modal.', 'fooconvert' ) }</p>
                        <DisplayRulesLocationsControl
                            options={ editorData?.exclude }
                            items={ rules?.exclude }
                            onChange={ value => changeRules( { exclude: value } ) }
                        />
                    </>
                );
            case "users":
                return (
                    <>
                        <p>{ __( 'Choose which users will see this modal.', 'fooconvert' ) }</p>
                        <DisplayRulesRolesControl
                            options={ editorData?.users }
                            items={ rules?.users }
                            onChange={ value => changeRules( { users: value } ) }
                        />
                    </>
                );
        }
    };

    const renderSummary = () => {
        let icon = warning;
        let messages = compiledMeta.reasons.map( r => r.message );
        if ( compiledMeta.success ) {
            icon = seen;
            messages = [ __( 'Configured', 'fooconvert' ) ];
        }
        return (
            <div className={ classnames( `${ rootClass }__summary`, { [`${ rootClass }__invalid`]: !compiledMeta.success } ) }>
                <div className={ `${ rootClass }__summary-icon` }>
                    <Icon icon={ icon }/>
                </div>
                <div className={ `${ rootClass }__summary-messages` }>
                    { messages.map( ( m, i ) => (
                        <span key={ i }>{ m }</span>
                    ) ) }
                </div>
            </div>
        );
    };

    return (
        <div className={ classnames( rootClass ) }>
            { renderSummary() }
            <Button className={ `${ rootClass }__button` } { ...buttonProps } isPressed={ isOpen }
                    onClick={ openModal }>
                { label }
            </Button>
            { isOpen && (
                <Modal className="fc-display-rules__modal"
                       title={ __( 'Display Rules', 'fooconvert' ) }
                       icon={ ( <Icon icon={ compiledRules.success ? seen : unseen }/> ) }
                       onRequestClose={ closeModal }>
                    <TabPanel
                        className="fc-display-rules__modal-tabs"
                        tabs={ tabs }
                    >
                        { tab => renderTab( tab ) }
                    </TabPanel>
                    <div className="fc-display-rules__modal-footer">
                        <Button variant="secondary" onClick={ closeModal }>
                            { __( 'Cancel', 'fooconvert' ) }
                        </Button>
                        <Button variant="primary" onClick={ saveModal } disabled={ !isDirty }>
                            { __( 'Save', 'fooconvert' ) }
                        </Button>
                    </div>
                </Modal>
            ) }
        </div>
    );
};

export default DisplayRulesButton;