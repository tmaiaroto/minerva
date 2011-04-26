<div id="missing_template" class="test-result test-result-fail error">
    Error: A view template seems to be missing.
</div>
<p>
    The requested template: <strong><?php echo $this->_config['template']; ?></strong><br />
    The requested layout: <strong><?php echo $this->_config['layout']; ?></strong><br />
    The requested library: <strong><?php echo $this->_config['request']->params['library']; ?></strong><br />
    The requested controller: <strong><?php echo $this->_config['controller']; ?></strong><br />
    The following paths are being checked for these templates:
</p>
<pre>
    <?php print_r($this->_config['paths']); ?>
</pre>