<?php
/**
 * Minerva's Form Helper
 * Extends and adds more to the basic Form helper provided by Lithium.
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 
*/
namespace minerva\extensions\helper;

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
                    $v['form']['type'] = (isset($v['form']['type'])) ? $v['form']['type']:'';
					switch($v['form']['type']) {
						case 'text':
						case 'input':
						default:
							$output .= $this->field($k, $v['form']);
							break;
						case 'select':
							$output .= '<div>';
							if(isset($v['form']['label'])) {
								$output .= $this->label($k, $v['form']['label']);
							}
							$output .= $this->select($k, $v['form']['options']);
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