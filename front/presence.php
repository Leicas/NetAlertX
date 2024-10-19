<!--
#---------------------------------------------------------------------------------#
#  NetAlertX                                                                       #
#  Open Source Network Guard / WIFI & LAN intrusion detector                      #  
#                                                                                 #
#  presence.php - Front module. Device Presence calendar page                     #
#---------------------------------------------------------------------------------#
#    Puche      2021        pi.alert.application@gmail.com   GNU GPLv3            #
#    jokob-sk   2022        jokob.sk@gmail.com               GNU GPLv3            #
#    leiweibau  2022        https://github.com/leiweibau     GNU GPLv3            #
#    cvc90      2023        https://github.com/cvc90         GNU GPLv3            #
#---------------------------------------------------------------------------------#
-->

<?php
  require 'php/templates/header.php';
?>

<!-- Page ------------------------------------------------------------------ -->
  <div class="content-wrapper">

<!-- Content header--------------------------------------------------------- -->
    <section class="content-header">
      <h1 id="pageTitle">
        <i class="fa fa-calendar"></i>
        <?= lang('Presence_Title');?>
      </h1>
    </section>

<!-- Main content ---------------------------------------------------------- -->
    <section class="content">

<!-- top small box 1 ------------------------------------------------------- -->
      <div class="row">

        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('all');">
          <div class="small-box bg-aqua">
            <div class="inner"><h3 id="devicesAll"> -- </h3>
                <p class="infobox_label"><?= lang('Presence_Shortcut_AllDevices');?></p>
            </div>
            <div class="icon"><i class="fa fa-laptop text-aqua-40"></i></div>
          </div>
          </a>
        </div>

<!-- top small box 2 ------------------------------------------------------- -->
        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('connected');">
            <div class="small-box bg-green">
              <div class="inner"> <h3 id="devicesConnected"> -- </h3> 
                  <p class="infobox_label"><?= lang('Presence_Shortcut_Connected');?></p>
              </div>
              <div class="icon"> <i class="fa fa-plug text-green-40"></i> </div>
            </div>
          </a>
        </div>

<!-- top small box 3 ------------------------------------------------------- -->
        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('favorites');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="devicesFavorites"> -- </h3>
                <p class="infobox_label"><?= lang('Presence_Shortcut_Favorites');?></p>
              </div>
              <div class="icon"> <i class="fa fa-star text-yellow-40"></i> </div>
            </div>
          </a>
        </div>

<!-- top small box 4 ------------------------------------------------------- -->
        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('new');">
            <div  class="small-box bg-yellow">
              <div class="inner"> <h3 id="devicesNew"> -- </h3>
                <p class="infobox_label"><?= lang('Presence_Shortcut_NewDevices');?></p>
              </div>
              <div class="icon"> <i class="ion ion-plus-round text-yellow-40"></i> </div>
            </div>
          </a>
        </div>

<!-- top small box 5 ------------------------------------------------------- -->
        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('down');">
            <div  class="small-box bg-red">
              <div class="inner"> <h3 id="devicesDown"> -- </h3>
                <p class="infobox_label"><?= lang('Presence_Shortcut_DownAlerts');?></p>
              </div>
              <div class="icon"> <i class="fa fa-warning text-red-40"></i> </div>
            </div>
          </a>
        </div>

<!-- top small box 6 ------------------------------------------------------- -->
        <div class="col-lg-2 col-sm-4 col-xs-6">
          <a href="#" onclick="javascript: getDevicesPresence('archived');">
            <div  class="small-box bg-gray top_small_box_gray_text">
              <div class="inner"> <h3 id="devicesHidden"> -- </h3>
                <p class="infobox_label"><?= lang('Presence_Shortcut_Archived');?></p>
              </div>
              <div class="icon"> <i class="fa fa-eye-slash text-gray-40"></i> </div>
            </div>
          </a>
        </div>

      </div>

