import { useOverrideTemplateValidity } from "../../hooks";

import "./Plugin.scss";
import { useEffect } from "@wordpress/element";
import getEditorDocument from "../../utils/getEditorDocument";

const OverrideTemplateValidityPlugin = () => {
    useEffect( () => {
        getEditorDocument().then( doc => {
            doc.classList.add( "override-template-validity" );
        } );
        return () => {
            getEditorDocument().then( doc => {
                doc.classList.remove( "override-template-validity" );
            } );
        };
    }, [] );

    useOverrideTemplateValidity();

    return (<></>);
};

export default OverrideTemplateValidityPlugin;