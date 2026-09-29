..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

..  _requirements:

Requirements
============

-   PHP 8.2 - 8.5
-   TYPO3 13.4 LTS - 14.3 LTS

..  _steps:

Installation
============

Require the extension via Composer (recommended):

..  code-block:: bash

    composer require cpsit/typo3-handlebars-forms

Or download it from the
`TYPO3 extension repository <https://extensions.typo3.org/extension/handlebars_forms>`__.

..  _site-set:

Site set
========

The extension ships a site set that provides default TypoScript configuration and site
settings. Include it in your site's :file:`config.yaml`:

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    dependencies:
      - cpsit/handlebars-forms

The site set depends on the following site sets, which are declared in the extension's
own set, so you do not need to list them separately:

-   :ref:`cpsit/handlebars <t3exthandlebars:site-sets>` (from EXT:handlebars) – provides
    site settings for template paths
-   :ref:`typo3/form <t3extform:quickstartintegrators>` (from EXT:form) – provides the
    TYPO3 Form Framework

..  _site-set-content-rendering:

Content element rendering
-------------------------

Forms are placed on a page using the *Form* content element. Like all plugin content
elements, it is based on :typoscript:`lib.contentElement`, which is not provided by
this extension. Include one of the following site sets to provide it:

-   :yaml:`typo3/fluid-styled-content` (from EXT:fluid_styled_content) – content elements
    are rendered with Fluid, as usual
-   :ref:`cpsit/handlebars-content-element <t3exthandlebars:site-sets>` (from
    EXT:handlebars) – content elements are rendered with Handlebars

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    dependencies:
      - typo3/fluid-styled-content
      - cpsit/handlebars-forms

In both cases, the form itself is rendered with Handlebars.

..  warning::

    If none of these site sets is included (and :typoscript:`lib.contentElement` is not
    defined otherwise), the Form content element renders nothing at all – no error
    message is shown.

Template paths
--------------

Template paths for your own Handlebars templates must be configured in addition, e.g.
by using the site settings :yaml:`handlebars.view.templateRootPath` and
:yaml:`handlebars.view.partialRootPath` provided by the :yaml:`cpsit/handlebars` site
set. All available methods are described in
:ref:`Template paths <t3exthandlebars:template-paths>`.

..  _installation-next-steps:

Next steps
==========

Continue with the :ref:`quick-start` to render your first form with a Handlebars
template.
