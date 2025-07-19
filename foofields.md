# FooFields - Settings and Metabox Framework

FooFields is a powerful, flexible settings and metabox framework built specifically for WordPress. It's designed to create clean, reusable admin interfaces with minimal boilerplate code.

## Overview

FooFields provides a cohesive system for creating:
- **Settings Pages** - WordPress admin configuration interfaces
- **Metaboxes** - Post edit screen panels
- **Fields** - Individual form controls (all HTML5 field types)

## File Structure

```
includes/Admin/FooFields/
├── Base.php               # Base utilities and sanitization
├── Container.php          # Abstract base for containers
├── Fields/                # All field types
│   ├── Field.php         # Base field class
│   ├── AjaxButton.php    # Ajax-powered buttons
│   ├── Blurb.php         # Information text blocks
│   ├── InputList.php     # Checkbox and radio groups
│   ├── Repeater.php      # Repeatable field sets
│   ├── Selectize.php     # Enhanced select inputs
│   └── ...               # Other field types
├── SettingsPage.php      # WordPress settings pages
├── Metabox.php           # Post edit screen metaboxes
└── Manager.php           # Asset and registry management
```

## Quick Start

### Creating a Settings Page

```php
use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;

class FooConvertSettings extends SettingsPage {
    public function __construct() {
        parent::__construct([
            'manager' => 'fooconvert',
            'settings_id' => 'fooconvert_settings',
            'menu_parent_slug' => 'options-general.php',
            'layout' => 'foofields-tabs-horizontal'
        ]);
    }

    function get_tabs() {
        return [
            'general' => [
                'id' => 'general',
                'label' => __('General Settings', 'textdomain'),
                'icon' => 'dashicons-admin-generic',
                'order' => 10,
                'fields' => [
                    'api_key' => [
                        'id' => 'api_key',
                        'type' => 'text',
                        'label' => __('API Key', 'textdomain'),
                        'placeholder' => 'Enter your API key...'
                    ]
                ]
            ]
        ];
    }
}
```

### Creating a Metabox

```php
use FooPlugins\FooConvert\Admin\FooFields\Metabox;

class PostSettings extends Metabox {
    public function __construct() {
        parent::__construct([
            'id' => 'post_settings_metabox',
            'title' => __('Post Settings', 'textdomain'),
            'post_types' => ['post', 'page'],
            'context' => 'side',
            'priority' => 'default',
            'manager' => 'my_plugin'
        ]);
    }

    function get_fields() {
        return [
            'featured' => [
                'id' => 'featured',
                'type' => 'checkbox',
                'label' => __('Featured Post', 'textdomain')
            ]
        ];
    }
}
```

## Core Concepts

### Containers

Containers are the foundation - they hold both configuration and data. There are two main types:

- **SettingsPage** - WordPress settings pages (stored in options table)
- **Metabox** - Post edit screen panels (stored as post meta)

#### Configuration Options

| Option | Type | Description |
|--------|------|-------------|
| `manager` | string | Asset manager key (required) |
| `layout` | string | Layout styling: `foofields-tabs-vertical` / `foofields-tabs-horizontal` |
| `styles` | array | Custom CSS files |
| `scripts` | array | Custom JavaScript files |

### Field Types

FooFields comes with comprehensive field types to handle any data entry need:

#### Basic Fields

```php
// Text Input
'title' => [
    'type' => 'text',
    'label' => 'Title',
    'placeholder' => 'Enter title...'
]

// Textarea
'description' => [
    'type' => 'textarea',
    'label' => 'Description',
    'default' => 'Default description text'
]

// Number
'amount' => [
    'type' => 'numeric',
    'label' => 'Amount',
    'min' => 0,
    'max' => 1000
]
```

#### Boolean Fields

```php
// Checkbox
'enabled' => [
    'type' => 'checkbox',
    'label' => 'Enable Feature',
    'default' => true
]

// Checkbox List
'features' => [
    'type' => 'checkboxlist',
    'label' => 'Features',
    'choices' => [
        'feature1' => 'Feature One',
        'feature2' => 'Feature Two'
    ]
]
```

#### Selection Fields

```php
// Dropdown
'color' => [
    'type' => 'select',
    'label' => 'Color',
    'choices' => [
        'red' => 'Red',
        'blue' => 'Blue'
    ],
    'empty' => '- Select Color -'
]

// Multi-select
'categories' => [
    'type' => 'selectize-multi',
    'label' => 'Categories',
    'choices' => $terms
]

// Suggest/autocomplete
'tags' => [
    'type' => 'suggest',
    'label' => 'Tags',
    'placeholder' => 'Type to search...'
]
```

