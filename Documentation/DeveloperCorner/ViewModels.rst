..  include:: /Includes.rst.txt

..  _view-models:

===========
View models
===========

Renderables are converted to so-called *view models*, which is done by dedicated *view model builders*.
Each view model represents the default view implementation of a renderable, based on the Fluid templates
shipped by EXT:form. Note that some renderables might be represented by various view model implementations,
based on specific aspects outlined below.

..  tip::
    You can also create custom view model builders by implementing the
    :php:`CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder\ViewModelBuilder` interface.

Where a renderable can resolve to more than one view model, each possible outcome is listed as
its own sub-section below, named after the condition under which it applies.

..  seealso::
    :ref:`Choosing a view model type <custom-vmb-view-model-types>` explains what each
    view model type below is for and when to use it in a custom builder.

The following renderables are currently supported by this extension. The table below
gives a quick overview; each renderable is covered in detail further down, including
the exact conditions for each outcome.

..  contents::
    :local:

..  _view-models-overview:

Overview
========

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Renderable
        -   View model
    *   -   :php:`AdvancedPassword`
        -   :php:`ViewModelCollection`
    *   -   :php:`Checkbox`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`ContentElement`
        -   :php:`ViewHelperContainedViewModel` (valid UID) or :php:`SimpleViewModel`
            (invalid UID)
    *   -   :php:`CountrySelect`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`Fieldset`
        -   :php:`StandaloneTagViewModel`
    *   -   :php:`FileUpload`, :php:`ImageUpload`
        -   :php:`ViewModelCollection` (resolved) or :php:`ViewHelperContainedViewModel`
            (unresolved)
    *   -   :php:`Form`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`Hidden`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`MultiCheckbox`
        -   :php:`FormFieldViewModel` or :php:`ViewHelperContainedViewModel` (per option)
    *   -   :php:`Password`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`RadioButton`
        -   :php:`FormFieldViewModel` or :php:`ViewHelperContainedViewModel` (per option)
    *   -   :php:`SingleSelect`, :php:`MultiSelect`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`StaticText`
        -   :php:`FormFieldViewModel` (label available) or :php:`StandaloneTagViewModel`
            (no label)
    *   -   :php:`Textarea`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`Text`, :php:`Date`, :php:`Email`, :php:`Number`, :php:`Telephone`,
            :php:`Url`
        -   :php:`ViewHelperContainedViewModel`
    *   -   :php:`DatePicker`
        -   *not supported*

..  _supported-renderables:

Supported renderables
=====================

..  _advanced-password:

:php:`AdvancedPassword`
-----------------------

Represented by :php:`ViewModelCollection`, containing two child view models:

`passwordField`
    :php:`ViewHelperContainedViewModel` — result from `<formvh:form.password>` view helper
    invocation for the password field.

`confirmationField`
    :php:`FormFieldViewModel` or :php:`ViewHelperContainedViewModel` — result from
    `<formvh:form.password>` view helper invocation for the confirmation field, combined
    with the confirmation label when available; otherwise returned standalone.

..  _checkbox:

:php:`Checkbox`
---------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.checkbox>` view helper invocation.

..  _content-element:

:php:`ContentElement`
---------------------

..  rubric:: Configured content element UID is valid

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<f:cObject>` view helper invocation.

..  rubric:: Configured content element UID is invalid

Represented by :php:`SimpleViewModel` as fallback, used when the configured UID cannot
be resolved.

..  _country-select:

:php:`CountrySelect`
--------------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.countrySelect>` view helper invocation.

..  _fieldset:

:php:`Fieldset`
---------------

Represented by :php:`StandaloneTagViewModel`, containing the `<fieldset>` tag with class
name(s) and additional attributes.

..  _file-upload:

:php:`FileUpload`, :php:`ImageUpload`
-------------------------------------

..  rubric:: Uploaded resource can be resolved

Represented by :php:`ViewModelCollection`, containing three child view models:

`uploadField`
    :php:`ViewHelperContainedViewModel` — result from `<formvh:form.uploadedResource>`
    view helper invocation for the upload field.

`resourcePointerFields`
    :php:`ViewModelCollection` of :php:`StandaloneTagViewModel` — optional; references
    hidden `<input>` fields with resource pointers, if available.

`uploads`
    :php:`ViewModelCollection` of :php:`FileResourceViewModel` — references file uploads.
    Each contains a `resource` (:php:`FileReference` or :php:`PseudoFileReference`) and,
    optionally (TYPO3 >= v14), a `deleteCheckbox` (:php:`FormFieldViewModel`) to delete
    the upload on submit.

..  rubric:: Uploaded resource cannot be resolved

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.uploadedResource>` view helper invocation.

..  _form:

:php:`Form`
-----------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form>` view helper invocation.

..  _hidden:

:php:`Hidden`
-------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.hidden>` view helper invocation.

..  _multi-checkbox:

:php:`MultiCheckbox`
--------------------

Contains one view model per available option, each as one of:

..  rubric:: Label is available

Represented by :php:`FormFieldViewModel`, combining the label and result from
`<formvh:form.checkbox>` view helper invocation.

..  rubric:: Associated label is invalid or missing

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.checkbox>` view helper invocation.

..  _password:

:php:`Password`
---------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.password>` view helper invocation.

..  _radio-button:

:php:`RadioButton`
------------------

Contains one view model per available option, each as one of:

..  rubric:: Label is available

Represented by :php:`FormFieldViewModel`, combining the label and result from
`<formvh:form.radio>` view helper invocation.

..  rubric:: Associated label is invalid or missing

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.radio>` view helper invocation.

..  _select:

:php:`SingleSelect`, :php:`MultiSelect`
---------------------------------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.select>` view helper invocation. Includes available `<option>` tags as children
of type :php:`StandaloneTagViewModel`.

..  _static-text:

:php:`StaticText`
-----------------

..  rubric:: Label is available

Represented by :php:`FormFieldViewModel`, combining the label and a `<p>` tag.

..  rubric:: Label is invalid or missing

Represented by :php:`StandaloneTagViewModel`, containing a `<p>` tag with class and text.

..  _textarea:

:php:`Textarea`
---------------

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.textarea>` view helper invocation.

..  _text:

Text and other text-based fields
---------------------------------

Applies to :php:`Text`, :php:`Date`, :php:`Email`, :php:`Number`, :php:`Telephone`, and
:php:`Url`.

Represented by :php:`ViewHelperContainedViewModel`, containing the result from
`<formvh:form.textfield>` view helper invocation.

..  _unsupported-renderables:

Unsupported renderables
========================

..  _date-picker:

:php:`DatePicker`
-----------------

This form element was :ref:`deprecated in TYPO3 v14.2 <t3changelog:deprecation-109152-1741600000>`
and is therefore not supported by this extension.

..  seealso::
    View the sources on GitHub:

    -   `ViewModelBuilder <https://github.com/CPS-IT/handlebars-forms/blob/main/Classes/Domain/ViewModel/Builder/ViewModelBuilder.php>`__
