<div id="missing_template" class="test-result test-result-fail error">
    Error: A view template seems to be missing.
</div>
<p>
    The requested template: <strong><?php echo (isset($this->_config['template'])) ? $this->_config['template']:'unknown'; ?></strong><br />
    The requested layout: <strong><?php echo (isset($this->_config['layout'])) ? $this->_config['layout']:'unknown'; ?></strong><br />
    The requested library: <strong><?php echo (isset($this->_config['request']->params['library'])) ? $this->_config['request']->params['library']:'unknown'; ?></strong><br />
    The requested controller: <strong><?php echo (isset($this->_config['controller'])) ? $this->_config['controller']:'uknown'; ?></strong><br />
    The following paths are being checked for these templates:
</p>
<pre>
    <?php print_r($this->_config['paths']); ?>
</pre>