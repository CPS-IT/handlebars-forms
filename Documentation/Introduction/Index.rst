..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  _what-it-does:

What does it do?
================

The extension provides a way to render forms built with the
:ref:`TYPO3 Form Framework <t3extform:start>`. It allows rendering of
generic forms, defined by a comprehensive TypoScript rendering definition.
This definition can be extended to a specific form to allow customizating the
output of various forms. In addition, the extension allows to modify the
build mechanisms of so called *view models*, which makes the whole concept
very dynamic and flexible.

..  _features:

Features
========

-   **Form elements:** Support for all default form elements of EXT:form
-   **Generic rendering:** One TypoScript rendering definition for all forms
-   **Per-form overrides:** Dedicated templates and data structures for specific forms
-   **Extensibility:** Custom view model builders and content objects for custom
    form elements
-   **Fluid fallback:** Elements without a Handlebars template can be rendered with
    EXT:form's default Fluid partials
-   **Compatibility:** Compatible with TYPO3 13.4 LTS and 14.3 LTS

..  _how-it-works:

How does it work?
=================

The extension hooks into EXT:form at two points:

1.  **Form renderer** – When a form is rendered on the frontend, EXT:form hands it over
    to this extension. Based on the TypoScript configuration at
    :typoscript:`plugin.tx_form.handlebarsForms`, a Handlebars template is selected
    for the form and rendered by EXT:handlebars.

2.  **Data processor** – Before the template is rendered, the :ref:`process-form <data-processor>`
    data processor walks through all form elements and collects the data the template
    needs (IDs, names, labels, values, validation errors, …). *What* is collected is
    defined in TypoScript using dedicated :typoscript:`HBS_*` content objects. The
    result is a plain array that becomes the template's context.

In short: EXT:form keeps handling the form logic (validation, finishers, multi-step
navigation), TypoScript describes the data, and Handlebars takes care of the markup.

..  _support:

Support
=======

There are several ways to get support for this extension:

-   Slack: https://typo3.slack.com/archives/C0281DBRFCZ
-   GitHub: https://github.com/CPS-IT/handlebars-forms/issues

..  _security-policy:

Security Policy
===============

Please read our `security policy <https://github.com/CPS-IT/handlebars-forms/blob/main/SECURITY.md>`__
if you discover a security vulnerability in this extension.

..  _license:

License
=======

This extension is licensed under
`GNU General Public License 2.0 (or later) <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>`_.
