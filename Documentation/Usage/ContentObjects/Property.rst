..  include:: /Includes.rst.txt

..  _co-hbs-property:

=============
HBS\_PROPERTY
=============

Reads a property from the current renderable, its view model, or the form runtime
using :php:`TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider::getByPath()`. Returns
whatever type the property holds (string, array, object, …).

**Configuration**

..  confval-menu::
    :name: hbs-property
    :display: table
    :type:
    :default:

    ..  confval:: path
        :name: hbs-property-path
        :type: string
        :default: *(none)*

        Property path. Supports dotted-path notation for nested access (e.g.
        :typoscript:`renderingOptions.foo`).

    ..  confval:: subject
        :name: hbs-property-subject
        :type: string
        :default: :typoscript:`renderable`

        The object to read from. One of:

        -   :typoscript:`renderable` – the current EXT:form renderable (default)
        -   :typoscript:`viewModel` – the view model built for this renderable
        -   :typoscript:`formRuntime` – the form runtime instance

**Example**

..  code-block:: typoscript

    renderableType = HBS_PROPERTY
    renderableType.path = type

    # Read from the view model instead
    resourcePointerFields = HBS_PROPERTY
    resourcePointerFields {
        subject = viewModel
        path = children.resourcePointerFields
    }
