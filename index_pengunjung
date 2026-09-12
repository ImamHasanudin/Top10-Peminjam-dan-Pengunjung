<?php
/**
 * index_pengunjung.php - Laporan "10 Pengunjung Tersering"
 * Berdiri sendiri sebagai satu menu Laporan di SLiMS (lihat imh_top_aktivitas.plugin.php)
 */

require __DIR__ . '/imh_common.php';

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';

$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">' . __('You don\'t have enough privileges to view this section!') . '</div>');
}

require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';

$dates = imh_get_filter_dates();
$where = imh_build_date_where('vc.checkin_date', $dates['start_esc'], $dates['end_esc']);

$data        = array();
$query_error = '';

try {
    $sql = "SELECT vc.member_id AS member_id,
                   vc.member_name AS member_name,
                   COUNT(*) AS jumlah
            FROM visitor_count AS vc
            WHERE $where
            GROUP BY vc.member_id, vc.member_name
            ORDER BY jumlah DESC, vc.member_name ASC
            LIMIT 10";
    $q = $dbs->query($sql);
    while ($row = $q->fetch_assoc()) {
        $data[] = $row;
    }
} catch (\Throwable $e) {
    $query_error = $e->getMessage();
}

imh_render_style();
?>
<div class="menuBox imh-wrapper">
    <div class="menuBoxInner">
        <div class="per_title">
            <h2><?php echo __('10 Pengunjung Tersering'); ?></h2>
        </div>

        <div class="sub_section p-3">
        <div id="imhAppRoot">

            <?php imh_render_filter_bar($dates); ?>

            <?php if ($query_error !== ''): ?>
                <div class="errorBox">
                    <?php echo __('Terjadi kesalahan saat mengambil data'); ?>: <?php echo htmlspecialchars($query_error); ?>
                </div>
            <?php elseif (empty($data)): ?>
                <div class="imh-empty">
                    <?php echo __('Tidak ada data ditemukan untuk periode yang dipilih.'); ?>
                </div>
            <?php else: ?>
                <table class="imh-table">
                    <thead>
                        <tr>
                            <th><?php echo __('Peringkat'); ?></th>
                            <th><?php echo __('ID Anggota'); ?></th>
                            <th><?php echo __('Nama'); ?></th>
                            <th style="text-align:center;"><?php echo __('Jumlah Kunjungan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="imh-rank"><?php echo imh_rank_badge($rank); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_id']) ? $row['member_id'] : '-'); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_name']) ? $row['member_name'] : __('(Tanpa Nama)')); ?></td>
                                <td class="imh-num"><?php echo (int) $row['jumlah']; ?></td>
                            </tr>
                            <?php $rank++; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
        </div>
    </div>
</div>
<?php imh_render_script(); ?>
