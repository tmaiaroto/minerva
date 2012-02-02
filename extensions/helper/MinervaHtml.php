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
		// setting core_minerva_models is just helpful for convenience with display reasons
		// in the methods below
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
	public function flash($options = array()) {
		$defaults = array(
			'key' => 'minerva_admin',
			// options for the layout template, some of these options are specifically for the
			// pnotify jquery plugin
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
						'layout' => '{:library}/views/layouts/{:layout}.{:type}.php'
					)
				));

		return $view->render('all', array(
			'options' => $options['options'],
			'message' => $message
			), array(
					'template' => 'flash_message',
					'type' => 'html',
					'layout' => 'blank',
					'library' => 'minerva'
				));
	}

	/**
	 * A little helpful method that returns the current URL for the page.
	 * @todo: refactor it and use lithiums env instead of _SERVER
	 *
	 * @param boolean $includeDomain Whether or not to include the domain or just the request
	 *                uri (true by default)
	 * @param boolean $includeQuerystring Whether or not to also include the querystring
	 *                (true by default)
	 * @param boolean $includePaging
	 *
	 * @return String
	 */
	public function here($includeDomain = true, $includeQuerystring = true, $includePaging = true) {
		$pageURL = 'http';
		if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'] == 'on')) {
			$pageURL .= 's';
		}
		$pageURL .= '://';
		if ($_SERVER['SERVER_PORT'] != '80') {
			$pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
			$pageURL .= $_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		if ($includeDomain === false) {
			$pageURL = $_SERVER['REQUEST_URI'];
		}

		// always remove the querystring, we'll tack it back on at the end if we want to keep it
		if ($includeQuerystring === false) {
			parse_str($_SERVER['QUERY_STRING'], $vars);
			unset($vars['url']);
			$querystring = http_build_query($vars);
			if (!empty($querystring)) {
				$pos = strlen($querystring) + 1;
				$pageURL = substr($pageURL, 0, -$pos);
			}
		}

		// note, this also ditches the querystring
		if ($includePaging === false) {
			$base_url = explode('/', $pageURL);
			$base_url = array_filter($base_url, function($val) {
						return (!stristr($val, 'page:') && !stristr($val, 'limit:'));
					});
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
	 * @param object $document
	 * @param array $options An array of options, such as whether or not to return a link to
	 *             the user's record, etc.
	 * @return String HTML
	 */
	public function owner_name($document = false, array $options = array()) {
		$defaults = array(
			'link_to_user_record' => true
		);
		$options += $defaults;

		$request = $this->_context->request();
		$name = '';

		if ($document) {
			$name = (isset($document->_owner->_name) && !empty($document->_owner->_name)) ?
					$document->_owner->_name : $name;
		}

		if ($options['link_to_user_record']) {
			$linkRoute = array(
				'library' => 'minerva',
				'controller' => 'users',
				'action' => 'read',
				'url' => $document->_owner->url
			);
			if (isset($request->params['admin'])) {
				$linkRoute['admin'] = $this->admin_prefix();
			}
			$name = $this->link($name, $linkRoute);
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
	public function date($value = null, $format = 'Y-M-d h:i:s') {
		$date = '';
		if (is_object($value)) {
			$date = date($format, $value->sec);
		} elseif (is_numeric($value)) {
			$date = date($format, $value);
		} elseif (!empty($value)) {
			$date = $value;
		}
		return $date;
	}

	/**
	 * Returns a list of links to model types' actions (Page types, User types, etc.)
	 * By default, with no options specified, this returns a list of links to create any type
	 * of page (except core page).
	 *
	 * @see minerva\libraries\util\Util::listTypes()
	 *
	 * @param $model_name String The model name (can be lowercase, the Inflector corrects it)
	 * @param $action String The controller action
	 * @param array $options Various options that get passed to Util::listTypes() and the
	 *              key "link_options" can contain an array of options for the $this->html->link()
	 * @return String HTML list of links
	 */
	public function link_types($model_name = 'Page', $action = 'create', $options = array()) {
		$options += array(
			'include_minerva' => true,
			'admin' => $this->admin_prefix,
			'library' => 'minerva',
			'link_options' => array()
		);

		$output = '';

		$model_class_name = Inflector::classify($model_name);
		// $models = Libraries::locate('minerva_models', $model_class_name);
		// TODO: The above was not working...See why.
		// For now, just get all minerva models and forget the $type
		$models = Libraries::locate('minerva_models');

		//$controller = $options['library'] . '.' . strtolower(Inflector::pluralize($model_name));
		// no longer using library.controller syntax... see how that works
		$controller = strtolower(Inflector::pluralize($model_name));

		$output .= '<ul>';

		// if include_minerva is true, then show the basic link... ie.  /minerva/pages/create
		if ($options['include_minerva']) {
			$linkRoute = array(
				'admin' => $options['admin'],
				'library' => $options['library'],
				'controller' => $controller,
				'action' => $action
			);
			$output .= '<li>';
			$output .= $this->link($model_class_name, $linkRoute, $options['link_options']);
			$output .= '</li>';
		}

		// this used the util class... might want to consider using it to eliminate the use
		// of Libraries class here and other classes if possible
		// and just duplicate (sorta) logic
		/*
		  foreach($types as $type) {
		  var_dump($type);
		  //$output .= '<li>' . $this->link($model_class_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action), $options['link_options']) . '</li>';
		  } */
		//@todo refactor this
		if (is_array($models)) {
			foreach ($models as $model) {
				$class_pieces = explode('\\', $model);
				$type = $class_pieces[0];
				$minerva_model_name = end($class_pieces);
				if (class_exists($model) && $minerva_model_name == $model_class_name) {
					$display_name = $model::displayName();
					// if the model doesn't have a display_name property, it'll pick it up from
					// either the base minerva model (Page, Block, or User) or the
					// MinervaModel class...in this case, use the document type
					$display_name = (
							$display_name == 'Model' ||
							empty($display_name) ||
							in_array($display_name, $this->core_minerva_models)
						) ? Inflector::humanize($type . ' ' . $model_name) : $display_name;
					//$output .= '<li>' . $this->link($display_name, array('admin' => $options['admin'], 'library' => $options['library'], 'controller' => $controller, 'action' => $action, 'args' => $type), $options['link_options']) . '</li>';
					// Now have a plugin key to use...
					$linkRoute =  array(
						'admin' => $options['admin'],
						'plugin' => $type,
						'library' => $options['library'],
						'controller' => $controller,
						'action' => $action,
						'args' => $type
					);
					$output .= '<li>';
					$output .= $this->link($display_name,$linkRoute, $options['link_options']);
					$output .= '</li>';
				}
			}
		} else {
			$class_pieces = explode('\\', $models);
			// no more type. type is assumed based on plugin name
			// so instead of passing $class_pieces[0] as $type to args in a link,
			// we're matching a route that's going to look like:
			// /minerva/plugin/plugin_name/admin/controller/action
			$plugin = $class_pieces[0];
			if (class_exists($models)) {
				$display_name = $models::displayName();
				// if the model doesn't have a display_name property, it'll pick it up from
				// either the base minerva model (Page, Block, or User) or the
				// MinervaModel class...in this case, use the document type
				$display_name = (
						$display_name == 'Model' ||
						empty($display_name) ||
						in_array($display_name, $this->core_minerva_models)
					) ? Inflector::humanize($type . ' ' . $model_name) : $display_name;

				$linkRoute = array(
					'admin' => $options['admin'],
					'plugin' => $plugin,
					'library' => $options['library'],
					'controller' => $controller,
					'action' => $action
				);
				$output .= '<li>';
				$output .= $this->link($display_name, $linkRoute, $options['link_options']);
				$output .= '</li>';
			}
		}

		$output .= '</ul>';

		return $output;
	}

	/**
	 * A generic form field input that passes a querystring to the URL for the current page.
	 *
	 * @todo: refactor and do not use globals
	 *
	 * @param array $options Various options for the form and HTML
	 * @return String HTML and JS for the form
	 */
	public function query_form($options = array()) {
		$options += array(
			'key' => 'q',
			'class' => 'search',
			'button_copy' => 'Submit',
			'div' => 'search_form',
			'label' => false
		);
		$output = '';

		$form_id = sha1('asd#@jsklvSx893S@gMp8oi' . time());

		$output .= (!empty($options['div'])) ? '<div class="' . $options['div'] . '">' : '';

		$output .= (!empty($options['label'])) ? '<label>' . $options['label'] . '</label>' : '';
		$output .= '<form id="' . $form_id . '" onSubmit="';
		$output .= 'window.location = window.location.href + \'?\' + $(\'#' . $form_id . '\')';
		$output .= '.serialize();';
		$output .= '">';
		$value = (isset($_GET[$options['key']])) ? $_GET[$options['key']] : '';
		$output .= '<input name="' . $options['key'];
		$output .= '" value="' . $value . '" class="' . $options['class'] . '" />';
		$output .= '<input type="submit" value="' . $options['button_copy'] . '" />';
		$output .= '</form>';

		$output .= (!empty($options['div'])) ? '</div>' : '';


		return $output;
	}
}

?>