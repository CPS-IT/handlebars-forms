..  include:: /Includes.rst.txt

..  _co-hbs-form-value:

================
HBS\_FORM\_VALUE
================

Reads the current or submitted value of the form element. Internally wraps
EXT:form's :fluid:`<formvh:renderFormValue>` view helper and exposes its result
as a :php:`FormValueViewModel`.

Without an :typoscript:`output` key the resolved :typoscript:`processedValue` is
returned directly.

**Output instructions**

The :typoscript:`output` key accepts one of the following built-in instructions:

..  confval-menu::
    :name: hbs-form-value-output
    :display: table
    :type:

    ..  confval:: PROCESSED_VALUE
        :name: hbs-form-value-processed-value
        :type: string instruction

        The formatted, human-readable value (e.g. option label for select fields).

    ..  confval:: VALUE
        :name: hbs-form-value-value
        :type: string instruction

        The raw, machine-readable value.

    ..  confval:: IS_MULTI_VALUE
        :name: hbs-form-value-is-multi-value
        :type: string instruction

        Boolean – whether the field holds multiple values (e.g. multi-select,
        multi-checkbox).

    ..  confval:: IS_SECTION
        :name: hbs-form-value-is-section
        :type: string instruction

        Boolean – whether the renderable is a section (fieldset, page).

    ..  confval:: EACH_PROCESSED_VALUE
        :name: hbs-form-value-each-processed-value
        :type: string instruction

        Iterates over all values and processes each with the sub-configuration in
        :typoscript:`output`.

    ..  confval:: EACH_VALUE
        :name: hbs-form-value-each-value
        :type: string instruction

        Like :typoscript:`EACH_PROCESSED_VALUE` but uses raw values.

**Examples**

..  code-block:: typoscript

    # Summary page: show the human-readable value
    value = HBS_FORM_VALUE
    value.output = PROCESSED_VALUE

    # Multi-value field: iterate over each option
    values = HBS_FORM_VALUE
    values {
        output = EACH_PROCESSED_VALUE
        output {
            label = HBS_LABEL

            selected = HBS_PROPERTY
            selected.path = selected
        }
    }
