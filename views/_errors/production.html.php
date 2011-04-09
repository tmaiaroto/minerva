<?php
$exception = $info['exception'];
?>
<h1 class="production-error-message-heading">Oops! We have a problem.</h1>
<div class="production-error-message">
	<?php if ($code = $exception->getCode()) {
        switch($code) {
            case 404:
            default:
                $this->title($code . ' Page Not Found');
                echo '<h3>' . $code . ' Page Not Found</h3>';
                echo '<p>The page you\'re looking for either does not exist or is otherwise unavailable.</p>';
                break;
            case 500:
                $this->title($code . ' Internal Server Error');
                echo '<h3>' . $code . ' Internal Server Error</h3>';
                echo '<p>There seems to be a problem on the server, please try again later.</p>';
                break;
        }
	} ?>
</div>