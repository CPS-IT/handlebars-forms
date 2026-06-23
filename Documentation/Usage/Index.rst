..  include:: /Includes.rst.txt

..  _usage:

=====
Usage
=====

This section explains how to set up and use EXT:handlebars_forms in a TYPO3 project.

The extension works in two phases:

1.  **Form renderer** – :php:`HandlebarsFormRenderer` is registered with EXT:form.
    When a form is rendered on the frontend, EXT:form delegates rendering to this
    class, which resolves a Handlebars view based on the TypoScript configuration
    under :typoscript:`plugin.tx_form.handlebarsForms`.

2.  **Data processor** – The Handlebars template is expected to call the
    :typoscript:`process-form` data processor. This processor walks the EXT:form
    renderable tree and builds a plain PHP array from it using :typoscript:`HBS_*`
    content objects. The resulting array is passed to the Handlebars template as its
    context.

..  toctree::
    :maxdepth: 2

    QuickStart
    ProcessFormProcessor
    ContentObjects
