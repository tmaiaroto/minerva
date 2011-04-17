<?php
/**
* Block Helper 
*
* Blocks are an inefficient way to solve design problems. Their usage can be quite flexible though and inneficiency may not be 
* a real big deal depending on where and when and for what. It is the equivelant of making a SQL JOIN call, but for PHP instead
* in order to "join" html/css to make up the page. Unfortunately, it's sometimes (often) necessary.
*
* RENDER METHOD
* --------------
* There are two main methods for blocks to be rendered. First is the "php" method which will call external/local URLs using cURL
* or render view templates from the views folder (by default located in: minerva/views/blocks/static). 
*
* Rendering view templates is perhaps your most basic use for blocks. It renders a "static" template from the views/blocks folder.
* Of course those templates can also call Block->render() again or Block->requestAction(). This can help with organization. 
* 
* As you can start to see, with all these classes (both View and your controllers) being instantiated over and over,
* pulling in other data is not the most effecient thing for a site/CMS and breaks MVC. It's a necessary evil though.
*
* Not to worry! There's caching for the "php" method! So once your block template (which may make several other calls that make
* several other calls) has been rendered, it can be cached to disk (or memory, depending) using Lithium's Cache class.
* That way, you won't be instantiating all those classes and running all those methods on each request.
* What's even better is that you can also, more than likely, cache the parent/calling view template! Caching for everyone!
*
* In the other corner...
* We have our "ajax" method. This method will always make another request to the web server.
* AJAX can not call external URLs. So why on earth would you use it? Well, maybe you don't care about how many requests
* are coming to your server. Maybe you really need to ensure that this block is never cached. Maybe you are calling another 
* page on your site that already is cached and you're just doing this so that your page can load first and have all your 
* blocks come in last for user experience. Again, note that is another request to the server, but you may weigh the options
* and decide that you're ok with that and it's more important to have the main part of the page load as fast as possible.
* It is like using cURL, but it is done on the client side so it doesn't hold up the main page from loading.
*
* 
* REQUEST ACTION METHOD
* ----------------------
* You also have another method in this helper. Called "requestAction"
* The serves as a shortcut to instantiating controller classes and calling specific methods/actions from within those controllers 
* to return data to the page. That means those methods should be built with the beforehand knowledge that they are to return 
* data instead of render views. This data can then be used directly within the view template.
* Using this in conjunction with the render() method should give you the ability to load whatever content you need into blocks.
* 
* So, you have flexibility while at the same time maintaining a small footprint and allowing you to write modular code.
* Remember that blocks can really hurt your site's performance if not cached and there's a bunch of them.
*
*
* @author Tom Maiaroto
* @website http://www.shift8creative.com
* @modified 2011-01-13 21:37:50 
* @created 2010-06-10 16:17:41 
*
*/
namespace minerva\extensions\helper;
use \lithium\template\View as View;
use \lithium\util\Inflector as Inflector;

class Block extends \lithium\template\Helper {
	
	/**
	 * Shortcut helper method for rendering an admin static block from a view template.
	 * Admin templates are always pulled from minerva/views/blocks/static/...
	 *
	 * @param $template string[required] The name of the template file
	 * @param $library string[optional] The name of the library (will always look in the main app before showing a missing template warning)
	 * @return Mixed the html/css from the rendered page/view template
	*/
	public function render_admin_block($template=null, $library='minerva') {
		if(empty($template)) {
			return '';
		}
		$options = array('library' => $library, 'template' => $template, 'admin' => 'admin');
		return $this->render($options);
	}
	
	/**
	 * Shortcut helper method for rendering a static block from a view template.
	 * Static templates are pulled from minerva/libraries/static/views/blocks/...
	 * If the path needs to be changed, use render() instead.
	 *
	 * @param $template string[required] The name of the template file
	 * @param $library string[optional] The name of the library (will always look in the main app before showing a missing template warning)
	 * @return Mixed the html/css from the rendered page/view template
	*/
	public function render_block($template=null, $library='minerva') {
		if(empty($template)) {
			return '';
		}
		return $this->render(array('template' => $template, 'library' => $library));
	}
	
