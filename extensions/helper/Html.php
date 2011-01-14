<?php
/**
 * Minerva's Html Helper
 * Extends and adds more to the basic Html helper provided by Lithium.
 *
 * @author Tom Maiaroto
 * @website http://www.shift8creative.com
 * @modified 2011-01-10 08:22:00 
 * @created 2011-01-10 08:22:00
 *
*/
namespace minerva\extensions\helper;

use minerva\libraries\util\Util;
use lithium\util\Inflector;

class Html extends \lithium\template\helper\Html {

    public function greeting($name) {
        return "Hello {$name}!";
    }
    
    public function date($value) {
        return date('Y-M-d h:i:s', $value->sec);
    }
    
    /**
     * Returns a list of links to model types' actions (Page types, User types, etc.)
     * By default, with no options specified, this returns a list of links to create any type of page (except core page).
     *
     * @param $model_name String The model name (can be lowercase, the Util class corrects it)
     * @param $action String The controller action
     * @param $options Array Various options that get passed to Util::list_types() and the key "link_options" can contain an array of options for the $this->html->link()
     * @return String HTML list of links
     * @see minerva\libraries\util\Util::list_types()
    */
    public function link_types($model_name='Page', $action='create', $options=array()) {
        $options += array('exclude_minerva' => true, 'link_options' => array());
        $libraries = Util::list_types($model_name, $options);
        $output = '';
        
        (count($libraries) > 0) ? $output .= '<ul>':$output .= '';
	foreach($libraries as $library) {
            if(substr($library, 0, 7) == 'minerva') {
                $model = $library;
            } else {
                $model = 'minerva\libraries\\' . $library;
            }
	    $type = current(explode('\\', $library));
	    if(strtolower($type) == 'minerva') {
		$type = null;
	    }
	    $output .= '<li>' . $this->link($model::display_name(), '/' . Inflector::pluralize($model_name) . '/' . $action . '/' . $type, $options['link_options']) . '</li>';
	}
        (count($libraries) > 0) ? $output .= '</ul>':$output .= '';
        
        return $output;
    }
    
    /**
     * A generic form field input that passes a querystring to the URL for the current page.
     *
     * @options Array Various options for the form and HTML
     * @return String HTML and JS for the form
    */
    public function query_form($options=array()) {
        $options += array('key' => 'q', 'class' => 'search', 'button_copy' => 'Submit', 'div' => 'search_form', 'label' => false);
        $output = '';
        
        $form_id = sha1('asd#@jsklvSx893S@gMp8oi' . time());
        
        $output .= (!empty($options['div'])) ? '<div class="' . $options['div'] . '">':'';
        
            $output .= (!empty($options['label'])) ? '<label>' . $options['label'] . '</label>':'';
            $output .= '<form id="' . $form_id . '" onSubmit="';
                $output .= 'window.location = window.location.href + \'?\' + $(\'#' . $form_id . '\').serialize();';
            $output .= '">';
                $value = (isset($_GET[$options['key']])) ? $_GET[$options['key']]:'';
                $output .= '<input name="' . $options['key'] . '" value="' . $value . '" class="' . $options['class'] . '" />';
                $output .= '<input type="submit" value="' . $options['button_copy'] . '" />';
            $output .= '</form>';
            
        $output .= (!empty($options['div'])) ? '</div>':'';
        
        
        return $output;
    }
    
}
?>