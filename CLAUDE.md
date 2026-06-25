# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`handlebars_forms` is a TYPO3 CMS extension (PHP, extension key `handlebars_forms`) that bridges EXT:form's form rendering pipeline with Handlebars templates. It depends on [`cpsit/typo3-handlebars`](https://github.com/CPS-IT/handlebars) for the Handlebars rendering layer.

Build artifacts go to `.Build/` (Composer `vendor-dir` and `bin-dir`). The local development example extension lives in `Build/Packages/hbs-forms-example/`.

## Commands

All commands run via Composer scripts from the project root. Dependencies must be installed first (`composer install`).

**Tests**
```bash
composer test          # run all tests (unit + functional)
composer test:unit     # unit tests only
composer test:functional  # functional tests only

# Run a single test file
composer test:unit -- Tests/Unit/Utility/StringUtilityTest.php
```

**Static analysis & linting**
```bash
composer sca           # PHPStan (level max)
composer lint          # composer normalize + editorconfig + php-cs-fixer + typoscript-lint
composer lint:php      # php-cs-fixer dry-run only
composer lint:typoscript
```

**Auto-fix**
```bash
composer fix           # fix composer + editorconfig + php-cs-fixer
composer fix:php       # php-cs-fixer only
```

**Dependency analysis**
```bash
composer analyze       # shipmonk/composer-dependency-analyser
```

## Architecture

### Rendering pipeline

The extension hooks into EXT:form's rendering at two points:

1. **`HandlebarsFormRenderer`** (`Classes/Domain/Renderer/`) — registered as EXT:form renderer. Resolves a `HandlebarsView` from TypoScript configuration under `plugin.tx_form.handlebarsForms.<formIdentifier|default>`, falls back to a standard Fluid view if no Handlebars configuration is found.

2. **`ProcessFormProcessor`** (`Classes/DataProcessing/`) — a TYPO3 data processor (`identifier: process-form`). The Handlebars template calls this processor which walks the EXT:form renderable tree and resolves each property through custom `HBS_*` content objects.

### Custom content objects (`Classes/ContentObject/`)

All `HBS_*` content objects extend `AbstractHandlebarsFormsContentObject` and are only valid inside a `process-form` data processor context. They read context from `ContextStack` and write resolved (non-string) values back via `ValueCollector` using a string placeholder key.

| Content object | Identifier | Purpose |
|---|---|---|
| `RenderablesContentObject` | `HBS_RENDERABLES` | Iterates child renderables, dispatches to per-type config |
| `PropertyContentObject` | `HBS_PROPERTY` | Reads a property from the renderable or its view model |
| `TagContentObject` | `HBS_TAG` | Reads an HTML attribute from the form element's rendered tag |
| `LabelContentObject` | `HBS_LABEL` | Resolves a translated label |
| `FormValueContentObject` | `HBS_FORM_VALUE` | Reads the submitted/current form value |
| `NavigationContentObject` | `HBS_NAVIGATION` | Resolves prev/next/submit navigation buttons |
| `PassthroughContentObject` | `HBS_PASSTHROUGH` | Renders the renderable via Fluid as-is |
| `ChildrenContentObject` | `HBS_CHILDREN` | Returns child renderables as data |
| `TranslatePropertyContentObject` | `HBS_TRANSLATE_PROPERTY` | Translates a renderable property |
| `TranslateErrorContentObject` | `HBS_TRANSLATE_ERROR` | Translates a validation error message |
| `ValidationResultsContentObject` | `HBS_VALIDATION_RESULTS` | Returns validation results |
| `ViewHelperContentContentObject` | `HBS_VH_CONTENT` | Invokes a Fluid view helper and returns its output |

### Context system (`Classes/ContentObject/Context/`)

`ContextStack` (shared singleton) holds a stack of `ValueResolutionContext` objects. A context is pushed before each `HBS_*` content object render call and popped immediately after. Each `ValueResolutionContext` carries the current `renderable`, `viewModel`, `renderingContext`, and `formRuntime`, plus a closure back into `ProcessFormProcessor::processRenderable()` so content objects can recurse.

`ValueCollector` lets content objects return non-string values (arrays, objects) by storing them under a unique placeholder string key; `ProcessFormProcessor` replaces these placeholders with the real values after the TypoScript tree is fully resolved.

### View models (`Classes/Domain/ViewModel/`)

Every renderable gets a `ViewModel` (implements `ArrayAccess`). Concrete models: `SimpleViewModel`, `FormFieldViewModel`, `FormValueViewModel`, `TagAwareViewModel`, `ViewHelperContainedViewModel`, `FileResourceViewModel`, `StandaloneTagViewModel`, `ViewModelCollection`.

`ViewModelBuilder` (service tag `handlebars_forms.view_model_builder`) is the extension point for mapping a renderable type to a custom view model. Built-in builders cover all default EXT:form elements. Custom builders must implement `ViewModelBuilder` — the interface's `#[AutoconfigureTag]` registers them automatically via Symfony DI.

### Fluid bridge (`Classes/Fluid/`)

`ViewHelperInvoker` programmatically invokes Fluid view helpers outside a normal Fluid rendering context — used by `HBS_PASSTHROUGH` and view model builders that need to call EXT:form view helpers (e.g. `TranslateElementPropertyViewHelper`) to resolve labels and HTML attributes.

`FluidRenderableRenderer` wraps a Fluid template render in a simulated `<f:form>` context so EXT:form's Fluid partials work correctly when called from Handlebars flow.

### TypoScript configuration

The extension ships a TYPO3 Site Set at `Configuration/Sets/Form/`. The key configuration namespace is:

```
plugin.tx_form.handlebarsForms {
  default { ... }          # fallback for all forms
  <formIdentifier> { ... } # override for a specific form
}
```

Each section can have a `dataProcessing.10 = process-form` block that maps renderable types to `HBS_*` content objects. See `Build/Packages/hbs-forms-example/Configuration/Sets/Example/TypoScript/form.typoscript` for a complete real-world example.

## Coding conventions

- PHP 8.2+, strict types everywhere
- Symfony DI attributes (`#[Autoconfigure]`, `#[AutoconfigureTag]`, `#[AutowireIterator]`) are used instead of YAML service configuration for individual classes
- `readonly` classes/properties preferred for value objects and services that don't mutate state
- PHPStan at level max; baseline is `phpstan-baseline.neon`
