# sad_spirit/quickform2_json_renderer

This is a renderer implementation for [HTML_QuickForm2](https://github.com/pear/HTML_QuickForm2) package
that converts an instance of `HTML_QuickForm2` to JSON. The resultant JSON representation can be used to rebuild 
the form on client side allowing seamless integration with JavaScript frameworks.

Two main differences between this renderer and built-in `HTML_QuickForm2_Renderer_Array`:
 * The latter returns generated HTML for form elements and the former does not;
 * This renderer does not generate JavaScript code to insert into page, it provides parts that can be used to
   perform client-side validation within framework.

## Usage

```PHP
use sad_spirit\html_quickform2\json_renderer\JsonRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use HTML_QuickForm2_Renderer as Renderer;

Renderer::register('json', JsonRenderer::class);

$form = new \HTML_QuickForm2('form-id');

//
// ... building the form ...
//

if ($form->validate()) {
    //
    // ... processing the form ...
    //
    return new JsonResponse(['success' => true]);
}

/** @var JsonRenderer $renderer */
$renderer = $form->render(Renderer::factory('json'));
return new JsonResponse($renderer->toArray());
```

## JSON structure

TBD

## Limitations

The renderer does not support 
 * Elements that contain JS setup code added via `HTML_QuickForm2_JavascriptBuilder::addElementJavascript()` other
   than `HTML_QuickForm2_Container_Repeat`;
 * `HTML_QuickForm2_Element_Script` elements used to insert inline JS into form.

These should be reimplemented client-side using the framework of choice.
