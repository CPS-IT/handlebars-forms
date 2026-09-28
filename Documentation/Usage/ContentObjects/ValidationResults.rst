..  include:: /Includes.rst.txt

..  _co-hbs-validation-results:

========================
HBS\_VALIDATION\_RESULTS
========================

Returns the Extbase validation results for the current renderable. Without an
:typoscript:`output` instruction the raw :php:`Result` object is returned.

**Output instructions**

..  confval-menu::
    :name: hbs-validation-results-output
    :display: table
    :type:

    ..  confval:: EACH_ERROR
        :name: hbs-validation-results-each-error
        :type: string instruction

        Iterates over every error and processes each with the sub-configuration in
        :typoscript:`output`. On composite renderables the result is a dictionary keyed by
        property path; on leaf elements it is a flat list.

    ..  confval:: EACH_RENDERABLE
        :name: hbs-validation-results-each-renderable
        :type: string instruction

        Iterates over renderables that have at least one error. Processes each with
        the sub-configuration, then passes the result through a second round of
        :typoscript:`process-form` resolution so nested :typoscript:`HBS_*` objects are
        resolved too. The result is a dictionary keyed by property path.

    ..  confval:: ERROR_MESSAGE
        :name: hbs-validation-results-error-message
        :type: string instruction

        Returns the translated message for the first error in the result set.

    ..  confval:: HAS_ERRORS
        :name: hbs-validation-results-has-errors
        :type: string instruction

        Returns :php:`true` if the result set contains at least one error,
        :php:`false` otherwise.

    ..  confval:: RESULT
        :name: hbs-validation-results-result
        :type: string instruction

        Returns a property from the :php:`Result` object. Requires
        :typoscript:`output.propertyPath` to be set.

**Example**

..  code-block:: typoscript

    errors = HBS_VALIDATION_RESULTS
    errors {
        output = EACH_RENDERABLE
        output {
            label = HBS_LABEL

            message = HBS_VALIDATION_RESULTS
            message.output = ERROR_MESSAGE
        }
    }
