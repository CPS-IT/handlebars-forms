..  include:: /Includes.rst.txt

..  _content-objects:

================
Content objects
================

The extension registers the following custom content objects (:typoscript:`HBS_*`). They
are only useful inside a :typoscript:`process-form` data processor block; using them
elsewhere may log a warning and return an empty string.

Every :typoscript:`HBS_*` content object supports the standard :ref:`stdWrap <t3tsref:stdwrap>`
sub-key. The value resolved by the content object is passed through :typoscript:`stdWrap`
before being stored in the processed data array.

..  toctree::
    :maxdepth: 1

    Renderables
    Property
    Tag
    Label
    FormValue
    Navigation
    Passthrough
    Children
    TranslateProperty
    TranslateError
    ValidationResults
    VhContent
