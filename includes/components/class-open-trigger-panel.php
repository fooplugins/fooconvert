<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\Base_Component;
use FooPlugins\FooConvert\Utils;

class Open_Trigger_Panel extends Base_Component {
    function get_attributes( array $attr_value ): array {
        $extra_attributes = array();
        $trigger_type = Utils::get_string( $attr_value, 'type' );
        $trigger_data = Utils::get_key( $attr_value, 'data' );
        switch ( $trigger_type ) {
            case 'immediate':
                $extra_attributes['trigger'] = $trigger_type;
                break;
            case 'anchor':
            case 'element':
            case 'visible':
                if ( Utils::is_string( $trigger_data, true ) ) {
                    $extra_attributes['trigger'] = $trigger_type;
                    $extra_attributes['trigger-data'] = $trigger_data;
                }
                break;
            case 'exit-intent':
            case 'timer':
                if ( Utils::is_int_within( $trigger_data, 0, 100 ) ) {
                    $extra_attributes['trigger'] = $trigger_type;
                    $extra_attributes['trigger-data'] = $trigger_data;
                }
                break;
            case 'scroll':
                if ( Utils::is_int_within( $trigger_data, 1, 100 ) ) {
                    $extra_attributes['trigger'] = $trigger_type;
                    $extra_attributes['trigger-data'] = $trigger_data;
                }
                break;
        }
        return $extra_attributes;
    }

    function get_data( array $attr_value ): array {
        $data = array();
        $trigger_type = Utils::get_string( $attr_value, 'type' );
        $trigger_data = Utils::get_key( $attr_value, 'data' );
        switch ( $trigger_type ) {
            case 'immediate':
                $data['trigger'] = $trigger_type;
                break;
            case 'anchor':
            case 'element':
            case 'visible':
                if ( Utils::is_string( $trigger_data, true ) ) {
                    $data['trigger'] = $trigger_type;
                    $data['triggerData'] = $trigger_data;
                }
                break;
            case 'exit-intent':
            case 'timer':
                if ( Utils::is_int_within( $trigger_data, 0, 100 ) ) {
                    $data['trigger'] = $trigger_type;
                    $data['triggerData'] = $trigger_data;
                }
                break;
            case 'scroll':
                if ( Utils::is_int_within( $trigger_data, 1, 100 ) ) {
                    $data['trigger'] = $trigger_type;
                    $data['triggerData'] = $trigger_data;
                }
                break;
        }
        return $data;
    }
}