	/**
	 * render() allows you to render a view template or external URL's content inline with the template it was called from.
	 *
	 * @param $options array[required]
	 * @return Mixed the html/css from the rendered page/view template or JavaScript code with an AJAX call to load local content or false if something went wrong
	*/
	public function render($options=array(), $data=array()) {
		$defaults = array(
			// unrelated to View class
			'url' => null,
			'curl_options' => array(),
			'curl_body_only' => false,
			'method' => 'php',
			
			// these are all options passed to View
			'admin' => false,
			'controller' => 'blocks',
			'library' => 'minerva', 
			'type' => 'html',
			'layout' => 'blank',
			'template' => '',
			//'paths' => array() // TODO: this
		);
		$options += $defaults;
		
		if($options['method'] == 'curl' && !empty($options['url'])) {
			$default_curl_options = array(CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1);
			$options['curl_options'] += $defaults['curl_options'];
			
			$ch = curl_init($options['url']);
			curl_setopt_array($ch, $options['curl_options']);
			$data = curl_exec($ch);
			
			// special option that will parse the results returned from cURL and if it's an HTML document, only include the body.
			// TODO: stuff like this...
			if($curl_body_only) {
			}
			
			curl_close($ch);
			
			return $data;	
		}
		
		/** 
		 *  Method by default is set to php, meaning we are going to get the content for the block now and render it with the page.
		 *  This allows us to cache the block content because the server is aware of it. <-- TODO: 
		 */
		if($options['method'] == 'php') {
			// Get the current paths (they were setup in minerva/config/bootstrap/templates.php)
			$view = $this->_context->view();
			$paths = $view->_config['paths'];
			
			// convert the $paths['template'] and $paths['layout'] if they are strings, so we can add a few different paths
			if(is_string($paths['template'])) {
				$paths['template'] = array($paths['template']);
			}
			if(is_string($paths['layout'])) {
				$paths['layout'] = array($paths['layout']);
			}
			
			// add to the template path
			array_unshift($paths['template'], '{:library}/views/{:controller}/static/{:template}.{:type}.php');
			
			// add to the layout path, first unshift minerva's layout (so by default "blank" doesn't need to be copied) then look in the library's layout path
			array_unshift($paths['layout'], LITHIUM_APP_PATH . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'minerva' . '/views/layouts/{:layout}.{:type}.php');
			array_unshift($paths['layout'], '{:library}/views/layouts/{:layout}.{:type}.php');
			
			// Add to the paths, admin template locations if admin was specified. It will still default back to non-admin and the main app
			if(!empty($options['admin'])) {
				array_unshift($paths['layout'], '{:library}/views/_admin/layouts/{:layout}.{:type}.php');
				array_unshift($paths['template'], '{:library}/views/_admin/{:controller}/static/{:template}.{:type}.php');
			}
			
			// Instantiate a View class instance with the options we'll need for the renderer, paths, etc.
			$new_view = new View(array('paths' => $paths));
			return $new_view->render('all', $data, $options);
		}
		
		/** 
		 *  However, we can also set the method to ajax, meaning JavaScript is written to the page instaed and the user gets  
		 *  the data. This won't allow us to cache the block content, but it also allows the rest of the page to load first 
		 *  instead of waiting on the block content to load before continuing on to other parts of the page. 
		 *  
		 *  NOTE: You can't call remote hosts due to security restrictions. So this may not be the method for everyone.
		 *  But if multiple http get requests to the same web server isn't particularly a problem, this could make for a very
		 *  fast loading, nice user experience, while at the same time being a very easy way to load content into your block area.
		 *
		 *  Also good for rendering "elements" and various "API" type components within the same domain.
		 */ 
		if($options['method'] == 'ajax') {	
			// TODO: add a spinner graphic, use a $.ajax() and make the success remove the spinner
			// jQuery should be included in the layout already in noConflict mode
			$id = 'div_' . uniqid();
			$ajax_code = '<div id="'.$id.'"></div><script type="text/javascript">$(document).ready(function() {';					
			$ajax_code .= '$.get(\''.$options['url'].'\', function(data) { $(\'#'.$id.'\').html(data); });'; 
			$ajax_code .= '});</script>';
			
			return $ajax_code;
		}
		
	}
	
	/**
	 *  requestAction() is a shortcut method to pulling back return data from any controller's method.
	 *  Normally, you'd have to manually instantiate the class, call the method, and pass arguments...
	 *  Which really isn't a big deal, but this is a convience to that. It also let's you pass a conveient library option.
	 *
	 *  @param $options array[required] This is your basic controller/action url in array format, you can also pass 'library'
	 *  @return Mixed data from the controller's method to use in your view template
	*/
	public function requestAction($options=array()) {
		$defaults = array('library' => null, 'args' => null);
		$options += $defaults;
		
		if((!isset($options['controller'])) || (!isset($options['action']))) {
			return false;
		}
		
		$controller_pieces = explode('.', $options['controller']);
        $controller = (count($controller_pieces) > 1) ? $controller_pieces[1]:$controller_pieces[0];
		$controller_name = Inflector::camelize($controller);
		if(empty($options['library'])) {
			$class = '\minerva\controllers\\'.$controller_name.'Controller';
		} else {
			$class = '\minerva\libraries\\'.$options['library'].'\controllers\\'.$controller_name.'Controller'; 			
		}
		$controller = new $class();		
		
		return $controller->{$options['action']}($options['args']);		
	}
	
	/**
	 *  request() is simply a shortcut method to the shortcut method of pulling back data from the block controller
	 *  In this case...We're talking about getting a dynamic block from the database.
	 *  While the requestAction() can call any controller/action, request() just calls the blocks controller's read method.
	 *  This leaves just one simple argument to be passed, the "URL" of the block.
	 *
	 *  @param $url String The URL for the dynamic block held in the database
	 *  @return Array The data from that record.
	*/	
	public function request($url=null) {
		if(!$url) {
			return false;
		}
		$data = array();
		$block = $this->requestAction(array('controller' => 'blocks', 'action' => 'read', 'args' => $url));
		if(is_object($block)) {
			$data = $block->data();
		}
		return $data;
	}
	
}
?>