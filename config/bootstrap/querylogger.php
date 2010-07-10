<?php
use \lithium\data\Connections;
use \lithium\analysis\Logger;

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
        Logger::write('info',
            json_encode($params['query']->export($MongoDb) + array('result' => $result->data()))        );  
    }       return $result;
});
?>
