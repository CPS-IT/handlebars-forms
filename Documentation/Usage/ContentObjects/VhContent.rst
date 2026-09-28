..  include:: /Includes.rst.txt

..  _co-hbs-vh-content:

================
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
