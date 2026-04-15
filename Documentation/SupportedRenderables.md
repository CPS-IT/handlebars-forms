# Supported renderables

> [!NOTE]
> Renderables are converted to so-called "view models". Each view model represents the
> default view implementation of a renderable, based on the Fluid templates shipped by
> EXT:form. Note that some renderables might be represented by various view model
> implementations, based on specific aspects outlined below.

> [!TIP]
> You can also create custom view model builders by implementing the
> [`Domain\ViewModel\Builder\ViewModelBuilder`](Classes/Domain/ViewModel/Builder/ViewModelBuilder.php)
> interface.

## [`AdvancedPassword`](Classes/Domain/ViewModel/Builder/PasswordViewModelBuilder.php)

<dl>
<dt>

(a) `ViewModelCollection`

</dt>
<dd>

Contains view models, reflecting both password fields:

| Name                | Type                                    | Description                                                                                                                        |
|---------------------|-----------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| `passwordField`     | `ViewHelperContainedViewModel`          | Contains result from `<formvh:form.password>` view helper invocation for password field.                                           |
| `confirmationField` | (a)&nbsp;`ViewHelperContainedViewModel` | Result from `<formvh:form.password>` view helper invocation for password confirmation field.                                       |
|                     | (b)&nbsp;`FormFieldViewModel`           | Combination of confirmation label and result from `<formvh:form.password>` view helper invocation for password confirmation field. |

</dd>
</dl>

## [`Checkbox`](Classes/Domain/ViewModel/Builder/CheckboxViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.checkbox>` view helper invocation.

</dd>
</dl>

## [`ContentElement`](Classes/Domain/ViewModel/Builder/ContentElementViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<f:cObject>` view helper invocation.

</dd>
<dt>

(b) `SimpleViewModel`

</dt>
<dd>

If configured content element UID is invalid.

</dd>
</dl>

## [`CountrySelect`](Classes/Domain/ViewModel/Builder/CountrySelectViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.countrySelect>` view helper invocation.

</dd>
</dl>

## [`DatePicker`](Classes/Domain/ViewModel/Builder/DatePickerViewModelBuilder.php)

> [!IMPORTANT]
> TODO: Still missing!

## [`Fieldset`](Classes/Domain/ViewModel/Builder/FieldsetViewModelBuilder.php)

<dl>
<dt>

(a) `StandaloneTagViewModel`

</dt>
<dd>

Contains the `<fieldset>` tag with class name(s) and additional attributes.

</dd>
</dl>

## [`FileUpload`, `ImageUpload`](Classes/Domain/ViewModel/Builder/FileUploadViewModelBuilder.php)

<dl>
<dt>

(a) `ViewModelCollection`

</dt>
<dd>

If uploaded resource can be resolved. Contains two or three view models:

| Name                   | Type                           | Description                                                                                      |
|------------------------|--------------------------------|--------------------------------------------------------------------------------------------------|
| `uploadField`          | `ViewHelperContainedViewModel` | Contains result from `<formvh:form.uploadedResource>` view helper invocation for password field. |
| `resource`             | `FileResourceViewModel`        | References file upload, which is an instance of `FileReference` or `PseudoFileReference`.        |
| `resourcePointerField` | `StandaloneTagViewModel`       | Optional. References hidden `<input>` field with resource pointer, if available.                 |

</dd>
<dt>

(b) `ViewHelperContainedViewModel`

</dt>
<dd>

If uploaded resource cannot be resolved. Contains result from `<formvh:form.uploadedResource>`
view helper invocation.

</dd>
</dl>

## [`Form`](Classes/Domain/ViewModel/Builder/FormViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form>` view helper invocation.

</dd>
</dl>

## [`Hidden`](Classes/Domain/ViewModel/Builder/HiddenViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.hidden>` view helper invocation.

</dd>
</dl>

## [`MultiCheckbox`](Classes/Domain/ViewModel/Builder/MultiCheckboxViewModelBuilder.php)

<dl>
<dt>

(a) `ViewModelCollection`

</dt>
<dd>

Contains view models which reflect all available options, each as one of:

| Type                           | Description                                                                                                             |
|--------------------------------|-------------------------------------------------------------------------------------------------------------------------|
| `FormFieldViewModel`           | If label is available. Contains a combination of label and result from `<formvh:form.checkbox>` view helper invocation. |
| `ViewHelperContainedViewModel` | If associated label is invalid or missing. Contains result from `<formvh:form.checkbox>` view helper invocation.        |

</dd>
</dl>

## [`Password`](Classes/Domain/ViewModel/Builder/PasswordViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.password>` view helper invocation.

</dd>
</dl>

## [`RadioButton`](Classes/Domain/ViewModel/Builder/RadioViewModelBuilder.php)

<dl>
<dt>

(a) `ViewModelCollection`

</dt>
<dd>

Contains view models which reflect all available options, each as one of:

| Type                           | Description                                                                                                         |
|--------------------------------|---------------------------------------------------------------------------------------------------------------------|
| `FormFieldViewModel`           | If label is available. Contains a combination of label and result from `<formvh:form.radio>` view helper invocation. |
| `ViewHelperContainedViewModel` | If associated label is invalid or missing. Contains result from `<formvh:form.radio>` view helper invocation.        |

</dd>
</dl>

## [`SingleSelect`, `MultiSelect`](Classes/Domain/ViewModel/Builder/SelectViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.select>` view helper invocation. Includes available
`<option>` tags as children of type `StandaloneTagViewModel`.

</dd>
</dl>

## [`StaticText`](Classes/Domain/ViewModel/Builder/StaticTextViewModelBuilder.php)

<dl>
<dt>

(a) `FormFieldViewModel`

</dt>
<dd>

If label is available. Contains a combination of label and `<p>` tag.

</dd>
<dt>

(b) `StandaloneTagViewModel`

</dt>
<dd>

If label is invalid or missing. Contains `<p>` tag with class and text.

</dd>
</dl>

## [`Textarea`](Classes/Domain/ViewModel/Builder/TextareaViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.textarea>` view helper invocation.

</dd>
</dl>

## [`Text`, `Date`, `Email`, `Number`, `Telephone`, `Url`](Classes/Domain/ViewModel/Builder/TextfieldViewModelBuilder.php)

<dl>
<dt>

(a) `ViewHelperContainedViewModel`

</dt>
<dd>

Contains result from `<formvh:form.textfield>` view helper invocation.

</dd>
</dl>
