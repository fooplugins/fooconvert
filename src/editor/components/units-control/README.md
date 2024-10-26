# UnitsControl

***Experimental** - Uses `__experimentalUnitControl` from the `@wordpress/components` package.*

`UnitsControl` is a component that allows the user to set a numeric quantity as well as a unit (e.g. `px`) using either an input or range slider.

## Usage

```jsx
import { useState } from "react";
import { UnitsControl } from "#editor";

const Example = () => {
    const [ value, setValue ] = setState();
    return (
        <UnitsControl
            value={ value }
            onChange={ nextValue => setValue( nextValue ) }
        />
    );
};
```

## Types

### `FCUnitsControlProps`

Defines the properties accepted by the component. See the *props* defined below.

## Props

Inherits all properties except `children` from `BaseControlProps` defined in `@wordpress/components/build-types/base-control/types`. 

### `value`: `string` | `undefined`

Current value.

If passed as a string, the current unit will be inferred from this value.
For example, a `value` of `50%` will set the current unit to `%`.

* Required: Yes

### `onChange`: `( nextValue: string | undefined ) => void`

Callback when the `value` changes.

* Required: Yes

### `units`: `WPUnitControlUnit[]`

Collection of available units.

* Required: No
* Default: A collection containing the 'px', '%' and 'em' units.

`WPUnitControlUnit` is defined in `@wordpress/components/build-types/unit-control/types`.

See the `UnitControl` readme for [more info](https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/unit-control/README.md#units-wpunitcontrolunit).  


### `disableUnits`: `boolean`

If `true`, the unit `<select>` is hidden.

* Required: No
* Default: `false`

See the `UnitControl` readme for [more info](https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/unit-control/README.md#disableunits-boolean).

### `min`: `number`

The minimum value allowed.

* Required: No
* Default: `0`

### `max`: `number`

The maximum value allowed.

* Required: No
* Default: `100`

### `step`: `number` | `"any"`

The minimum amount by which `value` changes. It is also a factor in validation as `value` must be a multiple of `step` (offset by `min`) to be valid. Accepts the special string value `"any"` that voids the validation constraint.

* Required: No
* Default: `1`

### `initialPosition`: `number`

The slider starting position, used when no `value` is passed. The `initialPosition` will be clamped between the provided `min` and `max` prop values.

* Required: No
* Default: The `min` value.

### `size`: `"default"` | `"small"` | `"compact"` | `"__unstable-large"`

Adjusts the size of the unit control.

* Required: No
* Default: `"__unstable-large"`

NOTE: As this uses `"__unstable-large"` this will need to be checked with new releases.

### `placeholder`: `string`

The placeholder to display when no `value` is passed.

* Required: No
* Default: `undefined`

### `before`: `() => React.ReactNode`

If this property is added, the callback allows for custom content to be rendered before the inputs.

* Required: No

### `after`: `() => React.ReactNode`

If this property is added, the callback allows for custom content to be rendered after the inputs.

* Required: No