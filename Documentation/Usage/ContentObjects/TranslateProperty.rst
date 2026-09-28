..  include:: /Includes.rst.txt

..  _co-hbs-translate-property:

========================
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
