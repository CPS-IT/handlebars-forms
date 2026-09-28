..  include:: /Includes.rst.txt

..  _co-hbs-translate-error:

=====================
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
