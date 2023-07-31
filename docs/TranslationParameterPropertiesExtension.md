# Translation parameters property extension

This form extension facilitates the injection of parameters from a form's data into `help_translation_properties` and `label_translation_properties`.

Define a constant on a form, mapping key names to desired data paths:
```php
    const INJECT_TRANSLATION_PARAMETERS = [
        'dayNumber' => 'journey.diaryDay.number',
        'stage_number' => 'number',
    ];
```

The relevant values will then be automatically looked up from the form's data using PropertyAccessor, and injected into both translation properties on the form.
