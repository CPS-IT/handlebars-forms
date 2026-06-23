..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

..  _site-settings:

Site settings
=============

The following settings are exposed through the site set and can be overridden per site.

..  confval-menu::
    :name: site-settings
    :display: table
    :type:
    :default:

    ..  confval:: handlebars_forms.view.templateName
        :name: handlebars_forms-view-templateName
        :type: string
        :default: :typoscript:`Form`

        Name of the Handlebars template used for rendering a form when no per-form
        template is configured. Corresponds to a template file in the configured
        Handlebars template root paths, e.g. :file:`Form.hbs`.

        ..  note::

            The default :file:`Form.hbs` template serves as placeholder to allow a smooth
            form rendering integration. It only shows a rendering warning and must be
            overridden by a concrete template which handles the whole rendering.

..  _typoscript:

TypoScript
==========

The site set sets up a TypoScript object at :typoscript:`plugin.tx_form.handlebarsForms`,
where integrators define how each form is rendered. The structure is:

..  code-block:: typoscript

    plugin.tx_form.handlebarsForms {
        # Applied to every form (fallback)
        default {
            templateName = {$handlebars_forms.view.templateName}

            dataProcessing {
                10 = process-form
                10 {
                    # ... HBS_* configuration
                }
            }
        }

        # Override for a specific form – merged on top of "default"
        my_contact_form {
            templateName = ContactForm
        }
    }

When a form is rendered, the extension looks up configuration blocks in the following
order. All matching blocks are merged, with later entries winning:

1.  :typoscript:`default` – applied to every form
2.  Form unique identifier (e.g. :typoscript:`my-contact-form-123`)
3.  Original form identifier before suffixes are appended (e.g. :typoscript:`my-contact-form`)
4.  Form persistence identifier (the YAML file path, e.g.
    :typoscript:`EXT:my_extension/Resources/Private/Forms/ContactForm.form.yaml`)

The :typoscript:`templateName` key names the Handlebars template file (without extension)
that receives the data produced by the :ref:`process-form <process-form-processor>` processor
as its template context.

..  note::

    The :typoscript:`dataProcessing` block follows the same TypoScript data-processor syntax
    used elsewhere in TYPO3 (e.g. inside :typoscript:`FLUIDTEMPLATE`). The
    :ref:`process-form <process-form-processor>` processor identifier is provided by this
    extension.

See :ref:`Conditions <process-form-conditions>` in the processor reference for details on
using :typoscript:`if` to conditionally omit keys from the output.