<!-- Activity Chart ------------------------------------------------------- -->
      <div class="row">
          <div class="col-md-12">
          <div class="box" id="clients">
              <div class="box-header with-border">
                <h3 class="box-title"><?= lang('Device_Shortcut_OnlineChart');?></h3>
              </div>
              <div class="box-body">
                <div class="chart">
                  <script src="lib/AdminLTE/bower_components/chart.js/Chart.js"></script>
                  <!-- <canvas id="clientsChart" width="800" height="140" class="extratooltipcanvas no-user-select"></canvas> -->
                  <canvas id="OnlineChart" style="width:100%; height: 150px;  margin-bottom: 15px;"></canvas>
                </div>
              </div>
              <!-- /.box-body -->
            </div>
          </div>
      </div>

      <script src="js/graph_online_history.js"></script>
      <script>
        $.get('api/table_online_history.json?nocache=' + Date.now(), function(res) {
              // Extracting data from the JSON response
              var timeStamps = [];
              var onlineCounts = [];
              var downCounts = [];
              var offlineCounts = [];
              var archivedCounts = [];

              res.data.forEach(function(entry) {
                  var dateObj = new Date(entry.Scan_Date);
                  var formattedTime = dateObj.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: false});

                  timeStamps.push(formattedTime);
                  onlineCounts.push(entry.Online_Devices);
                  downCounts.push(entry.Down_Devices);
                  offlineCounts.push(entry.Offline_Devices);
                  archivedCounts.push(entry.Archived_Devices);
              });

              // Call your presenceOverTime function after data is ready
              presenceOverTime(
                  timeStamps,
                  onlineCounts,
                  offlineCounts,
                  archivedCounts,
                  downCounts
              );
          }).fail(function() {
              // Handle any errors in fetching the data
              console.error('Error fetching online history data.');
          });
      </script>
  
      <!-- /.row -->

<!-- Calendar -------------------------------------------------------------- -->
      <div class="row">
        <div class="col-lg-12 col-sm-12 col-xs-12">
          <div id="tableDevicesBox" class="box" style="min-height: 500px">

            <!-- box-header -->
            <div class="box-header">
              <h3 id="tableDevicesTitle" class="box-title text-gray">Devices</h3>
            </div>

            <!-- box-body -->
            <div class="box-body table-responsive">              

              <!-- Calendar -->
              <div id="calendar"></div>
            </div>

          </div>
          <!-- /.box -->
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

<!-- ----------------------------------------------------------------------- -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->


<!-- ----------------------------------------------------------------------- -->
<?php
  require 'php/templates/footer.php';
?>


<!-- ----------------------------------------------------------------------- -->
<!-- fullCalendar -->
  <link rel="stylesheet" href="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.min.css">
  <link rel="stylesheet" href="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.print.min.css" media="print">
  <script src="lib/AdminLTE/bower_components/moment/moment.js"></script>
  <script src="lib/AdminLTE/bower_components/fullcalendar/dist/fullcalendar.min.js"></script>
  <script src="lib/AdminLTE/bower_components/fullcalendar/dist/locale-all.js"></script>

<!-- fullCalendar Scheduler -->
  <link href="lib/fullcalendar-scheduler/scheduler.min.css" rel="stylesheet">
  <script src="lib/fullcalendar-scheduler/scheduler.min.js"></script>




<!-- Dark-Mode Patch -->
<?php
switch ($UI_THEME) {
  case "Dark":
    echo '<link rel="stylesheet" href="css/dark-patch-cal.css">';
    break;
  case "System":
    echo '<link rel="stylesheet" href="css/system-dark-patch-cal.css">';
    break;

}
?>

<!-- page script ----------------------------------------------------------- -->
<script>

  var deviceStatus = 'all';

  // Read parameters & Initialize components
  main();


