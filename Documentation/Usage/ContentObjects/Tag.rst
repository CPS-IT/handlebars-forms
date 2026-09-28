..  include:: /Includes.rst.txt

..  _co-hbs-tag:

========
HBS\_TAG
========

Reads an HTML attribute (or the inner content) from the tag rendered by the current
renderable's view model. The view model must implement :php:`TagAwareViewModel`; this is
the case for all view models built by the built-in :php:`ViewModelBuilder` implementations.
Returns a :php:`SafeString` (Handlebars will not escape the value).

The tag reflects the final output of the Fluid ViewHelper responsible for rendering the
renderable. For example, the root :php:`FormRuntime` object is rendered by the
:fluid:`<formvh:form>` view helper, so :typoscript:`HBS_TAG` on it returns attributes (or
content) of the :html:`<form>` tag that view helper produces. Similarly, a :typoscript:`Text`
element is rendered by :fluid:`<f:form.textfield>`, so :typoscript:`HBS_TAG` exposes the
attributes of the resulting :html:`<input>` tag.

**Configuration**

..  confval-menu::
    :name: hbs-tag
    :display: table
    :type:
    :default:

    ..  confval:: attribute
        :name: hbs-tag-attribute
        :type: string
        :default: *(none)*

        Name of the HTML attribute to read. If omitted, the inner content of the
        rendered tag is returned instead.

**Example**

..  code-block:: typoscript

    # Read the "id" attribute of the rendered <input> tag
    id = HBS_TAG
    id.attribute = id

    # Read the inner HTML of the rendered tag (e.g. <textarea> content)
    content = HBS_TAG
