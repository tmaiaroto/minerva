<style type="text/css">
.input_help {
    float: left;
    display: block;
}
.input div, .input .in_use_error {
    display: block;
    float: left;
}
.input div {
    margin-bottom: 10px;
    width: 295px;
}
.input {
    clear: left;
}
.submit {
    clear: left;
    display: block;
}
.error {
    clear: left;
    color: #bf0000;
    display: block;
    margin-left: 108px;
    height: 10px;
    padding: 0px;
}
</style>

<div class="grid_11">
    <div id="left_column">
    <h2 id="page-heading">Register</h2>
	<?=$this->form->create($user, array('action' => 'register', 'onSubmit' => 'return submitCheck();')); ?>
	    <fieldset class="register">
                <legend>User Information</legend>
                <div class="input"><?=$this->form->field('email', array('label' => 'E-mail', 'id' => 'email_input'));?><div class="input_help">Please enter your e-mail address.</div></div>
                <div class="input"><?=$this->form->field('password', array('label' => 'Password', 'id' => 'password_input'));?><div class="input_help">Choose a password at least 6 characters long.</div></div>
                <div class="input"><?=$this->form->field('password_confirm', array('label' => 'Confirm Password', 'id' => 'password_confirm_input'));?><div class="input_help">Just to be sure, type your password again.</div></div>
		<?php // echo $this->form->field('profile_pic', array('type' => 'file', 'label' => 'Profile Picture')); ?>
	    <?=$this->form->submit('Create my account', array('class' => 'submit')); ?>
            <br style="clear: left;" /><p class="agree_to_terms small">Note: Your e-mail address is your username.<br />By clicking on the "create my account" button, you are agreeing to the Terms of Service and the Privacy Policy.</p>
            </fieldset>
	<?=$this->form->end(); ?>
    </div>
</div>

<div class="grid_5" id="right_grid">
	<div id="right_column">
            <div class="box terms_of_service_header">
                 <h2>Terms of Service</h2>
            </div>
	    <div class="terms_of_service_box">
                <p class="small">These Terms of Service ("Terms") govern your access to and use of the services and Minerva's websites (the "Services"), and any information, text, graphics, or other materials uploaded, downloaded or appearing on the Services (collectively referred to as "Content"). Your access to and use of the Services is conditioned on your acceptance of and compliance with these Terms. By accessing or using the Services you agree to be bound by these Terms.
                </p>
                <p class="small"><strong>Passwords</strong><br />
                You are responsible for safeguarding the password that you use to access the Services and for any activities or actions under your password. We encourage you to use “strong” passwords (passwords that use a combination of upper and lower case letters, numbers and symbols) with your account. Minerva cannot and will not be liable for any loss or damage arising from your failure to comply with the above requirements.
                </p>
            </div>
            <br />
	</div>
</div>
<div class="clear"></div>

<script type="text/javascript">
    function submitCheck() {
	if(($('#password_input').val() != $('#password_confirm_input').val()) || ($('#password_input').val() == '')) {
	    $('#password_confirm_input').parent().siblings('.input_help').hide();
	    $('#password_confirm_error').remove();
	    $('#password_confirm_input').parent().parent().append('<div id="password_confirm_error">Passwords must match.</div>');
	    return false;
	}
	return true;
    }
    $(document).ready(function() {
	$('#password_input').val('');
        
        $('input').blur(function() {
	    $('.input_help').hide();
	    
	    if($('#email_input').val().length < 5) {
		$('#email_error').remove();
                $('#email_input').parent().parent().append('<div id="email_error">You must provide your e-mail.</div>');
	    }
	    if($('#password_input').val().length < 6) {
		$('#password_error').remove();
                $('#password_input').parent().parent().append('<div id="password_error">Password must be at least 6 characters long.</div>');
	    }
	    $('.input_help').hide();
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