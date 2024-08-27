import { MenuGroup, MenuItem, ToolbarDropdownMenu } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useVariations } from "../variation-picker";

const ToolbarVariationMenu = ( {
                                   clientId,
                                   reset,
                                   resetLabel = __( 'Reset', 'fooconvert' ),
                                   ...props
                               } ) => {
    const { blockVariations, setVariation, canReset } = useVariations( clientId, reset );

    const onChange = async ( value, done ) => {
        await setVariation( value );
        done();
    };
    return (
        <ToolbarDropdownMenu { ...props }>
            { ( { onClose } ) => (
                <>
                    <MenuGroup>
                        { blockVariations.map( variation => {
                            return (
                                <MenuItem
                                    key={ variation.name }
                                    icon={ variation?.icon }
                                    onClick={ () => onChange( variation, onClose ) }
                                >
                                    { variation.title }
                                </MenuItem>
                            );
                        } ) }
                    </MenuGroup>
                    { canReset && (
                        <MenuGroup>
                            <MenuItem
                                key={ 'toolbar-variation-menu-reset' }
                                onClick={ () => onChange( null, onClose ) }
                            >
                                { resetLabel }
                            </MenuItem>
                        </MenuGroup>
                    ) }
                </>
            ) }
        </ToolbarDropdownMenu>
    );
};

export default ToolbarVariationMenu;