// -----------------------------------------------------------------------------
function main () {
  // Initialize components
  $(function () {
    initializeCalendar();
    getDevicesTotals();
    getDevicesPresence(deviceStatus);
  });

  // Force re-render calendar on tab change (bugfix for render error at left panel)
  $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (nav) {
    if ($(nav.target).attr('href') == '#panPresence') {
      $('#calendar').fullCalendar('rerenderEvents');
    }
  });
}


// -----------------------------------------------------------------------------
function initializeCalendar () {
  $('#calendar').fullCalendar({
    header: {
      left            : 'prev,next today',
      center          : 'title',
      right           : 'timelineYear,timelineMonth,timelineWeek,timelineDay'
    },
    defaultView       : 'timelineWeek',
    height            : 'auto',
    firstDay          : 1,
    allDaySlot        : false,
    timeFormat        : 'H:mm', 

    resourceLabelText : '<?= lang('Presence_CallHead_Devices');?>',
    resourceAreaWidth : '160px',
    slotWidth         : '1px',

    resourceOrder     : '-favorite,title',
    locale            : '<?= lang('Presence_CalHead_lang');?>',

    //schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
    schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',

    views: {
      timelineYear: {
        type              : 'timeline',
        duration          : { year: 1 },
        buttonText        : '<?= lang('Presence_CalHead_year');?>',
        slotLabelFormat   : 'MMM',
        // Hack to show partial day events not as fullday events
        slotDuration      : {minutes: 44641}
      },

      timelineQuarter: {
        type              : 'timeline',
        duration          : { month: 3 },
        buttonText        : '<?= lang('Presence_CalHead_quarter');?>',
        slotLabelFormat   : 'MMM',
        // Hack to show partial day events not as fullday events
        slotDuration      : {minutes: 44641}
      },

      timelineMonth: {
        type              : 'timeline',
        duration          : { month: 1 },
        buttonText        : '<?= lang('Presence_CalHead_month');?>',
        slotLabelFormat   : 'D',
        // Hack to show partial day events not as fullday events
        slotDuration      : '24:00:01'
      },

      timelineWeek: {
        type              : 'timeline',
        duration          : { week: 1 },
        buttonText        : '<?= lang('Presence_CalHead_week');?>',
        slotLabelFormat   : 'D',
        slotDuration      : '24:00:01'
      },
      timelineDay: {
        type              : 'timeline',
        duration          : { day: 1 },
        buttonText        : '<?= lang('Presence_CalHead_day');?>',
        slotLabelFormat   : 'H',
        slotDuration      : '00:30:00'
      }
    },
     
    // Needed due hack partial day events 23:59:59
    dayRender: function (date, cell) {
      if ($('#calendar').fullCalendar('getView').name == 'timelineYear') {
        cell.removeClass('fc-sat'); 
        cell.removeClass('fc-sun'); 
        return;
      }; 

      if (date.day() == 0) {
        cell.addClass('fc-sun'); };
                        
      if (date.day() == 6) {
        cell.addClass('fc-sat'); };

      if (date.format('YYYY-MM-DD') == moment().format('YYYY-MM-DD')) {
          cell.addClass ('fc-today'); };
          
      if ($('#calendar').fullCalendar('getView').name == 'timelineDay') {
        cell.removeClass('fc-sat');
        cell.removeClass('fc-sun');
        cell.removeClass('fc-today');
        if (date.format('YYYY-MM-DD HH') == moment().format('YYYY-MM-DD HH')) {
          cell.addClass('fc-today');
        }
      };
    },
    
    resourceRender: function (resourceObj, labelTds, bodyTds) {
      labelTds.find('span.fc-cell-text').html (
      '<b><a href="deviceDetails.php?mac='+ resourceObj.id+ '" class="">'+ resourceObj.title +'</a></b>');

      // Resize heihgt
      // $(".fc-content table tbody tr .fc-widget-content div").addClass('fc-resized-row');
    },
 
    eventRender: function (event, element, view) {
      $(element).tooltip({container: 'body', placement: 'bottom', title: event.tooltip});
      // element.attr ('title', event.tooltip);  // Alternative tooltip
    },

    loading: function( isLoading, view ) {
        if (isLoading) {
          showSpinner();
        } else {
          hideSpinner();
        }
    }

  })
}


