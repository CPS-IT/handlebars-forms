..  include:: /Includes.rst.txt

..  _co-hbs-children:

=============
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
