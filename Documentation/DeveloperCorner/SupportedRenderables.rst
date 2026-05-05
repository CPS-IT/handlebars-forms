..  include:: /Includes.rst.txt

..  _supported-renderables:

=====================
Supported renderables
=====================

..  note::
    Renderables are converted to so-called "view models". Each view model represents the
    default view implementation of a renderable, based on the Fluid templates shipped by
    EXT:form. Note that some renderables might be represented by various view model
    implementations, based on specific aspects outlined below.

..  tip::
    You can also create custom view model builders by implementing the
    :php:`CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder\ViewModelBuilder` interface.

..  _advanced-password:

:php:`AdvancedPassword`
=======================

\(a) :php:`ViewModelCollection`
    Contains view models, reflecting both password fields:

    +---------------------+------------------------------------------+------------------------------------------------------------------------------------------------------------------------------------+
    | **Name**            | **Type**                                 | **Description**                                                                                                                    |
    +---------------------+------------------------------------------+------------------------------------------------------------------------------------------------------------------------------------+
    | `passwordField`     | :php:`ViewHelperContainedViewModel`      | Contains result from `<formvh:form.password>` view helper invocation for password field.                                           |
    +---------------------+------------------------------------------+------------------------------------------------------------------------------------------------------------------------------------+
    | `confirmationField` | \(a) :php:`ViewHelperContainedViewModel` | Result from `<formvh:form.password>` view helper invocation for password confirmation field.                                       |
    +                     +------------------------------------------+------------------------------------------------------------------------------------------------------------------------------------+
    |                     | \(b) :php:`FormFieldViewModel`           | Combination of confirmation label and result from `<formvh:form.password>` view helper invocation for password confirmation field. |
    +---------------------+------------------------------------------+------------------------------------------------------------------------------------------------------------------------------------+

..  _checkbox:

:php:`Checkbox`
===============

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.checkbox>` view helper invocation.

..  _content-element:

:php:`ContentElement`
=====================

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<f:cObject>` view helper invocation.

\(b) :php:`SimpleViewModel`
    If configured content element UID is invalid.

..  _country-select:

:php:`CountrySelect`
====================

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.countrySelect>` view helper invocation.

..  _date-picker:

:php:`DatePicker`
=================

..  important::
    TODO: Still missing!

..  _fieldset:

:php:`Fieldset`
===============

\(a) :php:`StandaloneTagViewModel`
    Contains the `<fieldset>` tag with class name(s) and additional attributes.

..  _file-upload:

:php:`FileUpload`, :php:`ImageUpload`
=====================================

\(a) :php:`ViewModelCollection`
    If uploaded resource can be resolved. Contains two or three view models:

    +------------------------+--------------------------------+--------------------------------------------------------------------------------------------------+
    | **Name**               | **Type**                       | **Description**                                                                                  |
    +------------------------+--------------------------------+--------------------------------------------------------------------------------------------------+
    | `uploadField`          | `ViewHelperContainedViewModel` | Contains result from `<formvh:form.uploadedResource>` view helper invocation for password field. |
    +------------------------+--------------------------------+--------------------------------------------------------------------------------------------------+
    | `resource`             | `FileResourceViewModel`        | References file upload, which is an instance of `FileReference` or `PseudoFileReference`.        |
    +------------------------+--------------------------------+--------------------------------------------------------------------------------------------------+
    | `resourcePointerField` | `StandaloneTagViewModel`       | Optional. References hidden `<input>` field with resource pointer, if available.                 |
    +------------------------+--------------------------------+--------------------------------------------------------------------------------------------------+

\(b) :php:`ViewHelperContainedViewModel`
    If uploaded resource cannot be resolved. Contains result from `<formvh:form.uploadedResource>`
    view helper invocation.

..  _form:

:php:`Form`
===========

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form>` view helper invocation.

..  _hidden:

:php:`Hidden`
=============

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.hidden>` view helper invocation.

..  _multi-checkbox:

:php:`MultiCheckbox`
====================

\(a) :php:`ViewModelCollection`
    Contains view models which reflect all available options, each as one of:

    +--------------------------------+-------------------------------------------------------------------------------------------------------------------------+
    | **Type**                       | **Description**                                                                                                         |
    +--------------------------------+-------------------------------------------------------------------------------------------------------------------------+
    | `FormFieldViewModel`           | If label is available. Contains a combination of label and result from `<formvh:form.checkbox>` view helper invocation. |
    +--------------------------------+-------------------------------------------------------------------------------------------------------------------------+
    | `ViewHelperContainedViewModel` | If associated label is invalid or missing. Contains result from `<formvh:form.checkbox>` view helper invocation.        |
    +--------------------------------+-------------------------------------------------------------------------------------------------------------------------+

..  _password:

:php:`Password`
===============

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.password>` view helper invocation.

..  _radio-button:

:php:`RadioButton`
==================

\(a) :php:`ViewModelCollection`
    Contains view models which reflect all available options, each as one of:

    +--------------------------------+----------------------------------------------------------------------------------------------------------------------+
    | **Type**                       | **Description**                                                                                                      |
    +--------------------------------+----------------------------------------------------------------------------------------------------------------------+
    | `FormFieldViewModel`           | If label is available. Contains a combination of label and result from `<formvh:form.radio>` view helper invocation. |
    +--------------------------------+----------------------------------------------------------------------------------------------------------------------+
    | `ViewHelperContainedViewModel` | If associated label is invalid or missing. Contains result from `<formvh:form.radio>` view helper invocation.        |
    +--------------------------------+----------------------------------------------------------------------------------------------------------------------+

..  _select:

:php:`SingleSelect`, :php:`MultiSelect`
=======================================

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.select>` view helper invocation. Includes available
    `<option>` tags as children of type `StandaloneTagViewModel`.

..  _static-text:

:php:`StaticText`
=================

\(a) :php:`FormFieldViewModel`
    If label is available. Contains a combination of label and `<p>` tag.

\(b) :php:`StandaloneTagViewModel`
    If label is invalid or missing. Contains `<p>` tag with class and text.

..  _textarea:

:php:`Textarea`
===============

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.textarea>` view helper invocation.

..  _text:

:php:`Text`, :php:`Date`, :php:`Email`, :php:`Number`, :php:`Telephone`, :php:`Url`
===================================================================================

\(a) :php:`ViewHelperContainedViewModel`
    Contains result from `<formvh:form.textfield>` view helper invocation.

..  seealso::
    View the sources on GitHub:

    -   `ViewModelBuilder <https://github.com/CPS-IT/handlebars-forms/blob/main/Classes/Domain/ViewModel/Builder/ViewModelBuilder.php>`__
