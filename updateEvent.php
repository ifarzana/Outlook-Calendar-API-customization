<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');

  $eventId = $_GET['eventId'];
  $subject = 'updatedSubject';

  $updatedEvent = [
    'Subject' => $subject
  ];

  $response = OutlookCalendarService::updateEvent($_SESSION['access_token'], $_SESSION['user_email'], $eventId, $updatedEvent);
  error_log('Event: '.$response);

  header('Location: ./calendar.php');
 ?>
