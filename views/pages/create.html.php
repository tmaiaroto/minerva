<div class="grid_4">
	<div class="box">
	</div>
</div>


<div class="grid_8">
    <h2 id="page-heading">Create Page</h2>    
	<?php // $this->form->config(array('templates' => array('error' => '<div class="error"{:options}>{:content}</div>'))); ?>
	<?=$this->form->create($page); ?>
	    <?php foreach($fields as $k => $v) { ?>	    
		<?=$this->form->field($k, $v['form']);?>
	    <?php } ?>
	    <?=$this->form->submit('Add Page'); ?>
	<?=$this->form->end(); ?>
	
</div>

<div class="grid_4">
    <div class="box">
    </div>
</div>
<div class="clear"></div>
