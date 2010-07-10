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
		// First, render filter to change where the pages get their templates from
		Media::applyFilter('render', function($self, $params, $chain){
			//var_dump($params['options']);
			
			// Read read method only, change the template paths. This may not always be the case. Though. Per plugin basis.
			// It essentially allows one to change the admin create/update pages/forms/templates if so desired.
			// But those forms are automatically set by defining static $fields of course. So it's optional.
			if($params['options']['request']->action == 'read') {
				$params['options']['paths']['template'] = LITHIUM_APP_PATH . '/libraries/blog/views/pages/{:template}.{:type}.php';
				$params['options']['paths']['layout'] = LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php';
			}
			
			// var_dump($params['options']['request']->action); // ...and this gives us the action
			/// Set more data to the view... don't need to use "setViewData" !
			$params['data'] += array('var' => 'some more shit', 'library_data' => 'foo2');
			
			return $chain->next($self, $params, $chain);
		});
		
		// Second, ensure the index listing for pages show only blogs
		// TODO: any better/cleaner way of doing this??
		if($_SERVER['QUERY_STRING'] == 'url=pages/index/blog') {
			\minerva\models\Page::applyFilter('find', function($self, $params, $chain) {
				$params['options']['conditions'] = array('library' => 'blog');			
				return $chain->next($self, $params, $chain);
			});
		}
		
		parent::__init();
	}
	
}
?>