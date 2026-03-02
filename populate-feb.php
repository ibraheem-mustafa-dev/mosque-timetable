<?php
/**
 * Populates February 2026 prayer times for mosquewebdesign.com demo.
 * Run via: wp --path=/path/to/wp eval-file populate-feb.php
 */

$year  = 2026;
$month = 2;

$days = array(
    1  => array('fajr'=>'06:11','fajr_jamat'=>'06:31','sunrise'=>'07:49','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:27','asr_jamat'=>'14:42','maghrib'=>'16:54','isha'=>'18:23','isha_jamat'=>'18:53'),
    2  => array('fajr'=>'06:10','fajr_jamat'=>'06:30','sunrise'=>'07:48','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:28','asr_jamat'=>'14:43','maghrib'=>'16:56','isha'=>'18:25','isha_jamat'=>'18:55'),
    3  => array('fajr'=>'06:08','fajr_jamat'=>'06:28','sunrise'=>'07:46','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:30','asr_jamat'=>'14:45','maghrib'=>'16:58','isha'=>'18:27','isha_jamat'=>'18:57'),
    4  => array('fajr'=>'06:06','fajr_jamat'=>'06:26','sunrise'=>'07:44','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:32','asr_jamat'=>'14:47','maghrib'=>'17:00','isha'=>'18:28','isha_jamat'=>'18:58'),
    5  => array('fajr'=>'06:05','fajr_jamat'=>'06:25','sunrise'=>'07:43','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:33','asr_jamat'=>'14:48','maghrib'=>'17:01','isha'=>'18:29','isha_jamat'=>'18:59'),
    6  => array('fajr'=>'06:03','fajr_jamat'=>'06:23','sunrise'=>'07:41','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:35','asr_jamat'=>'14:50','maghrib'=>'17:03','isha'=>'18:31','isha_jamat'=>'19:01'),
    7  => array('fajr'=>'06:01','fajr_jamat'=>'06:21','sunrise'=>'07:39','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:36','asr_jamat'=>'14:51','maghrib'=>'17:05','isha'=>'18:33','isha_jamat'=>'19:03'),
    8  => array('fajr'=>'05:59','fajr_jamat'=>'06:19','sunrise'=>'07:37','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:38','asr_jamat'=>'14:53','maghrib'=>'17:07','isha'=>'18:34','isha_jamat'=>'19:04'),
    9  => array('fajr'=>'05:57','fajr_jamat'=>'06:17','sunrise'=>'07:35','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:39','asr_jamat'=>'14:54','maghrib'=>'17:09','isha'=>'18:36','isha_jamat'=>'19:06'),
    10 => array('fajr'=>'05:57','fajr_jamat'=>'06:17','sunrise'=>'07:34','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:41','asr_jamat'=>'14:56','maghrib'=>'17:11','isha'=>'18:38','isha_jamat'=>'19:08'),
    11 => array('fajr'=>'05:55','fajr_jamat'=>'06:15','sunrise'=>'07:32','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:42','asr_jamat'=>'14:57','maghrib'=>'17:13','isha'=>'18:40','isha_jamat'=>'19:10'),
    12 => array('fajr'=>'05:53','fajr_jamat'=>'06:13','sunrise'=>'07:30','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:43','asr_jamat'=>'14:58','maghrib'=>'17:15','isha'=>'18:41','isha_jamat'=>'19:11'),
    13 => array('fajr'=>'05:51','fajr_jamat'=>'06:11','sunrise'=>'07:28','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:45','asr_jamat'=>'15:00','maghrib'=>'17:17','isha'=>'18:43','isha_jamat'=>'19:13'),
    14 => array('fajr'=>'05:49','fajr_jamat'=>'06:09','sunrise'=>'07:26','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:46','asr_jamat'=>'15:01','maghrib'=>'17:19','isha'=>'18:45','isha_jamat'=>'19:15'),
    15 => array('fajr'=>'05:47','fajr_jamat'=>'06:07','sunrise'=>'07:24','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:48','asr_jamat'=>'15:03','maghrib'=>'17:20','isha'=>'18:46','isha_jamat'=>'19:16'),
    16 => array('fajr'=>'05:45','fajr_jamat'=>'06:05','sunrise'=>'07:22','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:49','asr_jamat'=>'15:04','maghrib'=>'17:22','isha'=>'18:47','isha_jamat'=>'19:17'),
    17 => array('fajr'=>'05:43','fajr_jamat'=>'06:03','sunrise'=>'07:20','zuhr'=>'12:22','zuhr_jamat'=>'12:52','asr'=>'14:51','asr_jamat'=>'15:06','maghrib'=>'17:24','isha'=>'18:49','isha_jamat'=>'19:19'),
    18 => array('fajr'=>'05:41','fajr_jamat'=>'06:01','sunrise'=>'07:18','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:52','asr_jamat'=>'15:07','maghrib'=>'17:26','isha'=>'18:51','isha_jamat'=>'19:21'),
    19 => array('fajr'=>'05:39','fajr_jamat'=>'05:59','sunrise'=>'07:16','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:54','asr_jamat'=>'15:09','maghrib'=>'17:28','isha'=>'18:53','isha_jamat'=>'19:23'),
    20 => array('fajr'=>'05:38','fajr_jamat'=>'05:58','sunrise'=>'07:14','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:55','asr_jamat'=>'15:10','maghrib'=>'17:30','isha'=>'18:54','isha_jamat'=>'19:24'),
    21 => array('fajr'=>'05:36','fajr_jamat'=>'05:56','sunrise'=>'07:12','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:57','asr_jamat'=>'15:12','maghrib'=>'17:32','isha'=>'18:56','isha_jamat'=>'19:26'),
    22 => array('fajr'=>'05:33','fajr_jamat'=>'05:53','sunrise'=>'07:09','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:58','asr_jamat'=>'15:13','maghrib'=>'17:34','isha'=>'18:58','isha_jamat'=>'19:28'),
    23 => array('fajr'=>'05:31','fajr_jamat'=>'05:51','sunrise'=>'07:07','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'14:59','asr_jamat'=>'15:14','maghrib'=>'17:35','isha'=>'18:59','isha_jamat'=>'19:29'),
    24 => array('fajr'=>'05:29','fajr_jamat'=>'05:49','sunrise'=>'07:05','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'15:01','asr_jamat'=>'15:16','maghrib'=>'17:37','isha'=>'19:00','isha_jamat'=>'19:30'),
    25 => array('fajr'=>'05:27','fajr_jamat'=>'05:47','sunrise'=>'07:03','zuhr'=>'12:21','zuhr_jamat'=>'12:51','asr'=>'15:02','asr_jamat'=>'15:17','maghrib'=>'17:39','isha'=>'19:02','isha_jamat'=>'19:32'),
    26 => array('fajr'=>'05:25','fajr_jamat'=>'05:45','sunrise'=>'07:01','zuhr'=>'12:20','zuhr_jamat'=>'12:50','asr'=>'15:03','asr_jamat'=>'15:18','maghrib'=>'17:41','isha'=>'19:04','isha_jamat'=>'19:34'),
    27 => array('fajr'=>'05:23','fajr_jamat'=>'05:43','sunrise'=>'06:59','zuhr'=>'12:20','zuhr_jamat'=>'12:50','asr'=>'15:05','asr_jamat'=>'15:20','maghrib'=>'17:43','isha'=>'19:06','isha_jamat'=>'19:36'),
    28 => array('fajr'=>'05:20','fajr_jamat'=>'05:40','sunrise'=>'06:56','zuhr'=>'12:20','zuhr_jamat'=>'12:50','asr'=>'15:06','asr_jamat'=>'15:21','maghrib'=>'17:45','isha'=>'19:07','isha_jamat'=>'19:37'),
);

// Build the data structure the plugin expects
$month_data = array( 'days' => array() );
foreach ( $days as $day_num => $times ) {
    $date_str = sprintf( '%04d-%02d-%02d', $year, $month, $day_num );
    $month_data['days'][] = array_merge(
        array(
            'day'       => $day_num,
            'date_full' => $date_str,
        ),
        $times
    );
}

// Use the plugin's helper function to save
if ( function_exists( 'mt_save_month_data' ) ) {
    $result = mt_save_month_data( $year, $month, $month_data );
    echo $result ? "Saved via mt_save_month_data\n" : "Save failed\n";
} else {
    // Direct DB fallback
    $all = get_option( 'mosque_timetable_rows', array() );
    $all[ $year ][ $month ] = $month_data;
    update_option( 'mosque_timetable_rows', $all, false );
    echo "Saved directly to wp_options\n";
}
