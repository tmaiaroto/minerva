<?php
/*
 * A general e-mail utility class for familyspoon.com. This includes several useful methods for sending e-mails.
 * There isn't much sense in a "wrapper" class since SwiftMailer can be used very easily and directly by any class.
 * However, this class has some methods that will keep things a little more modular leaving other classes cleaner.
 *
 * In particular, all of the confirmation and notification e-mails are defined here.
 * So when a user requests to join a family, the e-mail comes from here. Any copy adjustments, etc. are in one place.
 * This class is responsible for delivering all e-mail messages from Family Spoon.
 * 
*/
namespace minerva\util;

use \Swift_Mailer;
use \Swift_Message;

class Email {
    
    protected static $transport;
    
    protected static $config = array();
    
    public function __init(array $config = array()) {
        /* Can use any of these for the transport method
        * Swift_MailTransport
        * Swift_SmtpTransport
        * Swift_SendmailTransport
        * Also shorthand, "smtp" and "mail" and "sendmail" work...but method should be a class name otherwise
        */
        $defaults = array('transport' => array(
                                               'method' => 'Swift_SmtpTransport',
                                               'username' => 'tom.maiaroto@gmail.com',
                                               'password' => '19maiar82',
                                               'server' => 'smtp.gmail.com',
                                               'port' => 465,
                                               'encryption' => 'ssl'
                                              )
        );
        self::$config = $config + $defaults;
                
        switch(self::$config['transport']['method']) {
            case 'Swift_SmtpTransport':
            case 'smtp':
                self::$transport = \Swift_SmtpTransport::newInstance(self::$config['transport']['server'], self::$config['transport']['port'], self::$config['transport']['encryption'])
                    ->setUsername(self::$config['transport']['username'])
                    ->setPassword(self::$config['transport']['password']);
            break;
            case 'Swift_MailTransport':
            case 'mail':
                self::$transport = \Swift_MailTransport::newInstance();
            break;
            case 'Swift_SendmailTransport':
            case 'sendmail':
                self::$transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
            break;
        }
    }
    
    // TODO: write some test cases! For everything, but now is especially a good time to write some.
    public function simple() {
        // SWIFTMAILER TEST ... it works. it was easy.
        
        $mailer = Swift_Mailer::newInstance(self::$transport);
            
        $message = Swift_Message::newInstance()
            
              //Give the message a subject
              ->setSubject('Test Message')
            
              //Set the From address with an associative array
              ->setFrom(array('tom@shift8creative.com' => 'Tom'))
            
              //Set the To addresses with an associative array
              ->setTo(array('tom.maiaroto@gmail.com'))
            
              //Give it a body
              ->setBody('Here is the message itself.')
            
              //And optionally an alternative body
              ->addPart('<q>Here is the message itself.</q>', 'text/html')
            
              //Optionally add any attachments
              //->attach(Swift_Attachment::fromPath('my-document.pdf'))
              ;
              $result = $mailer->send($message);
            //// end test
            
    }
    
    /*
     * Send a basic message.
     * This one is pretty much a wrapper. The only thing that is done automatically is setup of the transport method.
     * Other than that, there's no real time saved over calling Swift_Mailer directly.
     *
     * @param $options Array
     *      - to: Array list of e-mail addresses, ex. array('email@site.com', ;email2@site.com;) or array('email@site.com' => 'name', 'email2@site.com' => 'name2')
     *      - from: From address in array or string format, ex. array('email@site.com' => 'Name') or 'email@site.com'
     *      - subject: The subject of the e-mail
     *      - body: The body copy for the e-mail
     *      - format: The e-mail message format, HTML or plain text, ex. 'text/html' or 'text/plain' or 'text' or 'html'
     * @return Boolean Whether the e-mail was sent or not.
    */
    public function message($options=array()) {
        $defaults = array('to' => array('tom.maiaroto@gmail.com'), 'from' => array('tom@shift8creative.com' => 'Tom'), 'body' => 'Default e-mail copy.', 'subject' => 'Default Subject', 'format' => 'text/html');
        $options += $defaults;
        
        // Shorthand options
        if($options['format'] == 'text') {
            $options['format'] = 'text/plain';
        }
        if($options['format'] == 'html') {
            $options['format'] = 'text/html';
        }
        
        $mailer = Swift_Mailer::newInstance(self::$transport);
        
        $message = Swift_Message::newInstance()
        ->setSubject($options['subject'])
        ->setFrom($options['from'])
        ->setTo($options['to'])
        ->setBody($options['body'], $options['format']);
        
        if($mailer->send($message)) {
            return true;
        } else {
            return false;
        }
    }
    
