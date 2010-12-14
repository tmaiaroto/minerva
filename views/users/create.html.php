<div class="grid_11">
    <div id="left_column">
    <h2 id="page-heading">Crete a New User</h2>
    <p>
        This is the backend create user form. Front-end uses the "register" action.
    </p>
	<?=$this->form->create($user, array('action' => 'register')); ?>
	    <fieldset class="register">
                <legend>User Information</legend>
		<?=$this->form->create($user); ?>
		    <?php foreach($fields as $k => $v) { ?>
			<?php if(isset($v['form'])) { ?>
			    <?php $v['form']['type'] = (isset($v['form']['type'])) ? $v['form']['type']:'text'; ?>
			    <?php if($v['form']['type'] == 'select') { ?>
				<?=$this->form->label($v['form']['label']);?>
				<?=$this->form->select($k, $v['form']['options']);?>
			    <?php } else { ?>
				<?=$this->form->field($k, $v['form']);?>
			    <?php } ?>
			<?php } ?>
		    <?php } ?>
		    <?=$this->form->submit('Create User Account', array('class' => 'submit')); ?>
		<?=$this->form->end(); ?>
                <?php // echo $this->form->field('profile_pic', array('type' => 'file', 'label' => 'Profile Picture')); ?>
	    
            </fieldset>
	<?=$this->form->end(); ?>
    </div>
</div>

<div class="grid_5" id="right_grid">
	<div id="right_column">
            <div class="box terms_of_service_header">
                 <h2>Sidebar</h2>
            </div>
	    <div class="terms_of_service_box">
                <p>sidebar...</p>
            </div>
	</div>
</div>
<div class="clear"></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#password_input').val('');
        
        $('input').blur(function() {
           $('.input_help').hide();
           //$(this).siblings('.error').show();
           $(this).siblings('.in_use_error').show();
           $(this).siblings('.password_error').show();
        });
        
        $('input').focus(function() {
            $(this).parent().siblings().show();
            $(this).siblings('.error').hide();
            $(this).siblings('.in_use_error').hide();
            $(this).siblings('.password_error').hide();
        });
        
        $('#email_input').change(function() {
            $.get('/users/is_email_in_use/' + $('#email_input').val(), function(data) {
                if(data == 'true') {
                    $('.in_use_error').remove();
                    $('#email_input').parent().append('<div class="in_use_error" id="email_error">Sorry, this e-mail address is already registered.</div>');
                } else {
                    $('#email_error').remove();
                }
            });
        });
        
        $('#password_input').change(function() {
            if($('#password_input').val().length < 6) {
                $('.password_error').remove();
                $('#password_input').parent().append('<div class="password_error" id="password_error">Password must be at least 6 characters long.</div>');
            } else {
                $('#password_error').remove();
            }
        });
    });
</script>