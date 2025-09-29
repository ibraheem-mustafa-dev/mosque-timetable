<?php

/**
 * Plugin Name: MT Admin Hotfix (Timetable Unifier)
 * Description: Unifies Timetable admin controls, binds buttons, and wires AJAX for Generate/Save/Hijri/PDF.
 */

if (!defined('ABSPATH')) exit;

/* ---------- Enqueue + Config (Timetables screen only) ---------- */
add_action('admin_enqueue_scripts', function ($hook) {
    // Adjust slug if your page slug differs:
    if ($hook !== 'toplevel_page_mosque-timetables') return;

    wp_enqueue_script(
        'mt-admin-hotfix',
        plugins_url('mt-admin-hotfix.js', __FILE__),
        ['jquery'],
        '1.0.0',
        true
    );

    $post_id = get_the_ID(); // your timetable post id
    wp_localize_script('mt-admin-hotfix', 'MT_ADMIN', [
        'ajax'         => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('mt_admin'),
        'postId'       => (int) $post_id,
        'currentYear'  => (int) date('Y'),
        'currentMonth' => (int) date('n'),
    ]);
});

/* ---------- Shared helpers ---------- */
function mt_hotfix_check_nonce()
{
    check_ajax_referer('mt_admin');
}

function mt_hotfix_repo_get_month_rows($post_id, $year, $month)
{
    if (function_exists('get_field')) {
        $all = get_field('daily_timetable', $post_id) ?: [];
        return array_values(array_filter($all, function ($r) use ($year, $month) {
            if (empty($r['date'])) return false;
            $t = strtotime($r['date']);
            return (int)date('Y', $t) === $year && (int)date('n', $t) === $month;
        }));
    }
    $key = sprintf('mt_rows_%d_%02d', $year, $month);
    $json = get_post_meta($post_id, $key, true);
    return $json ? (json_decode($json, true) ?: []) : [];
}
function mt_hotfix_repo_save_month_rows($post_id, $year, $month, array $rows)
{
    if (function_exists('get_field') && function_exists('update_field')) {
        $all = get_field('daily_timetable', $post_id) ?: [];
        $all = array_values(array_filter($all, function ($r) use ($year, $month) {
            if (empty($r['date'])) return true;
            $t = strtotime($r['date']);
            return !((int)date('Y', $t) === $year && (int)date('n', $t) === $month);
        }));
        $all = array_merge($all, $rows);
        return (bool) update_field('daily_timetable', $all, $post_id);
    }
    $key = sprintf('mt_rows_%d_%02d', $year, $month);
    return (bool) update_post_meta($post_id, $key, wp_json_encode($rows));
}

function mt_hotfix_generate_month_rows($year, $month)
{
    $tz = wp_timezone();
    $dt = DateTime::createFromFormat('Y-n-j', "$year-$month-1", $tz);
    if (!$dt) return [];
    $days = (int)$dt->format('t');
    $rows = [];
    for ($d = 1; $d <= $days; $d++) {
        $cur = DateTime::createFromFormat('Y-n-j', "$year-$month-$d", $tz);
        $rows[] = [
            'date'          => $cur->format('Y-m-d'),
            'day_name'      => $cur->format('l'),
            'hijri'         => '',
            'fajr'          => '--:--',
            'fajr_jamat' => '--:--',
            'sunrise'       => '--:--',
            'zuhr'          => '--:--',
            'zuhr_jamat' => '--:--',
            'jumah1'        => '--:--',
            'jumah2' => '--:--',
            'asr'           => '--:--',
            'asr_jamat' => '--:--',
            'maghrib'       => '--:--',
            'maghrib_jamat' => '--:--',
            'isha'          => '--:--',
            'isha_jamat' => '--:--',
        ];
    }
    return $rows;
}
// plug in your real hijri here later:
function mt_hotfix_hijri_from_gregorian($ymd)
{
    return '';
}
function mt_hotfix_fill_hijri(array $rows)
{
    foreach ($rows as &$r) {
        if (!empty($r['date'])) $r['hijri'] = mt_hotfix_hijri_from_gregorian($r['date']);
    }
    return $rows;
}

