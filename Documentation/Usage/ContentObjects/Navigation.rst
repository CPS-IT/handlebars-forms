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
-   :typoscript:`submit` – submit button (only present on the last page or if explicitly enabled)

The submit button is rendered on all pages if :typoscript:`submit.renderOnAllPages`
is enabled (see below).

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

**Options**

..  confval:: renderOnAllPages
    :name: co-hbs-navigation-renderOnAllPages
    :type: boolean
    :Default: 0

    Only valid within the :typoscript:`submit` block. If enabled, the submit button
    is added on every form page, in addition to the next-page and previous-page
    buttons. By default, it is only added on the last page.

    ..  code-block:: typoscript

        navItems = HBS_NAVIGATION
        navItems {
            submit {
                renderOnAllPages = 1
                label = HBS_LABEL
            }
        }