    // TODO: Built out methods for all of the approval actions and such...
    
    /*
     * The e-mail that gets sent out to all family heads after a user requests to join their family.
     * TODO:
     *
     * @param $options Array
     *      - to: The array of e-mail addresses for the family heads
     *      - family_name: The family name
     *      - family_url: The family URL
     *      - approval_code: The approval code (for the approval link in the e-mail)
     *      - username: Username of the requesting user (maybe provide more info, profile pic?)
     * @return
    */
    public function newMemberRequest($options=array()) {
        // todo: call... $this->message(); ... from here
        
    }
    
    /*
     * The e-mail that gets sent out to a visitor requesting membership to Family Spoon.
     *
     * @param $options Array
     *      - to: The array of e-mail addresses of the new user
     *      - approval_code: The approval code for the new user
     *      - first_name: The first name of the new user
     *      - last_name: The last name of the new user
     * @return
    */
    public function newUserRegistration($options=array()) {
        Email::message(array(
           'to' => $options['to'],
           'from' => array('tom@shift8creative.com' => 'Family Spoon'),
           'subject' => 'Confirm your regirstration to FamilySpoon.com',
           'body' => '<p>' . $options['first_name'] . ' ' . $options['last_name'] . ',</p>' .
                        '<p>Thank you for joining Family Spoon! We\'re all ready with your account, but we\'ll need ' .
                        'to ensure that you are a real person and confirm your request. If you did not request an ' .
                        'account, then someone else using your e-mail address has. Please notify admin@familyspoon.com ' .
                        'if this is the case and if you are concerned. Otherwise, you can simply ignore this e-mail.</p>' .
                        '<p>Please go to the following URL to confirm your registration: ' .
                        '<a href="http://' . $_SERVER['HTTP_HOST'] . '/confirm-registration/' . $options['approval_code'] . '">http://' . $_SERVER['HTTP_HOST'] . '/users/confirm/' . $options['approval_code'] . '</a>' .
                        '</p><p>Thank you, <br />The Family Spoon Team</p>'
        ));
        
    }
    
    /*
     * The e-mail that gets sent out to a user requesting to change their account's e-mail address.
     *
     * @param $options Array
     *      - to: The array of e-mail addresses of the new user
     *      - approval_code: The approval code for the new user
     *      - first_name: The first name of the new user
     *      - last_name: The last name of the new user
     * @return
    */
    public function changeUserEmail($options=array()) {
        Email::message(array(
           'to' => $options['to'],
           'from' => array('tom@shift8creative.com' => 'Family Spoon'),
           'subject' => 'Confirm your new e-mail address for FamilySpoon.com',
           'body' => '<p>' . $options['first_name'] . ' ' . $options['last_name'] . ',</p>' .
                        '<p>We received your request to change your e-mail address, but before we do we want to make sure you entered everything properly. So you will need to confirm this change by clicking the following link: ' .
                        '<a href="http://' . $_SERVER['HTTP_HOST'] . '/confirm-email-change/' . $options['approval_code'] . '">http://' . $_SERVER['HTTP_HOST'] . '/users/confirm/' . $options['approval_code'] . '</a>' .
                        '</p></p>Once you confirm the change, the change will become active and you will need to login using this new e-mail address.</p>'.
                        '<p>Thank you, <br />The Family Spoon Team</p>'
        ));
        
    }
    
}
?>