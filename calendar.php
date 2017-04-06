<?php
  session_start();
  require('oauth.php');
  require('outlook_calendar.php');
  require_once('sessionManager.php');

  $loggedIn = !is_null($_SESSION['access_token']);
  $redirectUri = 'http://localhost:8888/Outlook_API_Implementation/authorize.php';

  function getWeeks($date, $rollover)
  {
      $cut = substr($date, 0, 8);
      $daylen = 86400;

      $timestamp = strtotime($date);
      $first = strtotime($cut . "01");
      $elapsed = ($timestamp - $first) / $daylen;

      $weeks = 1;

      for ($i = 1; $i <= $elapsed; $i++)
      {
          $dayfind = $cut . (strlen($i) < 2 ? '0' . $i : $i);
          $daytimestamp = strtotime($dayfind);

          $day = strtolower(date("l", $daytimestamp));

          if($day == strtolower($rollover))  $weeks ++;
      }

      return $weeks;
  }
 ?>
<html>
  <head>
    <title>PHP Calendar API</title>
    <script type="text/javascript" src="./Datepicker/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="./Datepicker/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="./Datepicker/bootstrap/dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="./Datepicker/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
    <link rel="stylesheet" href="./Datepicker/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./Datepicker/bootstrap/dist/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" href="./Datepicker/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css" />

    <style>
      table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
      }
      th, td {
        padding: 5px;
      }
      .occur {
        display: none;
      }
      .occur.active {
        display: block;;
      }
    </style>
  </head>
  <body>
    <?php
      if (!$loggedIn) {
     ?>
        <!-- User not logged in, prompt for login -->
        <p>Please <a href="<?php echo oAuthService::getLoginUrl($redirectUri)?>">sign in</a> with your Office 365 or Outlook.com account.</p>
    <?php
      }
      else {
        $calendars = OutlookCalendarService::getCalendars($_SESSION['access_token'], $_SESSION['user_email']);

        if (SessionManager::checkResponseAndRefreshToken($calendars, $redirectUri)) {
          // Pick up new access token
          $accessToken = $_SESSION['accessToken'];

          error_log("Retrying get events request");
          $calendars = OutlookCalendarService::getCalendars($_SESSION['access_token'], $_SESSION['user_email']);
        }
     ?>

         <!-- Modal -->
         <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
           <div class="modal-dialog" role="document">
             <div class="modal-content">
               <div class="modal-header">
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                 <h4 class="modal-title" id="myModalLabel">Select repeat pattern</h4>
               </div>
               <div class="modal-body">
                 <p>
                   <label for="rep_occur" class="label-for-control">Occurs</label>
                   <select id="rep_occur">
                     <option value="occur_1">Daily</option>
                     <option value="occur_2">Weekly</option>
                     <option value="occur_3">The same day each month</option>
                     <option value="occur_4">The same week each month</option>
                     <option value="occur_5">The same day each year</option>
                     <option value="occur_6">The same week each year</option>
                   </select>
                 </p>

                 <p id="occur_1" class="occur active">
                   <label for="occur_day" class="label-for-control">Every</label>
                   <input type="text" id="occur1_day" value="1" style="width: 30px;">
                   <label for="occur_day" class="label-for-control">days</label><br>
                 </p>

                 <p id="occur_2" class="occur" hidden>
                   <label for="occur2_week" class="label-for-control">Every</label>
                   <input type="text" id="occur2_week" style="width: 30px;" value="1">
                   <label for="occur2_week" class="label-for-control">weeks on</label>

                   <input type="checkbox" id="occur2_mon_check" <?php echo date('N')==1?checked:''; ?>>
                   <label for="mon_check">Mon</label>
                   <input type="checkbox" id="occur2_tue_check" <?php echo date('N')==2?checked:''; ?>>
                   <label for="tue_check">Tue</label>
                   <input type="checkbox" id="occur2_wed_check" <?php echo date('N')==3?checked:''; ?>>
                   <label for="wed_check">Wed</label>
                   <input type="checkbox" id="occur2_thu_check" <?php echo date('N')==4?checked:''; ?>>
                   <label for="thu_check">Thu</label>
                   <input type="checkbox" id="occur2_fri_check" <?php echo date('N')==5?checked:''; ?>>
                   <label for="fri_check">Fri</label>
                   <input type="checkbox" id="occur2_sat_check" <?php echo date('N')==6?checked:''; ?>>
                   <label for="sat_check">Sat</label>
                   <input type="checkbox" id="occur2_sun_check" <?php echo date('N')==7?checked:''; ?>>
                   <label for="sun_check">Sun</label><br>
                 </p>

                 <p id="occur_3" class="occur" hidden>
                   <label for="occur_day" class="label-for-control">Day</label>
                   <input type="text" id="occur3_day" style="width: 30px;" value=<?php echo date('j'); ?>>
                   <label for="occur_day" class="label-for-control">of every</label>
                   <input type="text" id="occur3_month" style="width: 30px;" value="1">
                   <label for="occur_day" class="label-for-control">months</label>
                 </p>

                 <p id="occur_4" class="occur" hidden>
                   <label class="label-for-control">Every</label>
                   <input type="text" id="occur4_month" style="width: 30px;" value="1">
                   <label class="label-for-control">months on the</label>
                   <select id="occur4_weekNum">
                     <option value="first">first</option>
                     <option value="second">second</option>
                     <option value="third">third</option>
                     <option value="fourth">fourth</option>
                     <option value="last">last</option>
                   </select>
                   <select id="occur4_weekDay">
                     <option value="Sunday" <?php echo date('N')==7?selected:''; ?>>Sunday</option>
                     <option value="Monday" <?php echo date('N')==1?selected:''; ?>>Monday</option>
                     <option value="Tuesday" <?php echo date('N')==2?selected:''; ?>>Tuesday</option>
                     <option value="Wednesday" <?php echo date('N')==3?selected:''; ?>>Wednesday</option>
                     <option value="Thursday" <?php echo date('N')==4?selected:''; ?>>Thursday</option>
                     <option value="Friday" <?php echo date('N')==5?selected:''; ?>>Friday</option>
                     <option value="Saturday" <?php echo date('N')==6?selected:''; ?>>Saturday</option>
                   </select>
                 </p>

                 <p id="occur_5" class="occur" hidden>
                   <label for="occur5_month" class="label-for-control">On</label>
                   <select id="occur5_month">
                     <option value="1">January</option>
                     <option value="2">February</option>
                     <option value="3">March</option>
                     <option value="4">April</option>
                     <option value="5">May</option>
                     <option value="6">June</option>
                     <option value="7">July</option>
                     <option value="8">August</option>
                     <option value="9">September</option>
                     <option value="10">October</option>
                     <option value="11">November</option>
                     <option value="12">December</option>
                   </select>
                   <input type="text" style="width: 30px;" id="occur5_day" value="<?php echo date('d'); ?>">
                 </p>

                 <p id="occur_6" class="occur" hidden>
                   <label class="label-for-control">On the</label>
                   <select id="occur6_weekNum">
                     <option value="first">first</option>
                     <option value="second">second</option>
                     <option value="third">third</option>
                     <option value="fourth">fourth</option>
                     <option value="last">last</option>
                   </select>
                   <select id="occur6_weekDay">
                     <option value="Sunday" <?php echo date('N')==7?selected:''; ?>>Sunday</option>
                     <option value="Monday" <?php echo date('N')==1?selected:''; ?>>Monday</option>
                     <option value="Tuesday" <?php echo date('N')==2?selected:''; ?>>Tuesday</option>
                     <option value="Wednesday" <?php echo date('N')==3?selected:''; ?>>Wednesday</option>
                     <option value="Thursday" <?php echo date('N')==4?selected:''; ?>>Thursday</option>
                     <option value="Friday" <?php echo date('N')==5?selected:''; ?>>Friday</option>
                     <option value="Saturday" <?php echo date('N')==6?selected:''; ?>>Saturday</option>
                   </select>
                   <label class="label-for-control">of</label>
                   <select id="occur6_month">
                     <option value="1">January</option>
                     <option value="2">February</option>
                     <option value="3">March</option>
                     <option value="4">April</option>
                     <option value="5">May</option>
                     <option value="6">June</option>
                     <option value="7">July</option>
                     <option value="8">August</option>
                     <option value="9">September</option>
                     <option value="10">October</option>
                     <option value="11">November</option>
                     <option value="12">December</option>
                   </select>
                 </p>

               </div>
               <div class="modal-footer">
                 <button type="button" class="btn btn-primary" onclick="repetitionSaveAction()">Save</button>
                 <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
               </div>
             </div>
          </div>
        </div>

        <div class="container">
          <div class="row">
            <h2>Your calendars</h2>
            <?php
              $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

              $events = OutlookCalendarService::getEvents($_SESSION['access_token'], $_SESSION['user_email']);
             ?>
              <select>
                <?php foreach($calendars as $calendar) { ?>
                  <option value=<?php echo $calendar['Id'] ?>><?php echo $calendar['Name'] ?></option>
                <?php
                  }
                 ?>
              </select>

              <h2>Your events</h2>

              <table>
                <tr>
                  <th>Subject</th>
                  <th>Start</th>
                  <th>End</th>
                  <th></th>
                  <th></th>
                </tr>

                <?php foreach($events as $event) { ?>
                  <tr>
                    <td><?php echo $event['Subject'] ?></td>
                    <td><?php echo date_format(date_create($event['Start']['DateTime']), "m/d/Y H:i") ?></td>
                    <td><?php echo date_format(date_create($event['End']['DateTime']), "m/d/Y H:i") ?></td>
                    <td><input type="button" onclick="location.href='./updateEvent.php?eventId=<?php echo $event['Id'] ?>';" value="Update" /></td>
                    <td><input type="button" onclick="location.href='./deleteEvent.php?eventId=<?php echo $event['Id'] ?>';" value="Delete" /></td>
                  </tr>
                <?php
                } ?>
              </table>
              <hr/>
              <h4>Create your new event.</h4>
              <div>
                <form action="createEvent.php" method="post">
                  <p>
                    <select name="calendar_id" id="calendar_id">
                      <?php foreach($calendars as $calendar) { ?>
                        <option value=<?php echo $calendar['Id'] ?>><?php echo $calendar['Name'] ?></option>
                      <?php
                        }
                       ?>
                    </select>
                  </p>

                  <p>
                    <select name="timezone">
                      <?php foreach($tzlist as $timezone) { ?>
                        <option value=<?php echo $timezone ?> <?php echo $timezone == date('e') ? 'selected':''; ?>><?php echo $timezone ?></option>
                      <?php
                        }
                       ?>
                    </select>
                  </p>

                  <label for="subject" class="control-label">Subject</label>
                  <input type="text" id="subject" name="subject"><br>

                  <label for="location" class="control-label">Location</label>
                  <input type="text" id="location" name="location"><br>

                  <label for="attendee" class="control-label">Attendee</label>
                  <input type="text" id="attendee" name="attendee"><br><br>

                  <div>
                    <label for="datepicker_start">Start</label>
                    <input type="date" id='datepicker_start' name="start_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="time" id='timepicker_start' name="start_time" value="<?php echo date('h:i'); ?>">
                  </div>

                  <div>
                    <label for="datepicker_end">End</label>
                    <input type="date" id='datepicker_end' name="end_date" value="<?php echo date('Y-m-d'); ?>">
                    <input type="time" id='timepicker_end' name="end_time" value="<?php echo date('h:i'); ?>">
                  </div><br>

                  <div>
                    <input type="checkbox" id="private_check" name="private_check" >
                    <label for="private_check">Private</label>
                  </div>

                  <label for="reminder" class="label-for-control">Reminder</label>
                  <select id="reminder" name="reminder_time">
                    <option value="0">None</option>
                    <option value="0">0 minute</option>
                    <option value="5">5 minutes</option>
                    <option value="10">10 minutes</option>
                    <option value="15">15 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="60">1 hour</option>
                    <option value="120">2 hours</option>
                    <option value="180">3 hours</option>
                    <option value="240">4 hours</option>
                  </select><br/>

                  <label for="repetition" class="label-for-control">Repeat</label>
                  <select id="repetition" name="repetition">
                    <option value="rep_0">Never</option>
                    <option value="rep_1">Every day</option>
                    <option value="rep_2">Every <?php echo date('l'); ?></option>
                    <option value="rep_3">Every workday</option>
                    <option value="rep_4">Day <?php echo date('d'); ?> of every month</option>
                    <option value="rep_5">Every <?php echo getWeeks("2017-01-08", "sunday"); ?> <?php echo date('l'); ?></option>
                    <option value="rep_6">Every <?php echo date('F').' '.date('d'); ?></option>
                    <option value="rep_7">Other...</option>
                  </select><br>

                  <div id="rep_date" hidden>
                    <label for="rep_from" class="label-for-control">From</label>
                    <input type="date" id="rep_from" name="rep_from" value="<?php echo date('Y-m-d'); ?>">

                    <label for="rep_to" class="label-for-control">To</label>
                    <input type="date" id="rep_to" name="rep_to">
                  </div><br>

                  <label for="description" class="label-for-control">Description</label><br>
                  <textarea rows="4" cols="50" type="text" id="description" name="description"></textarea><br>

                  <input type="text" name="patternType" id="patternType" hidden>
                  <input type="text" name="patternInterval" id="patternInterval" value=1 hidden>
                  <input type="text" name="patternMonth" id="patternMonth" value=0 hidden>
                  <input type="text" name="patternDayOfMonth" id="patternDayOfMonth" value=0 hidden>
                  <input type="text" name="patternDaysOfWeek" id="patternDaysOfWeek" value="" hidden>
                  <input type="text" name="patternIndex" id="patternIndex" value="First" hidden>

                  <br>
                  <input type="submit" value="Create Event">
                </form>
              </div>
          </div>
        </div>
    <?php
      }
    ?>
  </body>
  <script type="text/javascript">
    repetitionSaveAction = function(e) {
      document.getElementById('patternInterval').value = 1;
      document.getElementById('patternMonth').value = 0;
      document.getElementById('patternDayOfMonth').value = 0;
      document.getElementById('patternDaysOfWeek').value = "";
      document.getElementById('patternIndex').value = "First";

      switch (document.getElementById('rep_occur').value) {
        case 'occur_1':
          document.getElementById('repetition').selectedIndex = 0;
          document.getElementById('patternType').value = "Daily";
          document.getElementById('patternInterval').value = document.getElementById('occur1_day').value;
          break;
        case 'occur_2':
          document.getElementById('repetition').selectedIndex = 1;
          document.getElementById('patternType').value = "Weekly";
          document.getElementById('patternInterval').value = document.getElementById('occur2_week').value;
          var days = "";
          days += document.getElementById('occur2_mon_check').checked ? ",Monday":'';
          days += document.getElementById('occur2_tue_check').checked ? ",Tuesday":'';
          days += document.getElementById('occur2_wed_check').checked ? ",Wednesday":'';
          days += document.getElementById('occur2_thu_check').checked ? ",Thursday":'';
          days += document.getElementById('occur2_fri_check').checked ? ",Friday":'';
          days += document.getElementById('occur2_sat_check').checked ? ",Saturday":'';
          days += document.getElementById('occur2_sun_check').checked ? ",Sunday":'';
          document.getElementById('patternDaysOfWeek').value = days.length>0?days.substring(1):days;
          break;
        case 'occur_3':
          document.getElementById('repetition').selectedIndex = 2;
          document.getElementById('patternType').value = "AbsoluteMonthly";
          document.getElementById('patternInterval').value = document.getElementById('occur3_month').value;
          document.getElementById('patternDayOfMonth').value = document.getElementById('occur3_day').value;
          break;
        case 'occur_4':
          document.getElementById('repetition').selectedIndex = 3;
          document.getElementById('patternType').value = "RelativeMonthly";
          document.getElementById('patternInterval').value = document.getElementById('occur4_month').value;
          document.getElementById('patternDaysOfWeek').value = document.getElementById('occur4_weekDay').value;
          document.getElementById('patternIndex').value = document.getElementById('occur4_weekNum').value;
          break;
        case 'occur_5':
          document.getElementById('repetition').selectedIndex = 4;
          document.getElementById('patternType').value = "AbsoluteYearly";
          document.getElementById('patternMonth').value = document.getElementById('occur5_month').value;
          document.getElementById('patternDayOfMonth').value = document.getElementById('occur5_day').value;
          break;
        case 'occur_6':
          document.getElementById('repetition').selectedIndex = 5;
          document.getElementById('patternType').value = "RelativeYearly";
          document.getElementById('patternMonth').value = document.getElementById('occur6_month').value;
          document.getElementById('patternDaysOfWeek').value = document.getElementById('occur6_weekDay').value;
          document.getElementById('patternIndex').value = document.getElementById('occur6_weekNum').value;
          break;
      }
      $('#myModal').modal('hide');
    }

    document.getElementById('repetition').onchange = function(e) {
      document.getElementById('rep_date').style.display = 'block';
      switch (e.target.value) {
        case 'rep_0':
          document.getElementById('rep_date').style.display = 'none';
          document.getElementById('pattern_type').value = "";
          document.getElementById('pattern_type').value = "";
          break;
        case 'rep_1':
          document.getElementById('patternType').value = "Daily";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = 0;
          document.getElementById('patternDayOfMonth').value = 0;
          document.getElementById('patternDaysOfWeek').value = "";
          break;
        case 'rep_2':
          document.getElementById('patternType').value = "Weekly";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = 0;
          document.getElementById('patternDayOfMonth').value = 0;
          document.getElementById('patternDaysOfWeek').value = "<?php echo date('l'); ?>";
          break;
        case 'rep_3':
          document.getElementById('patternType').value = "Weekly";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = 0;
          document.getElementById('patternDayOfMonth').value = 0;
          document.getElementById('patternDaysOfWeek').value = "Monday,Tuesday,Wednesday,Thursday,Friday";
          break;
        case 'rep_4':
          document.getElementById('patternType').value = "AbsoluteMonthly";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = 0;
          document.getElementById('patternDayOfMonth').value = "<?php echo date('d'); ?>";
          document.getElementById('patternDaysOfWeek').value = "";
          break;
        case 'rep_5':
          document.getElementById('patternType').value = "RelativeMonthly";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = 0;
          document.getElementById('patternDayOfMonth').value = 0;
          document.getElementById('patternDaysOfWeek').value = "<?php echo date('l'); ?>";
          document.getElementById('patternIndex').value = "<?php echo getWeeks(date('Y-m-d'), 'sunday'); ?>";
          break;
        case 'rep_6':
          document.getElementById('patternType').value = "Weekly";
          document.getElementById('patternInterval').value = 1;
          document.getElementById('patternMonth').value = "<?php echo date('l'); ?>";
          document.getElementById('patternDayOfMonth').value = 0;
          break;
        case 'rep_7':
          $('#myModal').modal('show');
          break;
      }
    }

    document.getElementById('rep_occur').onchange = function(e) {
      var arr = document.getElementsByClassName('occur');
      for (i = 0; i < arr.length; i ++) {
        arr[i].className = 'occur';
      }

      switch (e.target.value) {
        case 'occur_1':
          document.getElementById('occur_1').className = 'occur active';
          break;
        case 'occur_2':
          document.getElementById('occur_2').className = 'occur active';
          break;
        case 'occur_3':
          document.getElementById('occur_3').className = 'occur active';
          break;
        case 'occur_4':
          document.getElementById('occur_4').className = 'occur active';
          break;
        case 'occur_5':
          document.getElementById('occur_5').className = 'occur active';
          break;
        case 'occur_6':
          document.getElementById('occur_6').className = 'occur active';
          break;
      }
    }
  </script>
</html>
