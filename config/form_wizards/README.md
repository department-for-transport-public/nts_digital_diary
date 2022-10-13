# Form Wizard configuration reference

## Places (States)

These configuration keys are all within the relevant `metadata` options

### `template` *string*
Required

Specify the template to use when rendering this place

---

### `view_data` *null | array*
Default: `[]`

Additional data to pass to the twig render function. This would often include a `translation_prefix`
in order to take advantage of automatic title rendering. This is processed by `PropertyMapHelper`.

- `translation_parameters` is automatically added to the view (duplicated from `translation_parameters`, with the addition of `activity` which takes a value of either `adding` or `editing`) 

---

### `form_class` *null | string*
Default: `null`

The class name of the form type to use when rendering this place

---

### `form_options` *null | array*
Default: `[]`

The options array to use when creating the form

---

### `is_valid_alternative_start_place` *null | bool | string*
Default: `false`

Determines if the wizard is permitted to begin at his place. If a string is provided, it is treated as an Expression Language expression.

Context variables available:
- `state` - The form wizard state object

---

### `translation_parameters` *array*
Default: `[]`

Parameters to use in translations. Uses PropertyMapHelper to resolve properties to values.

---

### `form_data_property` *null | false | string*
Default: `'subject'`

Specify the data to use when initialising the form.

- `null` - Use the state object
- `false` - Do not initialise the form with data
- `string` - The property to use (using `PropertyAccessor`) on the state object

## Transitions

These configuration keys (except `guard`) are all within the relevant `metadata` options

### `guard` *null | string*
Default: `null`

This is the built-in transition blocking guard option, but we have added some helper functions to the context to allow
access to the raw form data (usually helpful when a form property isn't mapped).

- `getFormData()` - returns the form data. **Note:** Be aware that transition guards are processed before the `form`
  handles the `request` in order to render a place, and after handling the request when the form is submitted. As such,
  this will return `null` when arriving at a place, and the form data when submitting. If using this function, it will
  almost always need to be used in conjunction with `isFormDataSet()`
- `isFormDataSet()` - indicates if the form data has been set. See note above.
- `isFormDataPropertySameAs($property, $value)` - Uses Symfony's `PropertyAccessor` and the identical comparison
  operator (`===`) to return a bool. Returns false when the form data has not been set.
 

---

### `persist` *null | bool*
Default: `false`

Indicates if the state `subject` should be persisted before applying the transition

---

### `submit_label` *null | string*
Default: `'actions.continue'` (changes to `'actions.save-and-continue'` when `persist` is `true`)

The label value for the automatically added submit button

---

### `submit_translation_domain` *null | string*
Default: `'messages'`

The translation domain for the automatically added submit button

---

### `cancel_label` *null | string*
Default: `'actions.cancel'`

The label value for the automatically added cancel button

---

### `cancel_translation_domain` *null | string*
Default: `'messages'`

The translation domain for the automatically added cancel button

---

### `redirect_route` *null | string | array*
Default: `null`

If defined, a `RedirectResponse` will be returned from the controller. **Note:** the wizard controller `cleanUp()` method will be called immediately before returning the redirect response. 
- *string*: it will be treated as the route name and used without route parameters
- *array*: `route_name` (*string*) keys is required. Other optional keys are `route_parameters` (*array*), `hash` (*string*) and `hash_parameters` (*string*). `parameters` and `hash_parameters` are processed as
  PropertyAccess properties on the form wizard state object. `hash_parameters` are injected in to the `hash` using `str_replace` substitution 

---

### `notification_banner` *null | array*
Default: `null`

If defined, a `NotificationBanner` will be added to the flash message bag before applying the transition. Array keys:
- `title` (*string*) - The notification banner title (plain string, or translation key)
- `heading` (*string*) - The heading
- `content` (*string*) - The content
- `translation_domain` (*null | string*)- The domain to use in translating the `title`, `heading` and `content`
- `translation_parameters` (*null | array*)- The parameters to use in translating the `title`, `heading` and `content`. Uses PropertyAccessHelper to resolve properties to values (the state object is the context)
- `options` (*null | array*) - Additional options to pass to the notification banner. See `NotificationBanner::$options`.

---

### `context` *null | array*
Default: `null`

Provides context to "to" state/place. Useful if you need more than just a place name to mark a place, for example when a
wizard dynamically repeats a step. 
---
