<?php 

namespace App\Vendor;

use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;

class Email {

  function __construct() {

    $mail = new PHPMailer(true);

    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication    
    $mail->Host       = env('SMTP_HOST');
    $mail->Username   = env('SMTP_USER');
    $mail->Password   = env('SMTP_PASS');
    $mail->SMTPSecure = env('SMTP_TLS_SSL');
    $mail->Port       = env('SMTP_POST');                          // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

    // Attachments
    $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->setFrom('mohamedsaeed.ml.email@gmail.com', 'mohamedsaeed.ml');
    $mail->addAddress('m7md.sa3ed.a@gmail.com');
    $mail->Subject = '';
    $mail->Body = '';
    $mail->AltBody = '';
    
    $file = file_get_contents('app/vendor/verify-email.html');

    
  }

  function send() {
    
    try {
      $mail->send();
      echo 'Message has been sent';
    } catch (Exception $e) {
      echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

  }

}