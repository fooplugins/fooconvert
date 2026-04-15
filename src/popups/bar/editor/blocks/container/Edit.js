import { useIsInnerBlockSelected, useRootAttributes } from "#editor";
import { useEffect } from "@wordpress/element";
import EditBlock from "./EditBlock";
import EditSettings from "./EditSettings";
import { BAR_DEFAULTS } from "../../Edit";
import ViewStateControls from "../../components/view-state-controls";

export const CONTAINER_CLASS_NAME = 'fc--bar-container';

const Edit = props => {

    const {
        clientId,
        isSelected
    } = props;

    const [ parentAttributes, setParentAttributes ] = useRootAttributes( 'fc/bar' );

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
        parentAttributes,
        setParentAttributes,
        parentAttributesDefaults: {
            ...BAR_DEFAULTS
        }
    };

    return (
        <>
            <ViewStateControls/>
            <EditBlock { ...customProps }/>
            <EditSettings { ...customProps }/>
        </>
    );
};

export default Edit;