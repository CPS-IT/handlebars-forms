..  include:: /Includes.rst.txt

..  _co-hbs-passthrough:

================
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
