<?php
/**
 * Form Helper class file.
 *
 * @copyright     Copyright 2010, alkemann
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace al13_helpers\extensions\helper;

/**
 * Extended Form helper with more functionality
 *
 */
class Form extends \lithium\template\helper\Form {

	/**
	 * Generates a form field with a label, input, and error message (if applicable), all contained
	 * within a wrapping element.
	 *
	 * {{{
	 *  echo $this->form->field('name');
	 *  echo $this->form->field('present', array('type' => 'checkbox'));
	 *  echo $this->form->field(array('email' => 'Enter a valid email'));
	 *  echo $this->form->field(array('name','email','phone'),array('div' => false));
	 *  echo $this->form->field(array(
	 *  		'name' => array('label' => false),
	 *  		'present' => array('type' => 'checkbox')
	 *  	), array('template' => '<li{:wrap}>{:label}{:input}{:error}</li>'
	 *  ));
	 * }}}
	 * @param mixed $name The name of the field to render. If the form was bound to an object
	 *                   passed in `create()`, `$name` should be the name of a field in that object.
	 *                   Otherwise, can be any arbitrary field name, as it will appear in POST data.
	 *                   Alternatively supply an array of fields that will use the same options
	 *                   array($field1 => $label1, $field2, $field3 => $label3)
	 * @param array $options Rendering options for the form field. The available options are as
	 *              follows:
	 *              - `'label'` _mixed_: A string or array defining the label text and / or
	 *                parameters. By default, the label text is a human-friendly version of `$name`.
	 *                However, you can specify the label manually as a string, or both the label
	 *                text and options as an array, i.e.:
	 *                `array('label text' => array('class' => 'foo', 'any' => 'other options'))`.
	 *              - `'type'` _string_: The type of form field to render. Available default options
	 *                are: `'text'`, `'textarea'`, `'select'`, `'checkbox'`, `'password'` or
	 *                `'hidden'`, as well as any arbitrary type (i.e. HTML5 form fields).
	 *              - `'template'` _string_: Defaults to `'template'`, but can be set to any named
	 *                template string, or an arbitrary HTML fragment. For example, to change the
	 *                default wrapper tag from `<div />` to `<li />`, you can pass the following:
	 *                `'<li{:wrap}>{:label}{:input}{:error}</li>'`.
	 *              - `'wrap'` _array_: An array of HTML attributes which will be embedded in the
	 *                wrapper tag.
	 *              - `list` _array_: If `'type'` is set to `'select'`, `'list'` is an array of
	 *                key/value pairs representing the `$list` parameter of the `select()` method.
	 * @return string Returns a form input (the input type is based on the `'type'` option), with
	 *         label and error message, wrapped in a `<div />` element.
	 */
	public function fields($name, array $options = array()) {
		if (!isset($options['wrap']) || !isset($options['wrap']['class'])) {
			$options['wrap']['class'] = 'input';
		}
		if (is_array($name)) {
			$return = '';
			foreach ($name as $field => $label) {
				if (is_numeric($field)) {
					$field = $label;
					unset($label);
				}
				$specificOptions = array();
				if (isset($label) && is_array($label)) {
					$specificOptions = $label + $specificOptions;
					unset($label);
				}
				if (!isset($specificOptions['type']) && in_array($field, array('password', 'password_confirm'))) {
					$specificOptions['type'] = 'password';
				}
				if (isset($specificOptions['type'])
					&& in_array($specificOptions['type'], array('checkbox', 'radio'))
					&& !isset($options['template'])) {
					$specificOptions['template'] = $this->_strings['field-checkbox'];
					$specificOptions['wrap'] = array('class' => 'input '.$specificOptions['type']);
				}
				$return .= $this->field($field, compact('label') + $specificOptions + $options);
			}
			return $return;
		}
		return $this->field($name, $options);
	}

	public function radio($name, array $options = array(), array $list = array()) {
		if (empty($list)) {
			if (!isset($options['checked']) && isset($options['value'])) {
				$value = false;
				if ($name && $this->_binding ) {
					$value = $this->_binding->data($name);
				}
				if ($options['value'] == $value)
					$options['checked'] = true;
			}
			return parent::radio($name,$options);
		}
		$out = '';
		foreach ($list as $value => $label) {
			$itemOptions = $options;
			$itemOptions['id'] = $name.'-'.$label;
			$itemOptions['value'] = $value;
			$out .= '<div class="radio">';
			$out .= $this->radio($name, $itemOptions, array());
			$out .= $this->label($itemOptions['id'], $label);
			$out .= '</div>';
		}
		return $out;
	}
}

?>