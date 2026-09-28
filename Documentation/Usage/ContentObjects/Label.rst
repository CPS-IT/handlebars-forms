..  include:: /Includes.rst.txt

..  _co-hbs-label:

==========
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
