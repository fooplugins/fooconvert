import { Placeholder, Spinner } from "@wordpress/components";

const LoadingSpinner = () => {
    return (
        <Placeholder className="block-editor-global-styles-background-panel__loading">
            <Spinner />
        </Placeholder>
    );
};

export default LoadingSpinner;