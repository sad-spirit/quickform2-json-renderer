# sad_spirit/quickform2_json_renderer

This is a renderer implementation for [HTML_QuickForm2](https://github.com/pear/HTML_QuickForm2) package
that converts an instance of `HTML_QuickForm2` to JSON. The resultant JSON representation can be used to rebuild 
the form on client side allowing seamless integration with JavaScript frameworks.

The main difference between this renderer and built-in `HTML_QuickForm2_Renderer_Array` is that 
the latter returns generated HTML for form elements and the former does not.

## Usage

TBD

## JSON structure

TBD

## Limitations

The renderer does not support 
 * Elements that contain JS setup code added via `HTML_QuickForm2_JavascriptBuilder::addElementJavascript()` other
   than `HTML_QuickForm2_Container_Repeat`
 * `HTML_QuickForm2_Element_Script` used to insert inline JS into form.

These should be reimplemented client-side using the framework of choice.
