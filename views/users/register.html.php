<div id="content"> 
	<?=$this->form->create($user, array('action' => 'register', 'onSubmit' => 'return submitCheck();')); ?>
	    <fieldset class="register">
                <legend>Register</legend>
				<div class="input"><?=$this->form->field('first_name', array('label' => 'First Name'));?></div>
				<div class="input"><?=$this->form->field('last_name', array('label' => 'Last Name'));?></div>
                <div class="input"><?=$this->form->field('email', array('label' => 'E-mail (also your username)', 'id' => 'email_input'));?><div class="input_help_right">Please enter your e-mail address.</div></div>
                <div class="input"><?=$this->form->field('password', array('type' => 'password', 'label' => 'Password', 'id' => 'password_input'));?><div class="input_help_right">Choose a password at least 6 characters long.</div></div>
                <div class="input"><?=$this->form->field('password_confirm', array('type' => 'password', 'label' => 'Confirm Password', 'id' => 'password_confirm_input'));?><div class="input_help_right">Just to be sure, type your password again.</div></div>
		<?=$this->form->submit('Create my account', array('class' => 'submit')); ?>
            </fieldset>
	<?=$this->form->end(); ?>
</div>

<div class="clear"></div>

<script type="text/javascript">
    function submitCheck() {
	if(($('#password_input').val() != $('#password_confirm_input').val()) || ($('#password_input').val() == '')) {
	    $('#password_confirm_input').parent().siblings('.input_help_right').hide();
	    $('#password_confirm_error').remove();
	    $('#password_confirm_input').parent().parent().append('<div class="input_error_right error" id="password_confirm_error">Passwords must match.</div>');
	    return false;
	}
	return true;
    }
    $(document).ready(function() {
	$('#password_input').val('');
        
        $('input').blur(function() {
			$('.input_help_right').hide();
			
			if($('#email_input').val().length < 5) {
				$('#email_error').remove();
				$('#email_input').parent().parent().append('<div class="input_error_right error" id="email_error">You must provide your e-mail.</div>');
			}
			if($('#password_input').val().length < 6) {
				$('#password_error').remove();
				$('#password_input').parent().parent().append('<div class="input_error_right error" id="password_error">Password must be at least 6 characters long.</div>');
			}
			$('.input_help_right').hide();
			//$(this).siblings('.error').show();
			$(this).siblings('#email_error').show();
			$(this).siblings('#password_error').show();
			$(this).siblings('#password_confirm_error').show();
        });
        
        $('input').focus(function() {
            $(this).parent().siblings().show();
            $(this).parent().siblings('.error').hide();
            $(this).parent().siblings('#email_error').hide();
            $(this).parent().siblings('#password_error').hide();
			$(this).parent().siblings('#password_confirm_error').hide();
        });
        
        $('#email_input').change(function() {
	    $.get('/users/is_email_in_use/' + $('#email_input').val(), function(data) {
                if(data == 'true') {
                    $('#email_error').remove();
                    $('#email_input').parent().parent().append('<div id="email_error">Sorry, this e-mail address is already registered.</div>');
                } else {
                    $('#email_error').remove();
                }
            });
        });
        
    });
</script>