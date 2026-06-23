..  include:: /Includes.rst.txt

..  _process-form-processor:

====================================
:typoscript:`process-form` processor
====================================

The :typoscript:`process-form` data processor is the core of the extension. It resolves
each key in its TypoScript configuration through content objects in the context of the
top-level renderable (:php:`FormRuntime`), producing a plain PHP array that becomes the
Handlebars template context.

The data processor is registered under the identifier :typoscript:`process-form` and can
be used inside a :typoscript:`dataProcessing` block:

..  code-block:: typoscript

    plugin.tx_form.handlebarsForms.default {
        dataProcessing {
            10 = process-form
            10 {
                # processor configuration
            }
        }
    }

----

..  contents::
    :local:

..  _process-form-key-resolution:

Key resolution
==============

The processor iterates all keys in its configuration block and resolves each one by
the following rules, applied in order:

1.  **Array value (no string sibling)** – the sub-tree is processed recursively. The
    result is stored as a nested array under the key name (without trailing dot).

2.  **String value matching a registered content object** – the content object is
    rendered with the dotted sub-tree as its configuration. The return value is stored
    under the key name.

3.  **String value not matching any content object** – the raw string is stored as-is.

These rules mean the structure of the TypoScript block directly mirrors the structure
of the resulting PHP array:

..  code-block:: typoscript

    10 = process-form
    10 {
        # Rule 1: nested array
        formData {
            id = HBS_TAG
            id.attribute = id

            method = HBS_TAG
            method.attribute = method
        }

        # Rule 2: content object
        label = HBS_LABEL

        # Rule 3: literal string
        staticKey = staticValue
    }

Produces:

..  code-block:: php

    [
        'formData' => [
            'id'     => '…',   // resolved by HBS_TAG
            'method' => '…',   // resolved by HBS_TAG
        ],
        'label'     => '…',    // resolved by HBS_LABEL
        'staticKey' => 'staticValue',
    ]

..  note::

    A key is only written to the output array if its resolved value is not :php:`null`.
    Content objects that cannot resolve a value (e.g. :typoscript:`HBS_TAG` used on a
    view model that is not a :php:`TagAwareViewModel`) return :php:`null` and the key is
    silently omitted.

..  _process-form-content-object-config:

Content object configuration
=============================

Configuration for a content object is placed in the dotted sub-tree below the key:

..  code-block:: typoscript

    id = HBS_TAG
    id.attribute = id

    subject = HBS_PROPERTY
    subject.path = type
    subject.subject = renderable

The dotted sub-tree also supports :typoscript:`stdWrap`, which is applied to the
resolved value after the content object returns:

..  code-block:: typoscript

    label = HBS_LABEL
    label.stdWrap.case = upper

..  _process-form-conditions:

Conditions
==========

:typoscript:`if` is supported at two levels and behaves like the built-in
:ref:`TypoScript function <t3tsref:if>`.

It can be placed directly in the processor configuration (or in any nested sub-tree).
When the condition evaluates to :typoscript:`false`, the entire block is skipped and
:php:`null` is returned for its parent key:

..  code-block:: typoscript

    fields = HBS_RENDERABLES
    fields {
        Fieldset {
            if.isTrue = 0
        }
    }

The :typoscript:`currentValue` extension in :typoscript:`if` allows the condition to
be evaluated against a value resolved by the processor itself rather than a static
TypoScript value. It accepts a content object expression using the same syntax as the
main configuration:

..  code-block:: typoscript

    someField {
        if {
            currentValue = HBS_PROPERTY
            currentValue.path = required
            isTrue.current = 1
        }

        label = HBS_LABEL

        value = HBS_TAG
        value.attribute = value
    }

..  _process-form-ts-references:

TypoScript references
=====================

TypoScript copy (:typoscript:`<`) and reference (:typoscript:`=<`) operators are
fully supported. References are resolved before any key in a block is processed,
so the merged configuration is what the content objects see:

..  code-block:: typoscript

    Email < .Text

    navItems = HBS_NAVIGATION
    navItems {
        previousPage {
            # ...
        }
        nextPage < .previousPage
        submit < .previousPage
    }

    fields =< plugin.tx_form.handlebarsForms.default.dataProcessing.10.fields

..  _process-form-renderable-context:

Renderable context
==================

Each key in the processor configuration is resolved in the context of the current
renderable. At the top level this is the :php:`FormRuntime` (i.e. the whole form).
When :typoscript:`HBS_RENDERABLES` iterates children, each child key is resolved in
the context of that child renderable and its view model.

The active renderable and view model are accessible to all :typoscript:`HBS_*` content
objects through the :php:`ValueResolutionContext` they receive. This is what allows
:typoscript:`HBS_TAG` to read from the rendered tag of the *current* element rather
than a global one, and why the same :typoscript:`HBS_LABEL` call returns a different
label for each iteration of :typoscript:`HBS_RENDERABLES`.
