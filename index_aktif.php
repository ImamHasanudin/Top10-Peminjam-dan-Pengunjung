<?php
/**
 * index_aktif.php - Laporan "10 Pengunjung Teraktif"
 * (jumlah Peminjaman + Kunjungan terbanyak)
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
$where_loan  = imh_build_date_where('loan_date', $dates['start_esc'], $dates['end_esc']);
$where_visit = imh_build_date_where('checkin_date', $dates['start_esc'], $dates['end_esc']);

$combined    = array(); 
$data        = array();
$query_error = '';

try {
    // --- Data peminjaman ---
    $sql_loan = "SELECT member_id, member_name, COUNT(*) AS jumlah
                 FROM loan_history
                 WHERE $where_loan
                   AND member_id IS NOT NULL AND member_id != ''
                 GROUP BY member_id, member_name";
    $q1 = $dbs->query($sql_loan);
    if ($q1 === false || $q1 === null) {
        throw new \RuntimeException('Query data peminjaman gagal dijalankan.');
    }
    while ($row = $q1->fetch_assoc()) {
        $key = $row['member_id'] . '|' . $row['member_name'];
        if (!isset($combined[$key])) {
            $combined[$key] = array(
                'member_id'       => $row['member_id'],
                'member_name'     => $row['member_name'],
                'total_pinjam'    => 0,
                'total_kunjungan' => 0,
            );
        }
        $combined[$key]['total_pinjam'] += (int) $row['jumlah'];
    }

    // --- Data kunjungan ---
    $sql_visit = "SELECT member_id, member_name, COUNT(*) AS jumlah
                  FROM visitor_count
                  WHERE $where_visit
                  GROUP BY member_id, member_name";
    $q2 = $dbs->query($sql_visit);
    if ($q2 === false || $q2 === null) {
        throw new \RuntimeException('Query data kunjungan gagal dijalankan.');
    }
    while ($row = $q2->fetch_assoc()) {
        $key = $row['member_id'] . '|' . $row['member_name'];
        if (!isset($combined[$key])) {
            $combined[$key] = array(
                'member_id'       => $row['member_id'],
                'member_name'     => $row['member_name'],
                'total_pinjam'    => 0,
                'total_kunjungan' => 0,
            );
        }
        $combined[$key]['total_kunjungan'] += (int) $row['jumlah'];
    }

    // Hitung total aktivitas, urutkan, ambil 10 teratas
    foreach ($combined as $key => $row) {
        $combined[$key]['jumlah'] = $row['total_pinjam'] + $row['total_kunjungan'];
    }
    usort($combined, function ($a, $b) {
        if ($a['jumlah'] === $b['jumlah']) {
            return strcmp((string) $a['member_name'], (string) $b['member_name']);
        }
        return $b['jumlah'] - $a['jumlah'];
    });
    $data = array_slice($combined, 0, 10);

} catch (\Throwable $e) {
    $query_error = $e->getMessage();
}

imh_render_style();
?>
<div class="menuBox imh-wrapper">
    <div class="menuBoxInner">
        <div class="per_title">
            <h2><?php echo __('10 Anggota Teraktif'); ?></h2>
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
                            <th style="text-align:center;"><?php echo __('Total Peminjaman'); ?></th>
                            <th style="text-align:center;"><?php echo __('Total Kunjungan'); ?></th>
                            <th style="text-align:center;"><?php echo __('Total Aktivitas'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="imh-rank"><?php echo imh_rank_badge($rank); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_id']) ? $row['member_id'] : '-'); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_name']) ? $row['member_name'] : __('(Tanpa Nama)')); ?></td>
                                <td class="imh-num"><?php echo (int) $row['total_pinjam']; ?></td>
                                <td class="imh-num"><?php echo (int) $row['total_kunjungan']; ?></td>
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
