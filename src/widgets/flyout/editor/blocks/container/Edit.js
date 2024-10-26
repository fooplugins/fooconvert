import { useBlockAttributes, useIsInnerBlockSelected } from "#editor";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { FLYOUT_DEFAULTS } from "../../Edit";

export const CONTAINER_CLASS_NAME = 'fc--flyout-container';

const Edit = props => {

    const {
        clientId,
        isSelected,
        context: {
            'fc-flyout/clientId': parentClientId
        }
    } = props;

    const [ parentAttributes, setParentAttributes ] = useBlockAttributes( parentClientId );
    const isInnerBlockSelected = useIsInnerBlockSelected( clientId, true );

    const { viewState: parentViewState } = parentAttributes;

    useEffect( () => {
        if ( parentViewState === 'closed' && ( isSelected || isInnerBlockSelected ) ) {
            setParentAttributes( { viewState: 'open' } );
        }
    }, [ isSelected, isInnerBlockSelected ] );

    if ( parentViewState === 'closed' ) {
        return null;
    }

    const customProps = {
        ...props,
        parentClientId,
        parentAttributes,
        setParentAttributes,
        parentAttributesDefaults: {
            ...FLYOUT_DEFAULTS
        }
    };

    return (
        <>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

export default Edit;