..  include:: /Includes.rst.txt

..  _content-objects:

================
Content objects
================

The extension registers the following custom content objects (:typoscript:`HBS_*`). They
are only useful inside a :typoscript:`process-form` data processor block; using them
elsewhere may log a warning and return an empty string.

Every :typoscript:`HBS_*` content object supports the standard :ref:`stdWrap <t3tsref:stdwrap>`
sub-key. The value resolved by the content object is passed through :typoscript:`stdWrap`
before being stored in the processed data array.

----

..  contents::
    :local:

..  _co-hbs-renderables:

HBS\_RENDERABLES
================

Iterates the child renderables of the current renderable and resolves each one
according to per-type configuration. Returns a list (array).

When used at the top level (form runtime as current renderable), it iterates the
elements of the current page. For composite renderables such as :typoscript:`Fieldset`,
it iterates their direct children.

**Configuration**

..  code-block:: typoscript

    fields = HBS_RENDERABLES
    fields {
        # Per-type configuration (key = EXT:form element type)
        Text {
            template = @form-field-text
            label = HBS_LABEL
            value = HBS_TAG
            value.attribute = value
        }

        # Fallback for types without a dedicated block
        default {
            template = @form-field-generic
        }

        # Single content object for a type (no sub-configuration)
        Honeypot = HBS_PASSTHROUGH

        # Suppress a type entirely
        SomeType {
            if.isTrue = 0
        }
    }

The lookup order for each child element is: exact type key, :typoscript:`default`. If
neither matches, the element is skipped.

**Frontend register**

While iterating, :typoscript:`HBS_RENDERABLES` writes two values to the frontend
register that TypoScript conditions can read via :ref:`register <t3tsref:data-type-gettext-register>`:

-   :typoscript:`HBS_RENDERABLES_COUNT` – total number of renderables in the current iteration
-   :typoscript:`HBS_RENDERABLES_CURRENT` – zero-based index of the element being processed
    (unset after the loop)

..  note::
    Since TYPO3 v14 the register is part of the frontend register stack on the global
    request object (:php:`frontend.register.stack` request attribute) rather than the
    legacy :php:`$GLOBALS['TSFE']->register` array. The extension handles both automatically.

..  _co-hbs-property:

HBS\_PROPERTY
=============

Reads a property from the current renderable, its view model, or the form runtime
using :php:`Extbase\Reflection\ObjectAccess::getProperty()`. Returns whatever type the
property holds (string, array, object, …).

**Configuration**

..  confval-menu::
    :name: hbs-property
    :display: table
    :type:
    :default:

    ..  confval:: path
        :name: hbs-property-path
        :type: string
        :default: *(none)*

        Property path. Supports dotted-path notation for nested access (e.g.
        :typoscript:`renderingOptions.foo`).

    ..  confval:: subject
        :name: hbs-property-subject
        :type: string
        :default: :typoscript:`renderable`

        The object to read from. One of:

        -   :typoscript:`renderable` – the current EXT:form renderable (default)
        -   :typoscript:`viewModel` – the view model built for this renderable
        -   :typoscript:`formRuntime` – the form runtime instance

**Example**

..  code-block:: typoscript

    renderableType = HBS_PROPERTY
    renderableType.path = type

    # Read from the view model instead
    resourcePointerFields = HBS_PROPERTY
    resourcePointerFields {
        subject = viewModel
        path = children?[resourcePointerFields?]
    }

..  _co-hbs-tag:

HBS\_TAG
========

Reads an HTML attribute (or the inner content) from the tag rendered by the current
renderable's view model. The view model must implement :php:`TagAwareViewModel`; this is
the case for all view models built by the built-in :php:`ViewModelBuilder` implementations.
Returns a :php:`SafeString` (Handlebars will not escape the value).

The tag reflects the final output of the Fluid ViewHelper responsible for rendering the
renderable. For example, the root :php:`FormRuntime` object is rendered by the
:fluid:`<formvh:form>` view helper, so :typoscript:`HBS_TAG` on it returns attributes (or
content) of the :html:`<form>` tag that view helper produces. Similarly, a :typoscript:`Text`
element is rendered by :fluid:`<f:form.textfield>`, so :typoscript:`HBS_TAG` exposes the
attributes of the resulting :html:`<input>` tag.

**Configuration**

..  confval-menu::
    :name: hbs-tag
    :display: table
    :type:
    :default:

    ..  confval:: attribute
        :name: hbs-tag-attribute
        :type: string
        :default: *(none)*

        Name of the HTML attribute to read. If omitted, the inner content of the
        rendered tag is returned instead.

**Example**

..  code-block:: typoscript

    # Read the "id" attribute of the rendered <input> tag
    id = HBS_TAG
    id.attribute = id

    # Read the inner HTML of the rendered tag (e.g. <textarea> content)
    content = HBS_TAG

..  _co-hbs-label:

HBS\_LABEL
==========

Returns the translated label of the current renderable. If the current view model is
a :php:`FormFieldViewModel`, the label is taken from the view model's pre-resolved
:typoscript:`label` property; otherwise it falls back to :php:`$renderable->getLabel()`.
Returns a string.

**Configuration**

