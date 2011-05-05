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

<?php

foreach ($sanityChecks as $checkName => $check) {
	echo $check();
}

?>
<h3>Getting Started</h3>
<p>
	Likely, you're wanting to see how the CMS works. That's good because there really aren't any front-end templates right now (there are, but they are very basic). You first need to create an account and login. The very first account you create will have an administrator role. <a href="/minerva/users/register">Click here to register</a>.
</p>
<p>
	After you've registered, you can then login. You probably want to login to see the admin area so <a href="/minerva/admin/pages">click here to go to the admin area</a>. You will be redirected to login first and, after you do, you will then be redirected to the URL you were trying to access (the page listing within the administrative back-end). Alternatively, you could just login and then manually go to "/minerva/admin" or some other admin URL.
</p>
<h4>About This Page</h4>
<p>
	This is Minerva's default home page. To change this template, edit the file
	<code><?php echo realpath(LITHIUM_APP_PATH . '/libraries/minerva/views/pages/static/home.html.php'); ?></code>.
</p>
<p>
	Notice this page (and all static pages that do not display data from the database) come from a "static" folder. However, if you were to make your own library, you don't need to follow this convention. Lithium's default convention/example (how you get it "out of the box") would be for all view templates under the "pages" folder to be rendered statically; no database calls. Minerva uses the <em><a href="#">PagesController</a></em> to render pages of all <em><a href="#">document_types</a></em> (which contain data from the database) as well as static pages. This leaves other templates like "read.html.php" and "index.html.php" in the main "pages" view directory to handle the dynamic content.
</p>

<h4>Layout</h4>
<p>
	To change Minerva's
	<em><a href="http://lithify.me/en/docs/lithium/template">layout</a></em> (the file containing
	the header, footer and default styles), edit the file
	<code><?php echo realpath(LITHIUM_APP_PATH . '/libraries/minerva/views/layouts/default.html.php'); ?></code>.
</p>

<h4>Routing</h4>
<p>
	To change Minerva's <em><a href="http://lithify.me/docs/lithium/net/http/Router">routing</a></em>, edit the file
	<code><?php echo realpath(LITHIUM_APP_PATH . '/libraries/minerva/config/routes.php'); ?></code>.<br />
	However, this is not recommended. Instead, it is ideal to create your own libraries with their own routes.php file. You may have conflicting routes, at which point, you can simply comment out those that conflict within Minerva's routes.php file. Take note that Minerva also provides admin routing and all Minerva routes are prefixed. So you likely will not run into a conflict, so much as you will have the desire to disable a few routes and/or change the prefix.
</p>

<h4>Minerva's Routing Prefix &amp; Admin Routing</h4>
<p>
	You will notice in Minerva's routes.php file, two variables in the routes. One for the main prefix which isolates Minerva from your main application and one for admin routes. By default Minerva's prefix is "minerva" and "admin" is used for the admin prefix which is used with the Minerva prefix, leaving everything under Minerva's prefix. If you wish to change these, can do so when you add the Minerva library with Libraries::add(). For example:<br />
	<code>Libraries::add('minerva', array('url' => 'new-minerva-prefix', 'admin_prefix' => 'new-admin-prefix');</code>
</p>

<h4>Admin Templates</h4>
<p>
	All admin templates are located under an "_admin" directory by Minerva convention. Unlike the admin prefix, you can't change this. For example core Minerva _admin templates are located at<br />
	<code><?php echo realpath(LITHIUM_APP_PATH . '/libraries/minerva/views/_admin/'); ?></code>.
</p>

<h4>Even More on Templates</h4>
<p>
	Minerva checks various paths for templates, which creatse a fallback system. For example, if the admin template was missing, Minerva would look for the non-admin version of that template to render. If you like the convention Minerva uses to build it's render paths. Your own libraries can use Minerva's template conventions by setting the option when including them. For example:<br />
	<code>Libraries::add('example', array('use_minerva_templates' => true));</code>
</p>
<p>
	Minerva's core models and controllers can be utilized in your own libraries. This is how you might create a "blog" page for example. When you create a library that does extend Minerva's features, your library's view folder should contain (in this case) a "pages" directory. You will need to define a route as well, but so long as Minerva is using your new library for pages, it will use your library's page templates if provided. Again, Minerva uses several render paths, so if you did not create a "read.html.php" page template, it will use the default template from the "minerva" library directory. You can also override the admin templates for our own library this way. This is how you could alter the admin interface, in case the default index table listing didn't suit your needs, without destroying core functionality.
</p>

<h4>Utilizing Minerva's Core in Your Libraries</h4>
<p>
	The pages is just one Minerva model that can be extended by your library. You can in fact extend <strong>pages</strong>, <strong>users</strong>, and <strong>blocks</strong> in your library so that you can enhance their functionality, add new fields (remember Minerva uses MongoDB which is schemaless), and provide new templates. All this, without touching any of the core code.
</p>
<p>
	Each Minerva model has a <a href="#">"document_type"</a> that is a reference to a library name. Minerva registers a new class type with Lithium called "minerva_models" and they sit in a new "minerva/models" directory within your library. This keeps them separate from your normal library's models to avoid any conflicts and for organizational reasons.
</p>
<p>
	Your library is able to completely do its own thing and then simply define a model within<br />
	<code><?php echo realpath(LITHIUM_APP_PATH); ?>/libraries/your_library/minerva/models/Page.php</code>.
</p>
<p>
	This will then allow you define a few properties on this new Minerva model class that will extend the schema, validation rules, and access. Note that from here, you could also apply filters to this new model or Minerva's core model. Always try to extend classes when you can (and you could even create a new PagesController in your library that extends Minerva's) but, when you have no other choice, Lithium's filters could be a great way to avoid altering core Minerva code. This is important if you wish to easily update Minerva in the future.
