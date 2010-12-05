<?php
// TODO: rethink "page types" maybe instead of having all these libraries, have one "page_type" library
// then each model in that library will extend the Page model...
// and the view templates will be under the library /views/page_type_name for each page type...
// models still get instantiated on bootstrap...maybe route takes a key now instead of "library"
// make it "page_type" since library could cause problems anyway. also change on the collection
// the field "library" to "page_type" ...
// Downside: this does mean page types are slightly less portable. you have to move model class and view folders
// this also means if for some reaosn page types rely on other classes, where do they go?
// could go under the "page_type" library....could go in their own library...but the page type may
// depend on those classes, so again less portability. also not great for organization.
// really hurts the ability to package as a phar file

namespace minerva\libraries\blog\models;

use minerva\util\Access;

class Page extends \minerva\models\Page {
	
	// Completely override the core settings for which PagesController methods are protected and/or how they are protected
	/*static $access = array(
		// 'login_redirect' could be 'http://www.google.com' for all the system cares, it'll go there
		//'read' => array('rule' => 'allowTom', 'login_redirect' => '/users/login'),
		'create' => array(),
		'*' => array('rule' => 'allowTom'),
		'view' => array('rule' => 'allowAll')
	);*/
	
	// Add new fields here
	protected $_schema = array(
		'title' => array('label' => 'Blog Title'), // this won't overwrite the main app's page models' $fields title key
		'author' => array('type' => 'string'),
		'body' => array('type' => 'string', 'form' => array('label' => 'Page Copy', 'type' => 'textarea'))
	);
	
	// Add validation rules for new fields here
	public $validates = array(
		'body' => array(
                    array('notEmpty', 'message' => 'Body cannot be empty'),
                )
	);
	
	public static function __init() {
		Access::add('allowTom', function($user) {
		    //var_dump($user);
		    if($user['username'] == 'Tom')  {
			return true;   
		    }
		    return false;
		});
		
		// Put any desired filters here
		
		parent::__init();
	}
	
}

// Apply a filter to Minerva's Access class.
// The Access class will determine if the, already authenticated at this point, user has access to the requested location.
/*Access::applyFilter('check', function($self, $params, $chain) {
        var_dump('filter on check, applied from /libraries/blog/models/Page.php');
	exit();
        return $chain->next($self, $params, $chain);
});*/
?>