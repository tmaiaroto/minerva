<div class="grid_4">
	<div class="box">
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Create Page</h2>
    
	<?php $this->form->config(array('templates' => array('error' => '<div class="error"{:options}>{:content}</div>'))); ?>
	<?=$this->form->create($page); ?>
	    <?php foreach($fields as $k => $v) { ?>
	    	<?=$this->form->field($k, $v);?>
	    <?php } ?>
	    <?=$this->form->submit('Add Page'); ?>
	<?=$this->form->end(); ?>
	
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