</p>
<p>
	Of course your library can easily change the default templates for the Minerva model by adding the appropriate directories under the views directory for the library. For example:<br />
	<code><?php echo realpath(LITHIUM_APP_PATH ); ?>/libraries/your_library/views/pages/read.html.php</code><br />
	<code><?php echo realpath(LITHIUM_APP_PATH ); ?>/libraries/your_library/views/pages/index.html.php</code><br />
	<code><?php echo realpath(LITHIUM_APP_PATH ); ?>/libraries/your_library/views/users/read.html.php</code><br />
	...and so on.
</p>

<h4>General Philosophy</h4>
<p>
	The general philosophy that Minerva goes by is that pretty much everything can be considered a "page" and it just varies page by page what data is displayed. Thanks to MongoDB's schemaless design, we can store one "page" document with a certain set of fields and then the very next document with a different set of fields. This idea is not original, there have been other CMS' in the past that were designed like this. However, the fatal flaw was that those CMS' were using databases like MySQL. Unfortunately, that's all that was logically available at the time too; so it's not their fault. This would require them to alter the schema quite often as the CMS was customized for the site at hand.
</p>
<p>
	Minerva's goal is to provide you with the most flexibility possible within reason. If what you are trying to do does not fit under the definition and functionality of a "page," "user" or a "block," then simply create a new model, controller, and view templates. Minerva does not want to alter the Lithium framework in anyway either. This ensures maximum flexibility and continued ease of updates. It also means that you can likely add Minerva's functionality to your existing applications. While Minerva will not directly alter your existing application or make any decisions for you about how you go about designing your own application, it can enhance your existing application. You could simply use Minerva to manage content under a "pages" collection on "minerva" MongoDB database and then use your own code to bring that data into your own application that is completely independent of Minerva.
</p>
<p>
	Despite trying to be flexible, Minerva does require MongoDB. However, your own application (and libraries) can use whatever datasource they like. Along with this requirement, there are also a few libraries that Minerva depends on. Likely your applications will benefit from these libraries as well and you may even already be using them. Minerva does not alter any of it's dependencies directly so they can exist as originally designed and, of course, can be updated. Be prepared for a few git submodules.
</p>
<p>
	As Minerva grows, new functionality will be added. In the early stages, there may be severe changes that could cause errors; however, the goal is to always remain backwards compatible. After Minerva is stable, updates will undergo a much more carefully designed process to ensure that you can always update Minerva without breaking (too much) of your application's code. It is always reccommended to keep a development environment to test updates. Minerva's philosophy is to remain compatible, but unfortunately it's literally impossible to know how your own code works.
</p>
<p>
	Minerva also believes in a community effort. Minerva is open source and will remain open source; it is free to use personally or commercially. There are several open source libraries that Minerva uses that are built by the Lithium community instead of reinventing the wheel. If you would like to contribute, please fork <a href="https://github.com/tmaiaroto/minerva" target="_blank">Minerva on Github</a>! Comments and suggestions are always welcome as are any <a href="https://github.com/tmaiaroto/minerva/issues" target="_blank">issues</a> that you can bring up and note on Github. Thanks!
</p>
<p>
	Finally, at this time, Minerva is not reccommended for production use unless you are very daring.
</p>