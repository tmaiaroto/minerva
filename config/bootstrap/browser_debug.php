<?php
/**
 * Filter to log queries and trace back information to your browser console.
 * You will need the Firefox plugin "Firebug" or Google Chrome.
 *
 */

use \lithium\data\Connections;
use \lithium\analysis\Logger;
use \lithium\template\View;

Logger::config(array(
    'default' => array('adapter' => 'File')
));

/**
 * Log all queries passed to the MongoDB adapter.
 */
$MongoDb = Connections::get('default');
$MongoDb->applyFilter('read', function($self, $params, $chain) use (&$MongoDb) {
    $result = $chain->next($self, $params, $chain);

    if (method_exists($result, 'data')) {
        /*Logger::write('info',
            json_encode($params['query']->export($MongoDb) + array('result' => $result->data()))        );  
            */
            //var_dump($params['query']->export($MongoDb) + array('result' => $result->data()));
       
  		$view = new View(array('loader' => 'Simple', 'renderer' => 'Simple'));
  		echo $view->render(array('element' => '<script type="text/javascript">console.dir({:data});</script>'), array(
      		'data' => json_encode(array_filter($params['query']->export($MongoDb)) + array_filter(array('result' => $result->data())))
  		));
       
       //echo \lithium\analysis\Debugger::trace();  // would run a trace
       
       // TODO: make this render later on. so it doesn't put the javascript before <html>         
            
    }       return $result;
});
?>
