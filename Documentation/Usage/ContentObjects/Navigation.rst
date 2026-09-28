..  include:: /Includes.rst.txt

..  _co-hbs-navigation:

===============
HBS\_NAVIGATION
===============

Resolves the navigation buttons (previous page, next page / submit) for the current
form page. Returns a list of processed items. Each item is built by the sub-configuration
keyed by button role.

**Button roles**

-   :typoscript:`previousPage` – previous-page button (only present when not on the first page)
-   :typoscript:`nextPage` – next-page button (only present when not on the last page)
-   :typoscript:`submit` – submit button (only present on the last page)

Within each role block, :typoscript:`HBS_TAG` and :typoscript:`HBS_LABEL` operate on
the rendered :html:`<button>` tag and the translated button label respectively.

**Example**

..  code-block:: typoscript

    navItems = HBS_NAVIGATION
    navItems {
        previousPage {
            label = HBS_LABEL

            name = HBS_TAG
            name.attribute = name

            value = HBS_TAG
            value.attribute = value
        }

        nextPage < .previousPage
        submit < .previousPage
    }
