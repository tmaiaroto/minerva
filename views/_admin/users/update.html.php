<div class="grid_16">
	<h2 id="page-heading">Update <?=$display_name; ?></h2>  
</div>
<div class="clear"></div>

<div class="grid_12">
	<?php // $this->form->config(array('templates' => array('error' => '<div class="error"{:options}>{:content}</div>'))); ?>
	<?=$this->form->create($document); ?>
	<?=$this->minervaForm->form_section($fields, 'main', array('fieldset' => 'admin', 'legend' => 'Primary Information')); ?>
	<?=$this->form->submit('Edit ' . $display_name); ?> <?=$this->html->link('Cancel', array('admin' => $this->minervaHtml->admin_prefix, 'library' => 'minerva', 'controller' => 'users', 'action' => 'index')); ?>	
</div>

<div class="grid_4">
    <div class="box">
        <h2>Options</h2>
	    <div class="block">
			<?=$this->minervaForm->form_section($fields, 'options', array('fieldset' => 'admin')); ?>
        </div>
    </div>
</div>

<?=$this->form->end(); ?>
<div class="clear"></div>