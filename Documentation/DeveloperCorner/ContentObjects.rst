..  include:: /Includes.rst.txt

..  _custom-content-objects:

=======================
Custom content objects
=======================

If the built-in :typoscript:`HBS_*` content objects do not cover a required value, you
can create your own by extending
:php:`CPSIT\Typo3HandlebarsForms\ContentObject\AbstractHandlebarsFormsContentObject`.

..  _custom-co-implement:

Implementing a custom content object
=====================================

The abstract base class has one abstract method to implement:

..  code-block:: php

    abstract protected function resolve(
        array $configuration,
        ValueResolutionContext $context,
    ): mixed;

:php:`$configuration` is the TypoScript sub-tree below your content object's key.
:php:`$context` exposes the current state:

-   :php:`$context->renderable` – the EXT:form renderable being processed
-   :php:`$context->viewModel` – its view model
-   :php:`$context->formRuntime` – the active form runtime
-   :php:`$context->renderingContext` – the Fluid rendering context

Return any value. Strings are returned directly to the TypoScript tree. Non-string
values (arrays, objects) are automatically stored in the :php:`ValueCollector` under
a placeholder key; :php:`ProcessFormProcessor` replaces the placeholder with the real
value after the full tree has been resolved.

**Example**

..  code-block:: php

    use CPSIT\Typo3HandlebarsForms\ContentObject;
    use Symfony\Component\DependencyInjection;

    #[DependencyInjection\Attribute\AutoconfigureTag(
        'frontend.contentobject',
        ['identifier' => 'MY_CUSTOM_COBJ'],
    )]
    final class MyCustomContentObject extends ContentObject\AbstractHandlebarsFormsContentObject
    {
        protected function resolve(
            array $configuration,
            ContentObject\Context\ValueResolutionContext $context,
        ): mixed {
            return $context->renderable->getProperties()[$configuration['key'] ?? ''] ?? null;
        }
    }

Use it in TypoScript like any built-in content object:

..  code-block:: typoscript

    myValue = MY_CUSTOM_COBJ
    myValue.key = someProperty

..  _custom-co-stdwrap:

stdWrap support
===============

:typoscript:`stdWrap` is handled automatically by the base class for every
:typoscript:`HBS_*` and custom content object. If :php:`resolve()` returns a stringable
value, :typoscript:`stdWrap` is applied directly to it. If the value is non-stringable,
:typoscript:`stdWrap` receives an empty string but the original value is set as the
:typoscript:`currentValue` so TypoScript conditions inside :typoscript:`stdWrap` can
still read it.

..  _custom-co-registration:

Registration
============

The :php:`#[AutoconfigureTag('frontend.contentobject', ['identifier' => '...'])]`
attribute is sufficient for registration when the class lives in a directory that is
autowired. No explicit YAML service definition is needed.

The identifier must be globally unique across all loaded extensions. Prefixing it
with your vendor name (e.g. :typoscript:`MYVENDOR_FOO`) avoids collisions.

..  _custom-co-recurse:

Recursing into the TypoScript tree
====================================

:php:`$context->process()` re-invokes the :php:`ProcessFormProcessor` pipeline with an
arbitrary TypoScript configuration array. Its primary purpose is to let your content
object re-process the current (or a different) renderable under a different configuration
block — for example, to apply a sub-section of your own configuration, or to switch the
active renderable or view model entirely before the pipeline runs.

..  code-block:: php

    protected function resolve(
        array $configuration,
        ContentObject\Context\ValueResolutionContext $context,
    ): mixed {
        $subConfiguration = $configuration['items.'] ?? [];

        if (!is_array($subConfiguration)) {
            return null;
        }

        return $context->process($subConfiguration);
    }

To process a different renderable or view model, pass them as named arguments:

..  code-block:: php

    $context->process($subConfig, renderable: $otherRenderable, viewModel: $otherViewModel);

..  _custom-co-context-stack:

Context stack
=============

The :php:`ContextStack` is a shared singleton (`shared: true` in DI). It holds the
stack of :php:`ValueResolutionContext` objects pushed by :php:`ProcessFormProcessor` as
it walks the TypoScript tree.

Custom content objects receive the current context as a direct argument to
`resolve()` – injecting :php:`ContextStack` directly is not necessary and is
considered internal API.
