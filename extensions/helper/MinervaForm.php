<?php
/**
 * Minerva's Form Helper
 * Extends and adds more to the basic Form helper provided by Lithium.
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 
*/
namespace minerva\extensions\helper;

use lithium\util\Inflector;

class MinervaForm extends \lithium\template\helper\Form {
    
    /**
	 * Output a form section, which basically consists of a set of fields.
	 * It uses Model::schema() and with special "form" keys on each schema field,
	 * it will output the proper form widget for each field. It loops.
	 * It's basic and needs CSS applied to it, but there are a few options than be passed.
	 *
	 * TODO: A more robust helper/system
	 *
	 * @param $schema Array The schema obtained from Model::schema()
	 * @param $position String The position, only certain fields will be displayed based on this
	 * @param $options Array Options that control the HTML output a little bit
	 * @return String HTML form fields with optional fieldset and legend
	*/
	public function form_section($schema=array(), $position=null, $options=array()) {
		// in case it wasn't passed, but it exists in the data in the view template as "fields"
		if(empty($schema))  {
			$data = $this->_context->data();
			$schema = (isset($data['fields'])) ? $data['fields']:array();
		}
		
		$defaults = array('legend' => false, 'fieldset' => false);
		$options += $defaults;
	
		$output = '';
		
		// if there was a fieldset provided...use it for the clas name. if set to false, it won't be used
		if($options['fieldset']) {
			$output .= '<fieldset class="' . $options['fieldset'] . '">';
		}
		
		// if there was a legend provided...use it for the copy in the legend. if set to false, it won't be used
		if($options['legend']) {
			$output .= '<legend>' . $options['legend'] . '</legend>';
		}
		
		// Loop through the schema
		foreach($schema as $k => $v) {
			// IF the 'form' key is even set...
			if(isset($v['form'])) {
				// this default position is "main" so it's not required in the model's schema...but it's a good idea to provide a position
                // note: 'position' => false will not show it... at which point, i'm not sure why the 'form' key was even defined
                $v['form']['position'] = (isset($v['form']['position'])) ? $v['form']['position']:'main';
				if(($v['form']['position'] == $position) && ($v['form']['position'] != false)) {
					// ensure there's some sort of type set (empty string will be regular text input)
                    $v['form']['type'] = (isset($v['form']['type'])) ? $v['form']['type']:'';
					$v['form']['options'] = (isset($v['form']['options'])) ? $v['form']['options']:array();
					$v['form']['list'] = (isset($v['form']['list'])) ? $v['form']['list']:array();
					switch($v['form']['type']) {
						case 'text':
						case 'input':
						default:
							$output .= $this->_context->form->field($k, $v['form']);
							break;
						case 'select':
							$output .= '<div>';
							// for some reason this doesn't work
							/*
							if(isset($v['form']['label'])) {
								$output .= $this->_context->form->label($k, $v['form']['label']);
							}
							*/
							
							// so set the label like this (for now)
							$model = $this->_context->form->binding()->model();
							$meta = $model::meta();
							if(isset($v['form']['label'])) {
								$output .= '<label for="' . $meta['name'] . Inflector::camelize($k) . '">' . $v['form']['label'] . '</label>';
							}
							
							// this seems to be ok, but it doesn't keep options selected...
							// the "value" key in the "options" array has to be set, which we could do from the controller
							// but we'll do it here just in case so we don't have to remember to do so in controller
							// TODO: patch the lithium form helper method
							$field = $this->_context->form->binding()->data($k);
							$v['form']['options']['value'] = (is_object($field)) ? $field->data():$field;
							$output .= $this->_context->form->select($k, $v['form']['list'], $v['form']['options']);
							
							// this shouldn't be necessary...
							/*
							$output .= '<select>';
							foreach($v['form']['list'] as $value => $name) {
								$output .= '<option value="' . $value . '">' . $name . '</option>';
							}
							$output .= '</select>';
							*/
							
							// this seems absolutely fine
							$output .= $this->_context->form->error($k);
							
							$output .= '</div>';
							break;
					}
					
					if(isset($v['form']['help_text'])) {
						$output .= '<div class="help_text">' . $v['form']['help_text'] . '</div>';
					}
				}
			}
	    }
		
		// close off the fieldset (if it wasn't set to false)
		if($options['fieldset']) {
			$output .= '</fieldset>';
		}
		
        return $output;
		
	}
	
}
?>