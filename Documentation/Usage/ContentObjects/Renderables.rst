..  include:: /Includes.rst.txt

..  _co-hbs-renderables:

================
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
