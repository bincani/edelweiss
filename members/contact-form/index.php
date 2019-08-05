<?php
/**
A Simple Contact Form developed in PHP with HTML5 Form validation. Has a fallback
in pure JavaScript for browsers that do not support HTML5 form validation.
*/
require_once './Helpers/Config.class.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/vendor/autoload.php';

use Helpers\Config;
//https://github.com/pinceladasdaweb/Config

use Mailgun\Mailgun;
// https://github.com/mailgun/mailgun-php

$config = new Config;
$config->load('./config/config.php');

$errorMessage = $config->get('messages.error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = stripslashes(trim($_POST['form-name']));
    $email   = stripslashes(trim($_POST['form-email']));
    $phone   = stripslashes(trim($_POST['form-phone']));
    $subject = stripslashes(trim($_POST['form-subject']));
    $message = stripslashes(trim($_POST['form-message']));
    $pattern = '/[\r\n]|Content-Type:|Bcc:|Cc:/i';

    if (preg_match($pattern, $name) || preg_match($pattern, $email) || preg_match($pattern, $subject)) {
        die("Header injection detected");
    }

    $emailIsValid = filter_var($email, FILTER_VALIDATE_EMAIL);

    if ($name && $email && $emailIsValid && $subject && $message) {
        $postData = array();
        $postData['to'] = $config->get('emails.to');
        //$postData['from'] = $config->get('emails.from');
        //$mail->setSender($name);
        //$mail->setSenderEmail($email);
        $postData['from'] = sprintf("%s <%s>", $name, $email);

        $postData['subject'] = $config->get('subject.prefix') . ' ' . $subject;

        $body = "
        <!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
        <html>
            <head>
                <meta charset=\"utf-8\">
            </head>
            <body>
                <h1>{$subject}</h1>
                <p><strong>{$config->get('fields.name')}:</strong> {$name}</p>
                <p><strong>{$config->get('fields.email')}:</strong> {$email}</p>
                <p><strong>{$config->get('fields.phone')}:</strong> {$phone}</p>
                <p><strong>{$config->get('fields.message')}:</strong> {$message}</p>
            </body>
        </html>";

        //$postData['html'] = $body;
        $postData['text'] = $message;
        if ($config->get('mailgun.debug')) {
            echo sprintf("postData: <pre>%s</pre><br/>", print_r($postData, true));
        }
        $mailgun = new Mailgun($config->get('mailgun.key'));
        $sent = $mailgun->sendMessage($config->get('mailgun.domain'), $postData);
        if ($config->get('mailgun.debug')) {
            echo sprintf("response: <pre>%s</pre><br/>", print_r($sent, true));
        }
        if ($sent->{'http_response_code'} == 200) {
            $emailSent = true;
            $errorMessage = $sent->{'http_response_body'}->{'message'};
        }
        else {
            $hasError = true;
            $errorMessage = $sent->{'http_response_body'}->{'message'};
        }
    }
    else {
        $hasError = true;
    }
}
?><!DOCTYPE html>
<html>
<head>
    <title>Contact Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet" media="screen">
</head>
<body>
    <div class="page-header col-md-6 col-md-offset-3">
        <h2>Contact Form</h2>
    </div>
    <?php if (!empty($emailSent)): ?>
        <div class="col-md-6 col-md-offset-3">
            <div class="alert alert-success text-center"><?php echo $config->get('messages.success'); ?></div>
        </div>
        <div class="col-md-9 col-md-offset-3">
            <button type="button" class="btn btn-default" onclick="window.location.href='index.php';">Back to Contact Form</button>
        </div>
    <?php else: ?>
        <?php if(!empty($hasError)): ?>
            <div class="col-md-5 col-md-offset-4">
                <div class="alert alert-danger text-center"><?php echo $errorMessage; ?></div>
            </div>
        <?php endif; ?>
        <div class="col-md-6 col-md-offset-3">
            <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="application/x-www-form-urlencoded" id="contact-form" class="form-horizontal" method="post">
                <div class="form-group">
                    <label for="form-name" class="col-lg-2 control-label"><?php echo $config->get('fields.name'); ?></label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="form-name" name="form-name" placeholder="<?php echo $config->get('fields.name'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="form-email" class="col-lg-2 control-label"><?php echo $config->get('fields.email'); ?></label>
                    <div class="col-lg-10">
                        <input type="email" class="form-control" id="form-email" name="form-email" placeholder="<?php echo $config->get('fields.email'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="form-phone" class="col-lg-2 control-label"><?php echo $config->get('fields.phone'); ?></label>
                    <div class="col-lg-10">
                        <input type="tel" class="form-control" id="form-phone" name="form-phone" placeholder="<?php echo $config->get('fields.phone'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="form-subject" class="col-lg-2 control-label"><?php echo $config->get('fields.subject'); ?></label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="form-subject" name="form-subject" placeholder="<?php echo $config->get('fields.subject'); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="form-message" class="col-lg-2 control-label"><?php echo $config->get('fields.message'); ?></label>
                    <div class="col-lg-10">
                        <textarea class="form-control" rows="3" id="form-message" name="form-message" placeholder="<?php echo $config->get('fields.message'); ?>" required></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-offset-2 col-lg-10">
                        <button type="submit" class="btn btn-default"><?php echo $config->get('fields.btn-send'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <script type="text/javascript" src="public/js/contact-form.js"></script>
    <script type="text/javascript">
        new ContactForm('#contact-form');
    </script>
</body>
</html>
