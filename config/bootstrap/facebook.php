<?php
use lithium\core\Libraries;

if(isset($minerva_config['facebook'])) {
    try {
        Libraries::add('li3_facebook', array(
            'appId' => $minerva_config['facebook']['appId'],
            'secret' => $minerva_config['facebook']['secret']
        ));
    } catch (\Exception $e) {
        // Not going to handle it... Dependency check will bark.
    } 
}
?>