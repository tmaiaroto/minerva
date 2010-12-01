<?php
namespace minerva\libraries\blog\models;

use lithium\net\http\Media;

class Page extends \minerva\models\Page {
	
	protected $_schema = array(
		'title' => array('label' => 'Blog Title'), // this won't overwrite the main app's page models' $fields title key
		'author' => array('type' => 'string'),
		'body' => array('type' => 'string', 'form' => array('label' => 'Page Copy', 'type' => 'textarea'))
	);
	
	public $validates = array(
		'body' => array(
                    array('notEmpty', 'message' => 'Body cannot be empty'),
                )
	);
	
	public static function __init() {		
		// FILTERS
		
		// All filters that would have been required are now optional.
		// Someone who wants to develop a library/plugin for the CMS should be aware
		// of how filters work and what's going on, but they technically don't need to write any...
		// but might end up doing so for special cases.
		
		// First, render filter to change where the pages get their templates from
		// NOTE: NOW DONE IN BOOTSTRAP PROCESS
		/*Media::applyFilter('render', function($self, $params, $chain){
                    $params['options']['paths']['layout'] = LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php';
                	// Read read method only, change the template paths. This may not always be the case. Though. Per plugin basis.
			// It essentially allows one to change the admin create/update pages/forms/templates if so desired.
			// But those forms are automatically set by defining static $fields of course. So it's optional.
			if($params['options']['request']->action == 'read') {
				$params['options']['paths']['template'] = LITHIUM_APP_PATH . '/libraries/blog/views/pages/{:template}.{:type}.php';
				$params['options']['paths']['layout'] = LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php';
			}
			
			// var_dump($params['options']['request']->action); // ...and this gives us the action
			/// Set more data to the view... don't need to use "setViewData" !
			$params['data'] += array('var' => 'some more data', 'library_data' => 'foo2');
			
			return $chain->next($self, $params, $chain);
		});*/
		
		// Second, ensure the index listing for pages show only blogs
		// TODO: any better/cleaner way of doing this??
		// Yea, it's now done by the core PagesController now, because we pass the library via route 
		// and if it's present, we can add it to the conditions for the find() in all methods....
		// Can't really see a time when we wouldn't want to filter like that.
		// ...and if we did, we can apply a filter here to change conditions back.
		/*if($_SERVER['QUERY_STRING'] == 'url=pages/index/blog') {
			\minerva\models\Page::applyFilter('find', function($self, $params, $chain) {
				$params['options']['conditions'] = array('library' => 'blog');
				$params['options']['conditions'] = array(); // <-- so just setting the conditions back to empty like this would include ALL pages
				return $chain->next($self, $params, $chain);
			});
		}*/
		
		parent::__init();
	}
	
}
?>