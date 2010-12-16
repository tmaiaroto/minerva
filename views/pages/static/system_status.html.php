<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\data\Connections;

$checkName = null;
$checkStatus = $solutions = array();

$notify = function($status, $message, $solution = null) use (&$checkName, &$checkStatus, &$solutions) {
	$checkStatus[$checkName] = $status === true;

	if (!is_string($status)) {
		$status = $status ? 'success' : 'fail';
	}

	$message = is_array($message) ? join("\n<br />", $message) : $message;

	if (!empty($solution)) {
		$default = array(
			'id' => 'help-' . $checkName,
			'title' => $checkName,
			'content' => null
		);
		if (is_array($solution['content'])) {
			$solution['content'] = join("\n<br />", $solution['content']);
		}
		$solutions[$checkName] = $solution += $default;

	}
	return "<div class=\"test-result test-result-{$status}\">{$message}</div>";
};

$sanityChecks = array(
	'resourcesWritable' => function() use ($notify) {
		if (is_writable($path = realpath(LITHIUM_APP_PATH . '/resources'))) {
			return $notify(true, 'Resources directory is writable.');
		}
		return $notify(false, array(
			"Your resource path (<code>$path</code>) is not writeable. " .
			"To fix this on *nix and Mac OSX, run the following from the command line:",
			"<code>chmod -R 0777 {$path}</code>"
		));
	},
	'database' => function() use ($notify) {
		$config = Connections::config();
		$boot = realpath(LITHIUM_APP_PATH . '/config/bootstrap.php');
		$connections = realpath(LITHIUM_APP_PATH . '/config/connections.php');

		if (empty($config)) {
			return $notify('notice', array('No database connections defined.'), array(
				'title' => 'Database Connections',
				'content' => array(
					'To create a database connection, edit the file <code>' . $boot . '</code>, ',
					'and uncomment the following line:',
					'<pre><code>require __DIR__ . \'/connections.php\';</code></pre>',
					'Then, edit the file <code>' . $connections . '</code>.'
				)
			));
		}
		return $notify(true, 'Database connection(s) configured.');
	},
	// 'databaseEnabled' => function() use ($notify, &$checkStatus) {
	// 	if (!$checkStatus['database']) {
	// 		return;
	// 	}
	// 	$results = array();
	// 	$config = Connections::config();
	// 	foreach ($config as $name => $options) {
	// 		$enabled = Connections::enabled($name);
	// 		if (!$enabled) {
	// 			$results[] = $notify('exception', "Database for <code>{$options}</code> is not enabled.");
	// 		}
	// 	}
	// 	if (empty($results)) {
	// 		$results[] = $notify(true, "Database(s) enabled.");
	// 	}
	// 	return implode("\n", $results);
	// },
	// 'databaseConnected' => function() use ($notify, &$checkStatus) {
	// 	if (!$checkStatus['database']) {
	// 		return;
	// 	}
	// 	$results = array();
	// 	$config = Connections::config();
	// 	foreach ($config as $name => $options) {
	// 		$enabled = Connections::enabled($name);
	// 		if ($enabled) {
	// 			$connection = Connections::get($name)->connect();
	// 			if ($connection) {
	// 				$results[] = $notify(
	// 					true, "Connection to <code>{$name}</code> database verified."
	// 				);
	// 			} else {
	// 				$results[] = $notify(
	// 					false, "Could not connect to <code>{$name}</code> database."
	// 				);
	// 			}
	// 		}
	// 	}
	// 	return implode("\n", $results);
	// },
	'magicQuotes' => function() use ($notify) {
		if (get_magic_quotes_gpc() === 0) {
			return;
		}
		return $notify(false, array(
			"Magic quotes are enabled in your PHP configuration. Please set <code>" .
			"magic_quotes_gpc = Off</code> in your <code>php.ini</code> settings."
		));
	},
	'registerGlobals' => function() use ($notify) {
		if (!ini_get('register_globals')) {
			return;
		}
		return $notify(false, array(
			'Register globals is enabled in your PHP configuration. Please set <code>' .
			'register_globals = Off</code> in your <code>php.ini</code> settings.'
		));
	},
);

?>

<div class="grid_16">
	<h2 id="page-heading">System Status</h2>
</div>
<div class="clear"></div>

<div class="grid_16">
<?php

foreach ($sanityChecks as $checkName => $check) {
	echo $check();
}

?>
</div>

<div class="clear" style="height: 10px;"></div>