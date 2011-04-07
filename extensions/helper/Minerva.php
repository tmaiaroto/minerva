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

use minerva\extensions\util\Util;
use lithium\util\Inflector;
use li3_flash_message\extensions\storage\FlashMessage;
use lithium\template\View;
use lithium\core\Libraries;

class Minerva extends \lithium\template\helper\Html {

	/**
	 * We want to use our own little helper so that everything is shorter to write and
	 * so we can use fancier messages with JavaScript.
	 *
	 * @param $options
	 * @return HTML String
	*/
	public function flash($options=array()) {
		$defaults = array(
			'key' => 'minerva_admin',
			// options for the layout template, some of these options are specifically for the pnotify jquery plugin
			'options' => array(
				'type' => 'growl',
				'fade_delay' => '8000',
				'pnotify_opacity' => '.8'
			)
		);
		$options += $defaults;
		
		$message = '';
		
		$flash = FlashMessage::read($options['key']);
		if (!empty($flash)) {
			$message = $flash['message'];
			FlashMessage::clear($options['key']);
		}
		
		$view = new View(array(
			'paths' => array(
				'template' => '{:library}/views/elements/{:template}.{:type}.php',
				'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
			)
		));
		
		return $view->render('all', array('options' => $options['options'], 'message' => $message), array(
			'template' => 'flash_message',
			'type' => 'html',
			'layout' => 'blank',
			'library' => 'minerva'
		));

	
	}

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
     * @param $model_name String The model name (can be lowercase, the Inflector corrects it)
     * @param $action String The controller action
     * @param $options Array Various options that get passed to Util::list_types() and the key "link_options" can contain an array of options for the $this->html->link()
     * @return String HTML list of links
     * @see minerva\libraries\util\Util::list_types()
    */
    public function link_types($model_name='Page', $action='create', $options=array()) {
        $options += array('include_minerva' => true, 'admin' => true, 'library' => 'minerva', 'link_options' => array());
        $output = '';
		
		$model_class_name = Inflector::classify($model_name);
		$models = Libraries::locate('minerva_models', $model_class_name);
		$controller = $options['library'] . '.' . strtolower(Inflector::pluralize($model_name));
		
        $output .= '<ul>';
		
		// if include_minerva is true, then show the basic link... ie.  /minerva/pages/create
		if($options['include_minerva']) {
			$output .= '<li>' . $this->link($model_class_name, array('admin' => $options['admin'], 'controller' => $controller, 'action' => $action), $options['link_options']) . '</li>';
		}
		
		if(is_array($models)) {
			foreach($models as $model) {
				$class_pieces = explode('\\', $model);
				$type = $class_pieces[0];
				$output .= '<li>' . $this->link($model::display_name(), array('admin' => $options['admin'], 'controller' => $controller, 'action' => $action, 'document_type' => $type), $options['link_options']) . '</li>';
			}
		} else {
			$class_pieces = explode('\\', $models);
			$type = $class_pieces[0]; // the library name serves as the x_type
			$output .= '<li>' . $this->link($models::display_name(), array('admin' => $options['admin'], 'controller' => $controller, 'action' => $action, 'document_type' => $type), $options['link_options']) . '</li>';
		}
        $output .= '</ul>';
		
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