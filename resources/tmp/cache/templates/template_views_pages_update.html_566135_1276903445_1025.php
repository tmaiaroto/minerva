<div class="grid_4">
	<div class="box">
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Update Page</h2>    
    <div class="box">
	<?php echo $this->form->create(null, array('type' => 'file')); ?>
		<fieldset>
		<legend>Record</legend>
	    <?php 
	    	foreach($fields as $k => $v) {
	    		$v['value'] = $record->$k;
	    ?>
	    	<?php echo $this->form->field($k, $v); ?>
	    <?php } ?>	    
	    </fieldset>
	    <?php echo $this->form->submit('Edit Page'); ?>
	<?php echo $this->form->end(); ?>
	</div>
	
</div>

<div class="grid_4">
	<div class="box">
		<h2>Data Set by Filter From Library</h2>
		<div class="block">
			<p>Just running a var_dump() on $library_data ... It was set by applyFilter('setViewData') in the Page model of the library. This is another bridge between the core page view templates and any add-ons. It's selective too; within the filter, the name of the method is passed so you can send data to specific view templates.</p>
			<?php var_dump($library_data); ?>
		</div>
	</div>
</div>
<div class="clear"></div>