No configuration keys. :typoscript:`stdWrap` is supported.

..  code-block:: typoscript

    label = HBS_LABEL

..  _co-hbs-form-value:

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

..  _co-hbs-navigation:

HBS\_NAVIGATION
===============

Resolves the navigation buttons (previous page, next page / submit) for the current
form page. Returns a list of processed items. Each item is built by the sub-configuration
keyed by button role.

**Button roles**

-   :typoscript:`previousPage` – previous-page button (only present when not on the first page)
-   :typoscript:`nextPage` – next-page button (only present when not on the last page)
-   :typoscript:`submit` – submit button (only present on the last page)

Within each role block, :typoscript:`HBS_TAG` and :typoscript:`HBS_LABEL` operate on
the rendered :html:`<button>` tag and the translated button label respectively.

**Example**

..  code-block:: typoscript

    navItems = HBS_NAVIGATION
    navItems {
        previousPage {
            label = HBS_LABEL

            name = HBS_TAG
            name.attribute = name

            value = HBS_TAG
            value.attribute = value
        }

        nextPage < .previousPage
        submit < .previousPage
    }

..  _co-hbs-passthrough:

HBS\_PASSTHROUGH
================

Renders the current renderable using EXT:form's standard Fluid partials and returns
the result as a :php:`SafeString`. Useful for elements that do not need a custom template
(e.g. :typoscript:`Honeypot`, :typoscript:`ContentElement`) or as a fallback.

**Configuration**

Additional TypoScript keys are converted to plain PHP variables and passed to the Fluid
rendering context:

..  code-block:: typoscript

    Honeypot {
        content = HBS_PASSTHROUGH
    }

    # Pass extra variables to the Fluid partial
    SomeElement {
        content = HBS_PASSTHROUGH
        content {
            myVariable = someValue
        }
    }

..  _co-hbs-children:

HBS\_CHILDREN
=============

Returns the children of the current view model as a list. The view model must implement
:php:`CompositeViewModel` (e.g. :php:`ViewModelCollection`). Returns :php:`null` when the
view model has no children.

Each child is processed using the sub-configuration of :typoscript:`HBS_CHILDREN`.

**Frontend register**

While iterating, :typoscript:`HBS_CHILDREN` writes two values to the frontend register
that TypoScript conditions can read via :ref:`register <t3tsref:data-type-gettext-register>`:

-   :typoscript:`HBS_CHILDREN_COUNT` – number of children
-   :typoscript:`HBS_CHILDREN_CURRENT` – index of the child being processed (unset after the loop)

..  note::
    Since TYPO3 v14 the register is part of the frontend register stack on the global
    request object (:php:`frontend.register.stack` request attribute) rather than the
    legacy :php:`$GLOBALS['TSFE']->register` array. The extension handles both automatically.

**Example**

..  code-block:: typoscript

    options = HBS_CHILDREN
    options {
        label = HBS_LABEL

        checked = HBS_TAG
        checked.attribute = checked
    }

..  _co-hbs-translate-property:

HBS\_TRANSLATE\_PROPERTY
========================

Translates a renderable property using EXT:form's :fluid:`<formvh:translateElementProperty>`
view helper.

**Configuration**

..  confval-menu::
    :name: hbs-translate-property
    :display: table
    :type:
    :default:

    ..  confval:: property
        :name: hbs-translate-property-property
        :type: string
        :default: *(required)*

        Name of the element property to translate.

    ..  confval:: argumentName
        :name: hbs-translate-property-argument-name
        :type: string
        :default: :typoscript:`property`

        Argument name passed to the view helper. Use :typoscript:`renderingOptionProperty`
        to translate a rendering option rather than a regular property.

**Example**

..  code-block:: typoscript

    placeholder = HBS_TRANSLATE_PROPERTY
    placeholder.property = placeholder

    submitButtonLabel = HBS_TRANSLATE_PROPERTY
    submitButtonLabel {
        property = submitButtonLabel
        argumentName = renderingOptionProperty
    }

..  _co-hbs-translate-error:

HBS\_TRANSLATE\_ERROR
=====================

Returns the translated message for a specific validation error code on the current
renderable. Uses EXT:form's :fluid:`<formvh:translateElementError>` view helper internally.

**Configuration**

..  confval-menu::
    :name: hbs-translate-error
    :display: table
    :type:
    :default:

    ..  confval:: errorCode
        :name: hbs-translate-error-error-code
        :type: int
        :default: *(required)*

        Numeric validation error code (e.g. `1221560718` for :php:`NotEmpty`).

**Example**

..  code-block:: typoscript

    requiredError = HBS_TRANSLATE_ERROR
    requiredError.errorCode = 1221560718

..  _co-hbs-validation-results:

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

..  _co-hbs-vh-content:

HBS\_VH\_CONTENT
================

Returns the output of the view helper that was used to build the current view model.
The view model must be a :php:`ViewHelperContainedViewModel`. HTML strings are
returned as :php:`SafeString` (no double-escaping).

No configuration keys beyond :typoscript:`stdWrap`.

**Example**

..  code-block:: typoscript

    # Render the raw <input> tag produced by the view helper
    content = HBS_VH_CONTENT
