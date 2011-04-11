<?php
/**
 * If you wish to avoid checks for library dependencies, uncomment the line in Minerva's bootstrap 
 * that includes this file. It will save a filter and some function calls for the system.
 * Besides, once you have all the dependencies for the CMS, this check is completely pointless.
 * It's just to make the CMS a little less rough around the edges, but may become something
 * more robust in the future that may even automatically handle obtaining these dependencies for you.
 *
*/

use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\template\View;

Dispatcher::applyFilter('run', function($self, $params, $chain) {
    $data = $chain->next($self, $params, $chain);
    
    // Only apply the following when using the minerva library
	if(isset($params['request']->params['library']) && $params['request']->params['library'] == 'minerva') {
		$config = Libraries::get('minerva');
		
        // Check for these libraries for the CMS - these can be specified as key => value or just a value if not providing a link.
		$library_deps = array(
			'li3_flash_message' => 'http://dev.lithify.me/li3_flash_message',
            'li3_access' => 'https://github.com/tmaiaroto/li3_access'
		);
		
		if(isset($config['facebook'])) {
			$library_deps += array('li3_facebook' => 'https://github.com/tmaiaroto/li3_facebook');
		}
		
		$missing_deps = array();
		foreach($library_deps as $k => $v) {
			if(is_numeric($k)) {
                if(Libraries::get($v) == null) {
                    $missing_deps[] = $v;
                }
            } else {
                if(Libraries::get($k) == null) {
                    $missing_deps += array($k => $v);
                }
            }
		}
		
		if(!empty($missing_deps)) {
            
            // Borrow the ErrorHanlder's layout and use a new "dependencies" template in the _errors folder.
            $view = new View(array(
                'paths' => array(
                    'template' => '{:library}/views/_errors/{:template}.{:type}.php',
                    'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
                )
            ));
            
            return $view->render('all', array('missing_deps' => $missing_deps), array(
                'template' => 'dependencies',
                'type' => 'html',
                'layout' => 'error',
                'library' => 'minerva' // should already be minerva
            ));
            
		}
    }
    
    return $data;
});
?>