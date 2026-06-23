..  include:: /Includes.rst.txt

..  _quick-start:

===========
Quick start
===========

This page walks through the minimum steps required to render a form with Handlebars.

..  rst-class:: bignums-xxl

#.  Include the site set

    Follow the :ref:`site set instructions <site-set>` in the installation guide to add
    :typoscript:`cpsit/handlebars-forms` to your site's dependencies.

#.  Configure TypoScript

    Add a :typoscript:`dataProcessing` block under
    :typoscript:`plugin.tx_form.handlebarsForms.default` that maps EXT:form renderables
    to :typoscript:`HBS_*` content objects. The array built by the processor becomes the
    Handlebars template context.

    The example below produces a :typoscript:`fields` array and a :typoscript:`navItems`
    array from the current form page, plus a :typoscript:`hiddenFields` string:

    ..  code-block:: typoscript

        plugin.tx_form.handlebarsForms {
            default {
                dataProcessing {
                    10 = process-form
                    10 {
                        formData {
                            id = HBS_TAG
                            id.attribute = id

                            action = HBS_TAG
                            action.attribute = action

                            method = HBS_TAG
                            method.attribute = method
                        }

                        fields = HBS_RENDERABLES
                        fields {
                            default {
                                template = @form-field-generic

                                id = HBS_TAG
                                id.attribute = id

                                name = HBS_TAG
                                name.attribute = name

                                label = HBS_LABEL

                                value = HBS_TAG
                                value.attribute = value
                            }

                            # Per-type overrides inherit from default via TypoScript copy operator
                            Text < .default
                            Text {
                                template = @form-field-text
                            }

                            Email < .Text

                            # Suppress Honeypot in the template; render it verbatim instead
                            Honeypot {
                                content = HBS_PASSTHROUGH
                            }
                        }

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

                        hiddenFields = HBS_TAG
                    }
                }
            }
        }

#.  Create a Handlebars template

    Create a :file:`.hbs` file in the Handlebars template root path configured by your
    EXT:handlebars installation. The default template name is :typoscript:`Form`
    (configurable via the
    :ref:`handlebars_forms.view.templateName <confval-handlebars-forms-view-templatename>`
    site setting), so the file should be named :file:`Form.hbs`.

    The template receives the data built by the :typoscript:`process-form` processor
    directly as its context:

    ..  code-block:: handlebars

        <form id="{{formData.id}}"
              method="{{formData.method}}"
              action="{{formData.action}}"
        >
            {{#each fields}}
                {{#if template}}
                    {{> (lookup . 'template')}}
                {{elseif content}}
                    {{this.content}}
                {{/if}}
            {{/each}}

            {{#each navItems}}
                {{> '@button' this}}
            {{/each}}

            {{hiddenFields}}
        </form>

    ..  tip::

        The :typoscript:`hiddenFields` value is an HTML string (e.g. CSRF token, page
        index). It is emitted by EXT:form's :fluid:`<f:form>` view helper and must be output
        without escaping. In Handlebars this is done automatically when the value is a
        :php:`SafeString` – which is exactly what :typoscript:`HBS_TAG` (used without
        :typoscript:`attribute`) returns when wrapping tag content.

..  seealso::

    -   :ref:`process-form-processor` – full reference for the data processor, including
        key resolution rules, conditions and TypoScript references
    -   :ref:`content-objects` – reference for all available :typoscript:`HBS_*` content
        objects with their configuration options

..  _quick-start-per-form:

Per-form overrides
==================

To use a different template or a different data structure for a specific form, add a
block keyed by the form identifier. It is merged on top of :typoscript:`default`:

..  code-block:: typoscript

    plugin.tx_form.handlebarsForms {
        my_contact_form {
            templateName = ContactForm

            dataProcessing {
                10 = process-form
                10 {
                    fields =< plugin.tx_form.handlebarsForms.default.dataProcessing.10.fields
                    fields {
                        # Extra field type only present in this form
                        Rating {
                            template = @form-field-rating

                            value = HBS_TAG
                            value.attribute = value
                        }
                    }
                }
            }
        }
    }
