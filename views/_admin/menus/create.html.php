<?=$this->form->create($block); ?>
    <?php foreach($fields as $k => $v) { ?>
    	<?=$this->form->field($k, $v);?>
    <?php } ?>
    <?=$this->form->submit('Add Menu'); ?>
<?=$this->form->end(); ?>