/* ---------- AJAX endpoints ---------- */
add_action('wp_ajax_mt_generate_month', function () {
    mt_hotfix_check_nonce();
    $post_id = (int)$_POST['post_id'];
    $y = (int)$_POST['year'];
    $m = (int)$_POST['month'];
    $rows = mt_hotfix_fill_hijri(mt_hotfix_generate_month_rows($y, $m));
    $ok   = mt_hotfix_repo_save_month_rows($post_id, $y, $m, $rows);
    if (!$ok) wp_send_json_error(['message' => 'Save failed']);
    wp_send_json_success(['count' => count($rows)]);
});

add_action('wp_ajax_mt_generate_year', function () {
    mt_hotfix_check_nonce();
    $post_id = (int)$_POST['post_id'];
    $y = (int)$_POST['year'];
    $ok = true;
    $total = 0;
    for ($m = 1; $m <= 12; $m++) {
        $rows = mt_hotfix_fill_hijri(mt_hotfix_generate_month_rows($y, $m));
        $ok = $ok && mt_hotfix_repo_save_month_rows($post_id, $y, $m, $rows);
        $total += count($rows);
    }
    if (!$ok) wp_send_json_error(['message' => 'Year save failed']);
    wp_send_json_success(['count' => $total]);
});

add_action('wp_ajax_mt_recalc_hijri', function () {
    mt_hotfix_check_nonce();
    $post_id = (int)$_POST['post_id'];
    $y = (int)$_POST['year'];
    $m = (int)$_POST['month'];
    $rows = mt_hotfix_repo_get_month_rows($post_id, $y, $m);
    $rows = mt_hotfix_fill_hijri($rows);
    $ok   = mt_hotfix_repo_save_month_rows($post_id, $y, $m, $rows);
    if (!$ok) wp_send_json_error(['message' => 'Hijri save failed']);
    wp_send_json_success(['count' => count($rows)]);
});

add_action('wp_ajax_mt_get_month', function () {
    mt_hotfix_check_nonce();
    $post_id = (int)$_POST['post_id'];
    $y = (int)$_POST['year'];
    $m = (int)$_POST['month'];
    $rows = mt_hotfix_repo_get_month_rows($post_id, $y, $m);
    ob_start();
    mt_hotfix_render_month_table($rows, $y, $m);
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
});

add_action('wp_ajax_mt_upload_month_pdf', function () {
    mt_hotfix_check_nonce();
    if (empty($_FILES['file']['name'])) wp_send_json_error(['message' => 'No file']);
    $id = media_handle_upload('file', 0);
    if (is_wp_error($id)) wp_send_json_error(['message' => $id->get_error_message()]);
    $post_id = (int)$_POST['post_id'];
    $y = (int)$_POST['year'];
    $m = (int)$_POST['month'];
    $key = sprintf('mt_pdf_%d_%02d', $y, $m);
    update_post_meta($post_id, $key, (int)$id);
    wp_send_json_success(['attachment_id' => (int)$id]);
});

/* ---------- Minimal server-rendered table for mt_get_month ---------- */
function mt_hotfix_render_month_table(array $rows, int $year, int $month)
{ ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Day Name</th>
                <th>Hijri</th>
                <th>Fajr</th>
                <th>Fajr Jamat</th>
                <th>Sunrise</th>
                <th>Zuhr</th>
                <th>Zuhr Jamat</th>
                <th>Asr</th>
                <th>Asr Jamat</th>
                <th>Maghrib</th>
                <th>Maghrib Jamat</th>
                <th>Isha</th>
                <th>Isha Jamat</th>
                <th>Jummah 1</th>
                <th>Jummah 2</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="17">No rows yet for <?php echo esc_html(date('F', mktime(0, 0, 0, $month, 1)) . " $year"); ?>.</td>
                </tr>
                <?php else: foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?php echo (int)($i + 1); ?></td>
                        <td><?php echo esc_html($r['date'] ?? ''); ?></td>
                        <td><?php echo esc_html($r['day_name'] ?? ''); ?></td>
                        <td><?php echo esc_html($r['hijri'] ?? ''); ?></td>
                        <td><?php echo esc_html($r['fajr'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['fajr_jamat'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['sunrise'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['zuhr'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['zuhr_jamat'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['asr'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['asr_jamat'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['maghrib'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['maghrib_jamat'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['isha'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['isha_jamat'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['jumah1'] ?? '--:--'); ?></td>
                        <td><?php echo esc_html($r['jumah2'] ?? '--:--'); ?></td>
                    </tr>
            <?php endforeach;
            endif; ?>
        </tbody>
    </table>
<?php }
