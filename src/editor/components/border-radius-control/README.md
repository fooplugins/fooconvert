# BorderRadiusControl

***Experimental** - Uses `__experimentalUnitControl` from the `@wordpress/components` package.*

`BorderRadiusControl` is a component that allows the user to set a border radius using a single value for all corners or separate values per corner.

## Usage

```jsx
import { useState } from "react";
import { BorderRadiusControl } from "#editor";

const Example = () => {
    const [ value, setValue ] = setState();
    return (
        <BorderRadiusControl
            value={ value }
            onChange={ nextValue => setValue( nextValue ) }
        />
    );
};
```

## Types

### `FCBorderRadiusControlProps`

The properties accepted by the component. See the ***props*** section below.

### `FCBorderRadius`

An object which has a `topLeft`, `topRight`, `bottomRight` and `bottomLeft` properties.

## Props

Inherits all properties from `FCUnitsControlProps`, except `before`, `after`, `value` and `onChange`.

* `before` & `after` - Consumed internally by the component.
* `value` & `onChange` - Changed by the component. See below for more info.

### `value`: `string` | `Partial<FCBorderRadius>` | `undefined`

Current value.

If passed as a string, the current unit will be inferred from this value and the UI will display the linked input controls.
For example, a `value` of `50%` will set the current unit to `%`.

If passed an object which has any of the `FCBorderRadius` keys, the current unit will be inferred from those property values and the UI will display the unlinked input controls.

* Required: Yes

### `onChange`: `( nextValue: string | Partial<FCBorderRadius> | undefined ) => void`

Callback when the `value` changes. If the `nextValue` argument is an object, it will have at least one of the `FCBorderRadius` keys set with a string value.

* Required: Yes