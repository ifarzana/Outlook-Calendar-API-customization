<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');

  $event = (object)[];

  if ( !empty($_POST['subject']) ) {
    $event->Subject = $_POST['subject'];
  }

  if ( !empty($_POST['location']) ) {
    $event->Location = ['DisplayName' => $_POST['location']];
  }

  if ( !empty($_POST['start_date']) && empty($_POST['patternType']) ) {
    $startDate = date_create_from_format('Y-m-d', $_POST['start_date']);
    $startTime = date_create_from_format('H:i', $_POST['start_time']);
    $startDate->setTime($startTime->format('H'), $startTime->format('i'));

    $event->Start = [ 'DateTime' => OutlookCalendarService::encodeDateTime($startDate, $_POST['timezone']),
                      'TimeZone' => 'UTC' ];
  }

  if ( !empty($_POST['end_date']) && empty($_POST['patternType']) ) {
    $endDate = date_create_from_format('Y-m-d', $_POST['end_date']);
    $endTime = date_create_from_format('H:i', $_POST['end_time']);
    // $endTime = date_create_from_format('m/d/Y g:i A', $_POST['end_time']);
    $endDate->setTime($endTime->format('H'), $endTime->format('i'));

    $event->End = [ 'DateTime' => OutlookCalendarService::encodeDateTime($endDate, $_POST['timezone']),
                    'TimeZone' => 'UTC' ];
  }

  if ( !empty($_POST['attendee']) ) {
    $attendeeArr = [];
    $attendeeAddresses = array_filter(explode(',', $_POST['attendee']));

    foreach($attendeeAddresses as $address) {
      $attendee = [ 'EmailAddress' => ['Address' => $address],
                    'Type'         => 'Required' ];
      $attendeeArr[] = $attendee;
    }

    $event->Attendees = $attendeeArr;
  }

  if ( !empty($_POST['private_check']) ) {
    $event->Sensitivity = $_POST['private_check'] == 'on' ? 'Private' : 'Normal';
  }

  if ( !empty($_POST['reminder_time']) ) {
    $event->ReminderMinutesBeforeStart = intval($_POST['reminder_time']);
    $event->IsReminderOn = true;
  }

  if ( !empty($_POST['description']) ) {
    $htmlBody = '<html><body>'.$_POST['description'].'</body></html>';

    $event->Body = [ 'ContentType' => 'HTML',
                     'Content'     => $htmlBody ];
  }

  if ( !empty($_POST['patternType']) ) {
    $event->Recurrence = [
        'Pattern' => [
            'Type'           => $_POST['patternType'],
            'Interval'       => intval($_POST['patternInterval']),
            'Month'          => intval($_POST['patternMonth']),
            'DayOfMonth'     => intval($_POST['patternDayOfMonth']),
            'DaysOfWeek'     => explode(',', $_POST['patternDaysOfWeek']),
            'FirstDayOfWeek' => 'Sunday',
            'Index'          => $_POST['patternIndex']
        ],
        'Range'   => [
            'Type'                => !empty($_POST['rep_to']) ? 'EndDate' : 'NoEnd',
            'StartDate'           => $_POST['rep_from'],
            'EndDate'             => !empty($_POST['rep_to']) ? $_POST['rep_to'] : '0001-01-01',
            'RecurrenceTimeZone'  => 'Pacific Standard Time',
            'NumberOfOccurrences' => 0
        ]
    ];
  }

  $eventId = OutlookCalendarService::createEvent($_SESSION['access_token'], $_SESSION['user_email'], $event, $_POST['calendar_id']);
  error_log('Event Id: '.$eventId);

  header('Location: ./calendar.php');
 ?>
