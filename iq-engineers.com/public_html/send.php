<?php

	include('class_email.php');

	$title = $_POST['title'];
    $name = $_POST['name'];
	$phone = $_POST['phone'];
	$email = $_POST['email'];
    $text = $_POST['text'];

    $subjectEmail = "Новое сообщение с сайта IQEngeneers!";
    $messageEmail = $title . "<br><br>";
    $messageEmail .= '<strong>Телефон:</strong><br> ' . $phone ."<br>";

    if ($name) {
        $messageEmail .= '<strong>Имя:</strong> ' . $name . "<br>";
    }
    if ($email) {
        $messageEmail .= '<strong>Email:</strong> ' . $email . "<br>";
    }

    if ($text) {
        $messageEmail .= '<strong>Сообщение:</strong> ' . $text . "<br>";
    }

    new Email(array(
        'subject'	=>	$subjectEmail,
        'body'		=>	$messageEmail,
        'from'      =>  $email,
        'sender'	=> 	array('email' => $email)
    ), Email::TYPE_TO_ADMIN,true);

?>