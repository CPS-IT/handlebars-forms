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

The table below lists all content objects, roughly ordered from the ones needed in
almost every form to more specialised ones.

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Content object
        -   Purpose
    *   -   :ref:`HBS_RENDERABLES <co-hbs-renderables>`
        -   Iterates form elements and applies per-type configuration
    *   -   :ref:`HBS_TAG <co-hbs-tag>`
        -   Reads HTML attributes (``id``, ``name``, ``value``, …) of a rendered element
    *   -   :ref:`HBS_LABEL <co-hbs-label>`
        -   Returns the translated label of an element
    *   -   :ref:`HBS_NAVIGATION <co-hbs-navigation>`
        -   Resolves previous, next and submit buttons
    *   -   :ref:`HBS_PASSTHROUGH <co-hbs-passthrough>`
        -   Renders an element with EXT:form's default Fluid partials
    *   -   :ref:`HBS_FORM_VALUE <co-hbs-form-value>`
        -   Returns the submitted value of an element (e.g. on summary pages)
    *   -   :ref:`HBS_PROPERTY <co-hbs-property>`
        -   Reads an arbitrary property of an element or its view model
    *   -   :ref:`HBS_TRANSLATE_PROPERTY <co-hbs-translate-property>`
        -   Translates an element property (e.g. placeholder, description)
    *   -   :ref:`HBS_VALIDATION_RESULTS <co-hbs-validation-results>`
        -   Returns validation results of an element
    *   -   :ref:`HBS_TRANSLATE_ERROR <co-hbs-translate-error>`
        -   Translates a validation error message
    *   -   :ref:`HBS_CHILDREN <co-hbs-children>`
        -   Iterates child view models (e.g. select options, upload fields)
    *   -   :ref:`HBS_VH_CONTENT <co-hbs-vh-content>`
        -   Returns the raw output of the underlying Fluid view helper

..  toctree::
    :maxdepth: 1
    :hidden:

    Renderables
    Tag
    Label
    Navigation
    Passthrough
    FormValue
    Property
    TranslateProperty
    ValidationResults
    TranslateError
    Children
    VhContent
