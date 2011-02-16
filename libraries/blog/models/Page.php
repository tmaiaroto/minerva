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

class Page extends \minerva\models\Page {
	
	// This $access property will take priority oer the core PagesController's. Rather than needing to redefine each method's rules. So "view" for example can be left out and the default access rules will apply.
	// Blogs different in that we want to allow access to index. However we also apply a filter below that will limit the documents displayed in the index action to just those that are published.
	static $access = array(
        'action' => array(
            // Don't need to redfine all these...We only need index changed
            /*'create' => array(
            array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'update' => array(
            array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),
            'delete' => array(
            array('rule' => 'allowManagers', 'redirect' => '/users/login')
            ),*/
            'index' => array(
            array('rule' => 'allowAll')
            ),
            'foo' => array(
            'bar'
            )
        ),
        'document' => array(
            
        )
	);
	
	
	// Add new fields here
	protected $_schema = array(
		// this won't overwrite the main app's page models' $fields title key
		'title' => array(
			'form' => array(
				'label' => 'Blog Title'
			)
		),
		// these are new
		'author' => array(
			'type' => 'string',
			'form' => array(
				'help_text' => 'Optionally override the author name which is set from your user name.',
				'position' => 'options'
			)
		),
		'body' => array(
			'type' => 'string',
			'form' => array(
				'label' => 'Body Copy',
				'type' => 'textarea'
			)
		)
	);
	
	public $search_schema = array(
		'body' => array(
			'weight' => 1
		)
	);
	
	// Add validation rules for new fields here
	public $validates = array(
		'body' => array(
                    array('notEmpty', 'message' => 'Body cannot be empty'),
                ),
		'title' => array(
		    array('notEmpty', 'message' => 'It can\'t be empty foo!')
		)
	);
	
	// A little context
	public $display_name = 'Blog Entry';
	
	public static function __init() {
		
		\minerva\models\Page::applyFilter('find', function($self, $params, $chain) {
		    
            /**
             * find() doens't do anything with a "request_params" key and you wouldn't typically see it...
             * However, all core Minerva code will pass the request params to all find() calls just for
             * this kind of flexibility. In this case, if it's not an admin action then we're adding to the
             * conditions the requirement of the document being published.
             *
             * What else could we do? We could say certain users could see it on non-admin actions too...
             * Or...whatever else that could be dreamed of.
            */
            if((isset($params['options']['request_params']['admin'])) && ($params['options']['request_params']['admin'] !== true)) {
                $params['options']['conditions']['published'] = true;
            }
		    
		    return $chain->next($self, $params, $chain);
		    
		    // NOTE: could be applying access rules here and checking against them
		    //$record = $chain->next($self, $params, $chain);
		    // Here would be an "afterFind" don't forget to return $record; instead of to the chain
		    //var_dump($record);
		    
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