// -----------------------------------------------------------------------------
function getDevicesTotals () {
  // stop timer
  stopTimerRefreshData();

  // get totals and put in boxes
  $.get('php/server/devices.php?action=getDevicesTotals', function(data) {
    var totalsDevices = JSON.parse(data);

    $('#devicesAll').html        (totalsDevices[0].toLocaleString());
    $('#devicesConnected').html  (totalsDevices[1].toLocaleString());
    $('#devicesFavorites').html  (totalsDevices[2].toLocaleString());
    $('#devicesNew').html        (totalsDevices[3].toLocaleString());
    $('#devicesDown').html       (totalsDevices[4].toLocaleString());
    $('#devicesHidden').html     (totalsDevices[5].toLocaleString());

    // Timer for refresh data
    newTimerRefreshData (getDevicesTotals);
  } );
}


// -----------------------------------------------------------------------------
function getDevicesPresence (status) {
  // Save status selected
  deviceStatus = status;

  // Defini color & title for the status selected
  switch (deviceStatus) {
    case 'all':        tableTitle = '<?= lang('Presence_Shortcut_AllDevices');?>';    color = 'aqua';    break;
    case 'connected':  tableTitle = '<?= lang('Presence_Shortcut_Connected');?>';     color = 'green';   break;
    case 'favorites':  tableTitle = '<?= lang('Presence_Shortcut_Favorites');?>';     color = 'yellow';  break;
    case 'new':        tableTitle = '<?= lang('Presence_Shortcut_NewDevices');?>';    color = 'yellow';  break;
    case 'down':       tableTitle = '<?= lang('Presence_Shortcut_DownAlerts');?>';    color = 'red';     break;
    case 'archived':   tableTitle = '<?= lang('Presence_Shortcut_Archived');?>';      color = 'gray';    break;
    default:           tableTitle = '<?= lang('Presence_Shortcut_Devices');?>';       color = 'gray';    break;
  } 

  period = "7 days"

  // Calculate startDate and endDate based on the period
  let startDate = "";
  let endDate = new Date().toISOString().slice(0, 10);  // Today's date in ISO format (YYYY-MM-DD)

  // Calculate startDate based on period
  switch (period) {
    case "7 days":
      startDate = new Date();
      startDate.setDate(startDate.getDate() - 7);  // Subtract 7 days
      startDate = startDate.toISOString().slice(0, 10);  // Convert to ISO format
      break;
    case "1 month":
      startDate = new Date();
      startDate.setMonth(startDate.getMonth() - 1);  // Subtract 1 month
      startDate = startDate.toISOString().slice(0, 10);  // Convert to ISO format
      break;
    case "1 year":
      startDate = new Date();
      startDate.setFullYear(startDate.getFullYear() - 1);  // Subtract 1 year
      startDate = startDate.toISOString().slice(0, 10);  // Convert to ISO format
      break;
    default:
      console.error("Invalid period selected");
  }

  // Set title and color
  $('#tableDevicesTitle')[0].className = 'box-title text-'+ color;
  $('#tableDevicesBox')[0].className = 'box box-'+ color;
  $('#tableDevicesTitle').html (tableTitle);

  // Define new datasource URL and reload
  $('#calendar').fullCalendar ('option', 'resources', 'php/server/devices.php?action=getDevicesListCalendar&status='+ deviceStatus);
  $('#calendar').fullCalendar ('refetchResources');

  $('#calendar').fullCalendar('removeEventSources');
  $('#calendar').fullCalendar('addEventSource', { url: `php/server/events.php?period=${period}&start=${startDate}&end=${endDate}&action=getEventsCalendar` });
};

</script>
