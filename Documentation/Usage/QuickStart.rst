..  include:: /Includes.rst.txt

..  _quick-start:

===========
Quick start
===========

This page walks through a minimal working example: rendering a form built with
the TYPO3 Form Framework using a single Handlebars template. It assumes the
extension is already :ref:`installed <installation>`.

..  rst-class:: bignums

#.  Include the site sets

    Add the :yaml:`cpsit/handlebars-forms` site set to your site configuration
    (see :ref:`site-set`), together with a site set providing
    :ref:`content element rendering <site-set-content-rendering>`, e.g.
    :yaml:`cpsit/handlebars-content-element`:

    ..  code-block:: yaml
        :caption: config/sites/<my-site>/config.yaml

        dependencies:
          - cpsit/handlebars-content-element
          - cpsit/handlebars-forms

#.  Configure template paths

    Declare where your :file:`.hbs` files are located using the site settings
    provided by the :yaml:`cpsit/handlebars` site set (from EXT:handlebars), which is
    included automatically by the :yaml:`cpsit/handlebars-forms` site set:

    ..  code-block:: yaml
        :caption: config/sites/<my-site>/settings.yaml

        handlebars.view.templateRootPath: 'EXT:my_sitepackage/Resources/Private/Templates/Handlebars'
        handlebars.view.partialRootPath: 'EXT:my_sitepackage/Resources/Private/Partials/Handlebars'

    ..  seealso::

        :ref:`Template paths <t3exthandlebars:template-paths>` in the EXT:handlebars
        documentation – all configuration methods and their priority order.

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

    Create the template file at the template root path declared above. The default
    template name is :typoscript:`Form` (configurable via the
    :ref:`handlebars_forms.view.templateName <confval-handlebars-forms-view-templatename>`
    site setting), so the file must be named :file:`Form.hbs`.

    The template receives the data built by the :typoscript:`process-form` processor
    directly as its context:

    ..  code-block:: handlebars
        :caption: EXT:my_sitepackage/Resources/Private/Templates/Handlebars/Form.hbs

        <form id="{{formData.id}}"
              method="{{formData.method}}"
              action="{{formData.action}}"
        >
            {{#each fields}}
                {{#if template}}
                    {{> (lookup . 'template')}}
                {{else if content}}
                    {{this.content}}
                {{/if}}
            {{/each}}

            {{#each navItems}}
                {{> '@button' this}}
            {{/each}}

            {{hiddenFields}}
        </form>

    ..  tip::

        The :typoscript:`hiddenFields` value is an HTML string (e.g. page index,
        :html:`trustedProperties`, object identity). It is emitted by EXT:form's
        :fluid:`<f:form>` view helper and must be output without escaping. In Handlebars
        this is done automatically when the value is a :php:`SafeString` – which is
        exactly what :typoscript:`HBS_TAG` (used without :typoscript:`attribute`)
        returns when wrapping tag content.

    ..  note::

        The partials referenced in this example (:file:`@form-field-generic`,
        :file:`@form-field-text` and :file:`@button`) must be created in the partial
        root path as well. The ``@`` prefix looks up a partial by its bare
        filename, see :ref:`Referencing templates and partials <t3exthandlebars:templates-names>`.

#.  Flush caches

    After editing TypoScript or site settings, flush the TYPO3 caches.

..  _quick-start-next-steps:

Next steps
==========

-   :ref:`data-processor` – full reference for the data processor, including key
    resolution rules, conditions and TypoScript references
-   :ref:`content-objects` – reference for all available :typoscript:`HBS_*` content
    objects with their configuration options
-   :ref:`configuration-per-form` – use a different template or data structure for
    a specific form
