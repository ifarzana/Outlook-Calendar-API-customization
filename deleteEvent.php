<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');

  $eventId = $_GET['eventId'];

  OutlookCalendarService::deleteEvent($_SESSION['access_token'], $_SESSION['user_email'], $eventId);

  header('Location: ./calendar.php');
 ?>
