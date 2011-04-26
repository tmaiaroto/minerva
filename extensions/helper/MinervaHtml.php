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

class MinervaHtml extends \lithium\template\helper\Html {

	/**
	 * Set some useful data for this helper.
	*/
	public function _init() {
		parent::_init();
		
		//$minerva_config = Libraries::get('minerva'); // not currently needed with the constants
		$this->base = MINERVA_BASE_URL;
		$this->admin_prefix = MINERVA_ADMIN_PREFIX;
		// setting core_minerva_models is just helpful for convenience with display reasons in the methods below
		$this->core_minerva_models = array(
			'Page',
			'Block',
			'User',
			'Menu'
		);
	}

	/**
	 * Simply returns the admin prefix.
	 * Of course the admin_prefix could also be returned by using:
	 * $this->minervaHtml->admin_prefix
	 *
	 * @return String The admin prefix
	*/
	public function admin_prefix() {
		return $this->admin_prefix;
	}

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
	
	/**
	 * A little helpful method that returns the current URL for the page.
	 * 
	 * @param $include_domain Boolean Whether or not to include the domain or just the request uri (true by default)
	 * @param $include_querystring Boolean Whether or not to also include the querystring (true by default)
	 * @return String
	*/
	public function here($include_domain=true, $include_querystring=true, $include_paging=true) {
		$pageURL = 'http';
		if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'] == 'on')) {$pageURL .= 's';}
		$pageURL .= '://';
		if ($_SERVER['SERVER_PORT'] != '80') {
			$pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		
		if($include_domain === false) {
			$pageURL = $_SERVER['REQUEST_URI'];
		}
		
		// always remove the querystring, we'll tack it back on at the end if we want to keep it
		if($include_querystring === false) {
			parse_str($_SERVER['QUERY_STRING'], $vars);
			unset($vars['url']);
			$querystring = http_build_query($vars);
			if(!empty($querystring)) {
				$pageURL = substr($pageURL, 0, -(strlen($querystring) + 1));
			}
		}
		
		// note, this also ditches the querystring
		if($include_paging === false) {
			$base_url = explode('/', $pageURL);
			$base_url = array_filter($base_url, function($val) { return (!stristr($val, 'page:') && !stristr($val, 'limit:')); });
			$pageURL = implode('/', $base_url);
		}
		
		return $pageURL;
	}

    public function greeting($name) {
        return "Hello {$name}!";
    }
    
	/**
	 * Show's the owner's full name and optionally links to the user's record.
	 * 
	 * @param $document Object
	 * @param $options Array An array of options, such as whether or not to return a link to the user's record, etc.
	 * @return String HTML
	*/
	public function owner_name($document=false, array $options=array()) {
		$defaults = array(
			'link_to_user_record' => true
		);
		$options += $defaults;
		
		$request = $this->_context->request();
		$name = '';
		
		if($document) {
			$name = (isset($document->_owner->_name) && !empty($document->_owner->_name)) ? $document->_owner->_name:$name;
		}
		
		if($options['link_to_user_record']) {
			if(isset($request->params['admin'])) {
				$name = $this->link($name, array('admin' => $this->admin_prefix(), 'library' => 'minerva', 'controller' => 'users', 'action' => 'read', 'url' => $document->_owner->url));
			} else {
				$name = $this->link($name, array('library' => 'minerva', 'controller' => 'users', 'action' => 'read', 'url' => $document->_owner->url));
			}
		}
		
		return $name;
	}
	
	/**
	 * Basic date function.
	 * TODO: Make a better one
	 *
	 * @param $value The date object from MongoDB (or a unix time, ie. MongoDate->sec)
	 * @param $format The format to return the date in
	 * @return String The parsed date
	*/
    public function date($value=null, $format='Y-M-d h:i:s') {
		$date = '';
		if(is_object($value)) {
			$date = date($format, $value->sec);
		} elseif(is_numeric($value)) {
			$date = date($format, $value);
		} elseif(!empty($value)) {
			$date = $value;
		}
        return $date;
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
        $options += array('include_minerva' => true, 'admin' => $this->admin_prefix, 'library' => 'minerva', 'link_options' => array());
        $output = '';
		
		$model_class_name = Inflector::classify($model_name);
		$models = Libraries::locate('minerva_models', $model_class_name);
		
		//$controller = $options['library'] . '.' . strtolower(Inflector::pluralize($model_name));
		// no longer using library.controller syntax... see how that works
		$controller = strtolower(Inflector::pluralize($model_name));
		
        $output .= '<ul>';
		
		// if include_minerva is true, then show the basic link... ie.  /minerva/pages/create
		if($options['include_minerva']) {
			$output .= '<li>' . $this->link($model_class_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action), $options['link_options']) . '</li>';
		}
		
		// this used the util class... might want to consider using it to eliminate the use of Libraries class here and other classes if possible
		// and just duplicate (sorta) logic
		/*
		foreach($types as $type) {
			var_dump($type);
			//$output .= '<li>' . $this->link($model_class_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action), $options['link_options']) . '</li>';
		}*/
		
		if(is_array($models)) {
			foreach($models as $model) {
				$class_pieces = explode('\\', $model);
				$type = $class_pieces[0];
				if(class_exists($model)) {
					$display_name = $model::display_name();
					// if the model doesn't have a display_name property, it'll pick it up from either the base minerva model (Page, Block, or User) or the MinervaModel class...in this case, use the document type
					$display_name = ($display_name == 'Model' || empty($display_name) || in_array($display_name, $this->core_minerva_models)) ? Inflector::humanize($type . ' ' . $model_name):$display_name;
					$output .= '<li>' . $this->link($display_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action, 'args' => $type), $options['link_options']) . '</li>';
				}
			}
		} else {
			$class_pieces = explode('\\', $models);
			$type = $class_pieces[0]; // the library name serves as the document_type
			if(class_exists($models)) {
				$display_name = $models::display_name();
				// if the model doesn't have a display_name property, it'll pick it up from either the base minerva model (Page, Block, or User) or the MinervaModel class...in this case, use the document type
				$display_name = ($display_name == 'Model' || empty($display_name) || in_array($display_name, $this->core_minerva_models)) ? Inflector::humanize($type . ' ' . $model_name):$display_name;
				$output .= '<li>' . $this->link($display_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action, 'args' => $type), $options['link_options']) . '</li>';
			}
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