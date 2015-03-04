
<?

// $report_files = array('2012-11-08','2012-11-09','2012-11-10','2012-11-11','2012-11-12','2012-11-13','2012-11-14','2012-11-15','2012-11-16','2012-11-17','2012-11-18','2012-11-19','2012-11-20','2012-11-21','2012-11-22','2012-11-23','2012-11-24','2012-11-25','2012-11-26','2012-11-27','2012-11-28','2012-11-29','2012-11-30','2012-12-01','2012-12-02','2012-12-03','2012-12-04','2012-12-05','2012-12-06','2012-12-07','2012-12-08','2012-12-09','2012-12-10','2012-12-11','2012-12-12','2012-12-13','2012-12-14','2012-12-15','2012-12-16','2012-12-17','2012-12-18','2012-12-19','2012-12-20','2012-12-21','2012-12-22','2012-12-23','2012-12-24','2012-12-25','2012-12-26','2012-12-27','2012-12-28','2012-12-29','2012-12-30','2012-12-31','2013-01-01','2013-01-02','2013-01-03','2013-01-04','2013-01-05','2013-01-06','2013-01-07','2013-01-08','2013-01-09','2013-01-10','2013-01-11','2013-01-12','2013-01-13','2013-01-14','2013-01-15','2013-01-16','2013-01-17','2013-01-18','2013-01-19','2013-01-20','2013-01-21','2013-01-22','2013-01-23','2013-01-24','2013-01-25','2013-01-26','2013-01-27','2013-01-28','2013-01-29','2013-01-30','2013-01-31','2013-02-01','2013-02-02','2013-02-03','2013-02-04','2013-02-05','2013-02-06','2013-02-07','2013-02-08','2013-02-09','2013-02-10','2013-02-11','2013-02-12','2013-02-13','2013-02-14','2013-02-15','2013-02-16','2013-02-17','2013-02-18','2013-02-19','2013-02-20','2013-02-21','2013-02-22','2013-02-23','2013-02-24','2013-02-25','2013-02-26','2013-02-27','2013-02-28','2013-03-01','2013-03-02','2013-03-03','2013-03-04','2013-03-05','2013-03-06','2013-03-07','2013-03-08','2013-03-09','2013-03-10','2013-03-11','2013-03-12','2013-03-13');

// Get Daily report files
$report_files = glob('./archives/airport_guides/*.csv');

// Create new array for sorted data
$reports = array();

// Start sorting
foreach ($report_files as $file) {
    
    // Remove relative .
    $file_path = ltrim($file, '.');

    // Get file basename
    $filename = basename($file, '.csv');

    // Get report date from file name
    $date = strtotime($filename);

    // Get Month
    $f_month = date('n', $date);

    // Get Year
    $f_year = date('Y', $date);

    // Get Day
    $f_day = date('j', $date);

    $reports[$f_year][$f_month][$f_day] = $file_path;
}

// Sort array years
krsort($reports);

// Sort months in year
foreach ($reports as $year => $months) {
    krsort($reports[$year]);

    // Sort days in month
    foreach ($reports[$year] as $month => $days) {
        krsort($reports[$year][$month]);
    }
}

?>
<div class="reports">
<h3>Airport Guides Reports</h3>
<hr/>

<? foreach ($reports as $year => $months) : ?>

    <div class="years panel panel-default">

    <!-- Year -->
    <div class="panel-heading"><h4><?= $year ?></h4></div>
    <div class="panel-body">
    <? foreach ($months as $month => $days) : ?>

        <!-- Month -->
        <div class="month">
            <h5><?= date('F', strtotime("2000-{$month}-01")) ?></h5>

                <!-- Days -->
                <? foreach ($days as $day => $report) : ?>

                    <a target="_blank" href="<?= $report ?>" type="button" class="btn btn-primary"><?= date('jS', strtotime("2000-01-{$day}")) ?>
                    </a>

                <? endforeach; ?>
        </div>

    <? endforeach; ?>
    </div>

    </div>
<? endforeach; ?>
</div>
