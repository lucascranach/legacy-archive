<?php

/**
 *
 * @author Joerg Stahlmann
 * @package src/ajax
 */
if (isset($_POST['send'])) {
    $arr = json_decode($_POST['send'], true);

    $to = 'cda-contact@smkp.de';
    $subject = $arr['subject'];
    $headers = "From: CDA-Nachricht <mail@lucascranach.org>\r\n";
    $headers .= "Return-Path: " . $arr['email'] . "\r\n";
    $text =  'Feedback zum Objekt: ' . $arr['objectId'];
    $text .= "\n\r";
    $text .=  $arr['message'];
    $text .= "\n\r";
    $text .= "\n\r";
    $text .= 'Anschrift:   ---------------------------------------';
    $text .= "\n";
    $text .= $arr['sender'];
    $text .= "\n";
    $text .= $arr['email'];

    if (mail($to, $subject, $text, $headers)) {
        echo 'true';
    } else {
        echo 'false';
    }
}