#### Advanced Fields

```php
// Repeater (Repeatable Fields)
'slides' => [
    'type' => 'repeater',
    'label' => 'Slides',
    'add_button_text' => 'Add Slide',
    'fields' => [
        [
            'id' => 'title',
            'type' => 'text',
            'label' => 'Slide Title'
        ],
        [
            'id' => 'image',
            'type' => 'image',
            'label' => 'Slide Image'
        ]
    ]
]

// Icon Picker
'icon' => [
    'type' => 'icon-picker',
    'label' => 'Choose Icon'
]

// Ajax Button
'test_connection' => [
    'type' => 'ajaxbutton',
    'label' => 'Test Connection',
    'button' => 'Test API',
    'callback' => function() {
        wp_send_json_success();
    }
]
```

### Conditional Logic (Show/Hide Fields)

FooFields supports dynamic conditional visibility:

```php
'api_key' => [
    'type' => 'text',
    'label' => 'API Key',
    'data' => [
        'show-when' => [
            'field' => 'enabled',
            'value' => 1,
            'operator' => '===' // '===', '!==', 'indexOf', 'regex'
        ]
    ]
]
```

### Field Validation

Automatic validation with custom messages:

```php
'email' => [
    'type' => 'text',
    'label' => 'Email',
    'validate' => 'email',
    'error' => 'Please enter a valid email address'
]
```

## Data Handling

### Getting Values

#### Settings Pages

```php
// Settings stored in options table
$settings = get_option('fooconvert_settings');
$api_key = $settings['api_key'] ?? '';

// Or using helper functions
$enabled = fooconvert_get_setting('enabled', false);
```

#### Metaboxes (Post Meta)

```php
// Standard WordPress meta
$featured = get_post_meta($post_id, 'featured', true);

// Or using container methods
$container->get_field_value('featured_field');
```

### Sanitization

All data is automatically sanitized based on field type:
- `text` → `sanitize_text_field()`
- `textarea` → `sanitize_textarea_field()`
- `email` → `sanitize_email()`
- `url` → `esc_url_raw()`
- `numeric` → `is_numeric()` validation

### Saving Data

The framework handles all data persistence:
- Settings automatically saved during form submission
- Metabox data automatically saved on post save
- No need for custom save handlers

## Advanced Features

### Custom Field Types

Register custom field types:

```php
add_filter('foofields_field_type_mappings', function($mappings) {
    $mappings['colorpicker'] = 'MyPlugin\Fields\ColorPicker';
    return $mappings;
});
```

### Custom Validation

Add custom validation rules:

```php

'custom_field' => [
    'type' => 'text',
    'label' => 'Custom Field',
    'validate' => function($value) {
        return preg_match('/^[a-z]+$/', $value);
    },
    'error' => 'Only lowercase letters allowed'
]
```

## Best Practices

### Organizing Fields

Use tabs for complex configurations:

```php
// Settings grouped by logical tabs
$tabs = [
    'general' => [...],    // Basic settings
    'advanced' => [...],   // Power user options
    'integration' => [...]  // API settings
];
```

### Reusable Configurations

Create reusable field sets:

```php
// Define reusable field groups
$api_fields = [
    'api_url' => [...],
    'api_key' => [...],
    'timeout' => [...]
];

// Use in multiple places
'tabs' => [
    'integration1' => ['fields' => $api_fields],
    'integration2' => ['fields' => $api_fields]
]
```

### Performance Considerations

1. **Lazy Loading**: Fields are only instantiated when displayed
2. **Caching**: Static field configurations are cached
3. **Minimal DB Queries**: Single query per container for efficiency

### Security Features

- **Automatic Sanitization**: All inputs are sanitized by field type
- **Capability Checking**: User permissions validated for each operation
- **CSRF Protection**: Nonce validation on all form submissions
- **Escaping**: All outputs automatically escaped

## Namespace and Organization

- **Core Files**: `FooPlugins\FooConvert\Admin\FooFields`
- **Field Classes**: `FooPlugins\FooConvert\Admin\FooFields\Fields`
- **Manager**: Singleton registry for container and field instances

This system allows for clean separation of concerns and makes it easy to extend functionality without modifying core code.