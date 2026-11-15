<?php
	require_once "Mail.php";
	require_once "Mail/smtp.php";

	$host = "ssl://smtp.gmail.com";
	$username = getenv("ADMIN_EMAIL");
	$password = getenv("ADMIN_PASSWORD");
	$port = "465";
	$to = $_GET["email"];

	$email_from = getenv("ADMIN_EMAIL");
	$email_subject = "Thank you for signing up for email notifications";
	$email_body = "Testing if this works, if it does, this should say something like 'you have signed up for emails for game.name, you will receive an email anytime it gets an update in our database";
	$email_address = getenv("ADMIN_EMAIL");
	$content = "text/html; charset=utf-8";
	$mime = "1.0";

	$headers = array ('From' => $email_from, 
		'To' => $to,
		'Subject' => $email_subject,
		'Reply-To' => $email_address,
		'MIME-Version' => $mime,
		'Content-type' => $content);

	$params = array ('host' => $host,
		'port' => $port,
		'auth' => true,
		'username' => $username,
		'password' => $password);

	$smtp = Mail::factory ('smtp', $params);
	$mail = $smtp->send($to, $headers, $email_body);
	
	if (PEAR::isError($mail)) {
		http_response_code(500);
		echo "Email failed: " . $mail->getMessage();
	} else {
		echo "Email sent successfully";
	}
?>
