..  include:: /Includes.rst.txt

..  _custom-view-model-builders:

===========================
Custom view model builders
===========================

Every EXT:form renderable that reaches :typoscript:`HBS_RENDERABLES` is converted to a
:ref:`view model <view-models>` before TypoScript processes it. The conversion is
handled by *view model builders*: classes that implement
:php:`CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder\ViewModelBuilder`.

When no registered builder claims a renderable, a plain :php:`SimpleViewModel` is
used as the fallback.

..  _custom-vmb-implement:

Implementing the interface
==========================

The :php:`ViewModelBuilder` interface declares two methods:

..  code-block:: php

    interface ViewModelBuilder
    {
        public function build(
            RootRenderableInterface $renderable,
            RenderingContext $renderingContext,
        ): ViewModel;

        public function supports(RootRenderableInterface $renderable): bool;
    }

:php:`supports()` is called first; return :php:`true` only for the renderable types
your builder handles. :php:`build()` is then called to produce the view model.

The easiest starting point is to extend
:php:`CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder\AbstractViewModelBuilder`.
It takes care of the boilerplate:

-   Wraps the rendering inside a simulated :fluid:`<formvh:renderRenderable>` context
    so EXT:form's state is correct.
-   Applies grid-column classes when the renderable is inside a :php:`GridRow`.
-   Provides :php:`renderAdditionalAttributes()` to resolve :php:`fluidAdditionalAttributes`
    from the element's properties.

Override :php:`renderRenderable()` to produce your custom view model; return :php:`null`
from it to fall back to the default :php:`ViewHelperContainedViewModel` that wraps
the full rendered output of the view helper.

**Minimal example**

..  code-block:: php

    use CPSIT\Typo3HandlebarsForms;
    use TYPO3\CMS\Fluid;
    use TYPO3\CMS\Form;

    final class RatingViewModelBuilder extends Typo3HandlebarsForms\Domain\ViewModel\Builder\AbstractViewModelBuilder
    {
        protected array $supportedTypes = ['Rating'];

        protected function renderRenderable(
            Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
            Fluid\Core\Rendering\RenderingContext $renderingContext,
        ): ?Typo3HandlebarsForms\Domain\ViewModel\ViewModel {
            $result = $this->viewHelperInvoker->invoke(
                $renderingContext,
                Fluid\ViewHelpers\Form\TextfieldViewHelper::class,
                [
                    'type'     => 'number',
                    'property' => $renderable->getIdentifier(),
                    'id'       => $renderable->getUniqueIdentifier(),
                    'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
                ],
            );

            return new Typo3HandlebarsForms\Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);
        }
    }

..  _custom-vmb-registration:

Registration
============

The :php:`ViewModelBuilder` interface carries a
:php:`#[AutoconfigureTag('handlebars_forms.view_model_builder')]` attribute. Any class that
implements the interface is therefore **registered automatically** via Symfony DI when
autowiring is enabled (the default for extensions that include a :file:`Configuration/Services.yaml`
with `autowire: true`).

No explicit YAML service definition is needed.

..  _custom-vmb-priority:

Builder priority
================

When multiple builders claim the same renderable type via `supports()`, the first
one in the service iterator wins. The iterator order is determined by the Symfony DI
`priority <https://symfony.com/doc/current/service_container/tags.html#tagged-services-with-priority>`__
tag attribute. Built-in builders are registered without an explicit priority (i.e. priority 0).

To ensure your builder runs before a built-in one, set a higher priority:

..  code-block:: yaml

    # Configuration/Services.yaml
    Vendor\MyExtension\Domain\ViewModel\Builder\RatingViewModelBuilder:
        tags:
            - name: handlebars_forms.view_model_builder
              priority: 10

..  _custom-vmb-view-model-types:

Choosing a view model type
==========================

Pick the view model type that best matches what your TypoScript configuration will
consume:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Type
        -   When to use

    *   -   :php:`ViewHelperContainedViewModel`
        -   Wraps the rendered tag from a Fluid view helper. Use when
            :typoscript:`HBS_TAG` and :typoscript:`HBS_VH_CONTENT` need to read from it.

    *   -   :php:`StandaloneTagViewModel`
        -   Wraps a manually-built :php:`TagBuilder`. Use for composite elements
            such as :typoscript:`Fieldset` where you construct the tag yourself.

    *   -   :php:`FormFieldViewModel`
        -   Combines a label (as :php:`ViewHelperInvocationResult`) with a child
            view model. Use when :typoscript:`HBS_LABEL` should return the
            view-helper-resolved label rather than the raw renderable label.

    *   -   :php:`ViewModelCollection`
        -   Holds a named map of child view models. Use for multi-part elements
            such as :typoscript:`AdvancedPassword` or :typoscript:`FileUpload` where
            the template needs to access named children.
            :typoscript:`HBS_CHILDREN` iterates over these.

    *   -   :php:`SimpleViewModel`
        -   Thin wrapper over a renderable with no extra state. Use as a fallback
            or for elements whose properties are accessed entirely via
            :typoscript:`HBS_PROPERTY`.
