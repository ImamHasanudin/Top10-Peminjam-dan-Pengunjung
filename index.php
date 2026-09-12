<?php
/**
 * ==========================================================================
 * Top Peminjam & Pengunjung 
 * --------------------------------------------------------------------------
 * Author  : Imam Hasanudin
 * Compat  : SLiMS 9 (Bulian) ke atas, PHP 7.4 & PHP 8.0+
 *
 * berjalan di PHP 7.4 maupun 8.0+:

 */

// Set timezone eksplisit (WIB)
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

// ---------------------------------------------------------------------
// 1. Keamanan & Session (mengikuti mekanisme inti SLiMS)
// ---------------------------------------------------------------------
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-reporting');

require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';

// Cek hak akses ke modul reporting
$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">' . __('You don\'t have enough privileges to view this section!') . '</div>');
}

require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';

// ---------------------------------------------------------------------
// 2. Helper kecil (fungsi biasa, aman untuk PHP 7.4 & 8.0+)
// ---------------------------------------------------------------------


function imh_get_param($key, $default = '')
{
    if (isset($_GET[$key])) {
        return trim((string) $_GET[$key]);
    }
    return $default;
}


function imh_is_valid_date($date)
{
    if (empty($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


function imh_rank_badge($rank)
{
    if ($rank === 1) {
        return '🥇 1';
    } elseif ($rank === 2) {
        return '🥈 2';
    } elseif ($rank === 3) {
        return '🥉 3';
    }
    return (string) $rank;
}



/**

 * @param array $overrides key => value. Jika value null, key tsb dihapus.
 */
function imh_build_url(array $overrides = array())
{
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return '?' . http_build_query($params);
}


function imh_render_hidden_params(array $exclude = array())
{
    foreach ($_GET as $key => $value) {
        if (in_array($key, $exclude, true) || is_array($value)) {
            continue;
        }
        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . "\n";
    }
}

// ---------------------------------------------------------------------
// 3. Ambil & validasi parameter filter
// ---------------------------------------------------------------------
$tab        = imh_get_param('tab', 'peminjam');
$valid_tabs = array('peminjam', 'pengunjung', 'aktif');
if (!in_array($tab, $valid_tabs, true)) {
    $tab = 'peminjam';
}

$start_date_raw = imh_get_param('start_date');
$end_date_raw   = imh_get_param('end_date');

$start_date = imh_is_valid_date($start_date_raw) ? $start_date_raw : '';
$end_date   = imh_is_valid_date($end_date_raw) ? $end_date_raw : '';


if ($start_date !== '' && $end_date !== '' && strtotime($start_date) > strtotime($end_date)) {
    $tmp        = $start_date;
    $start_date = $end_date;
    $end_date   = $tmp;
}

$has_date_filter = ($start_date !== '' || $end_date !== '');


$start_date_esc = $dbs->escape_string($start_date);
$end_date_esc   = $dbs->escape_string($end_date);

function imh_build_date_where($column, $start, $end)
{
    if ($start !== '' && $end !== '') {
        return "DATE($column) BETWEEN '$start' AND '$end'";
    } elseif ($start !== '') {
        return "DATE($column) >= '$start'";
    } elseif ($end !== '') {
        return "DATE($column) <= '$end'";
    }
    return '1=1';
}

$where_loan_date  = imh_build_date_where('lh.loan_date', $start_date_esc, $end_date_esc);
$where_visit_date = imh_build_date_where('vc.checkin_date', $start_date_esc, $end_date_esc);

// ---------------------------------------------------------------------
// 4. Query per-tab
// ---------------------------------------------------------------------
$data          = array();
$query_error   = '';

try {
    if ($tab === 'peminjam') {
        // -----------------------------------------------------------
        // TOP 10 PEMINJAM TERSERING
        // -----------------------------------------------------------
        $sql = "SELECT lh.member_id AS member_id,
                       lh.member_name AS member_name,
                       COUNT(*) AS jumlah
                FROM loan_history AS lh
                WHERE $where_loan_date
                  AND lh.member_id IS NOT NULL AND lh.member_id != ''
                GROUP BY lh.member_id, lh.member_name
                ORDER BY jumlah DESC, lh.member_name ASC
                LIMIT 10";
        $q = $dbs->query($sql);
        while ($row = $q->fetch_assoc()) {
            $data[] = $row;
        }
    } elseif ($tab === 'pengunjung') {
        // -----------------------------------------------------------
        // TOP 10 PENGUNJUNG TERSERING
        // -----------------------------------------------------------
        $sql = "SELECT vc.member_id AS member_id,
                       vc.member_name AS member_name,
                       COUNT(*) AS jumlah
                FROM visitor_count AS vc
                WHERE $where_visit_date
                GROUP BY vc.member_id, vc.member_name
                ORDER BY jumlah DESC, vc.member_name ASC
                LIMIT 10";
        $q = $dbs->query($sql);
        while ($row = $q->fetch_assoc()) {
            $data[] = $row;
        }
    } else {
        // -----------------------------------------------------------
        // TOP 10 PENGUNJUNG TERAKTIF (Jumlah Peminjaman + Kunjungan terbanyak)
        // -----------------------------------------------------------
        $where_loan_date_sub  = imh_build_date_where('loan_date', $start_date_esc, $end_date_esc);
        $where_visit_date_sub = imh_build_date_where('checkin_date', $start_date_esc, $end_date_esc);

        $sql = "SELECT identity_id AS member_id,
                       identity_name AS member_name,
                       SUM(jumlah_pinjam) AS total_pinjam,
                       SUM(jumlah_kunjungan) AS total_kunjungan,
                       (SUM(jumlah_pinjam) + SUM(jumlah_kunjungan)) AS jumlah
                FROM (
                    SELECT member_id AS identity_id,
                           member_name AS identity_name,
                           COUNT(*) AS jumlah_pinjam,
                           0 AS jumlah_kunjungan
                    FROM loan_history
                    WHERE $where_loan_date_sub
                      AND member_id IS NOT NULL AND member_id != ''
                    GROUP BY member_id, member_name

                    UNION ALL

                    SELECT member_id AS identity_id,
                           member_name AS identity_name,
                           0 AS jumlah_pinjam,
                           COUNT(*) AS jumlah_kunjungan
                    FROM visitor_count
                    WHERE $where_visit_date_sub
                    GROUP BY member_id, member_name
                ) AS combined
                GROUP BY identity_id, identity_name
                ORDER BY jumlah DESC, identity_name ASC
                LIMIT 10";
        $q = $dbs->query($sql);
        while ($row = $q->fetch_assoc()) {
            $data[] = $row;
        }
    }
} catch (\Throwable $e) {
  
    $query_error = $e->getMessage();
}

// Query string dasar (dipakai ulang oleh form filter agar tab & tanggal konsisten)
$base_query_string = $_SERVER['PHP_SELF'];
?>
<style>
:root {
    --imh-primary: #0056b3;
    --imh-border: #e3eaf2;
    --imh-light: #f7fafd;
}
.imh-wrapper .per_title {
    background: var(--imh-primary);
    color: #fff;
    padding: 12px 16px;
    margin: -12px -16px 12px -16px;
    border-radius: 6px 6px 0 0;
}
.imh-wrapper .per_title h2 {
    margin: 0;
    font-size: 1.2em;
    font-weight: 600;
}
.imh-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.imh-tabs a {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 999px;
    border: 1.5px solid var(--imh-border);
    color: var(--imh-primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.92em;
    background: #fff;
}
.imh-tabs a.active {
    background: var(--imh-primary);
    color: #fff;
    border-color: var(--imh-primary);
}
.imh-filter-box {
    background: #fff;
    border: 1px solid var(--imh-border);
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
}
.imh-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}
.imh-filter-row input[type="date"] {
    padding: 7px 10px;
    border: 1px solid var(--imh-border);
    border-radius: 6px;
}
.imh-filter-row .s-btn {
    border-radius: 999px;
    padding: 8px 18px;
    font-weight: 600;
}
.imh-quick-filters {
    display: flex;
    gap: 4px;
}
.imh-quick-filters button {
    padding: 7px 12px;
    font-size: 0.85em;
    background: #fff;
    border: 1px solid var(--imh-border);
    border-radius: 999px;
    cursor: pointer;
    color: #555;
}
.imh-quick-filters button:hover {
    background: var(--imh-primary);
    color: #fff;
    border-color: var(--imh-primary);
}
.imh-info {
    background: var(--imh-light);
    border-left: 4px solid var(--imh-primary);
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: 0.95em;
}
table.imh-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
table.imh-table th {
    background: #e9eef6;
    padding: 10px 12px;
    text-align: left;
    font-weight: 700;
    border-bottom: 2px solid var(--imh-border);
}
table.imh-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
}
table.imh-table tr:nth-child(even) td {
    background: var(--imh-light);
}
table.imh-table td.imh-rank {
    font-weight: 700;
    white-space: nowrap;
    width: 70px;
}
table.imh-table td.imh-num {
    text-align: center;
    font-weight: 700;
    color: var(--imh-primary);
}
.imh-empty {
    padding: 20px;
    text-align: center;
    color: #888;
    background: #fff;
    border-radius: 8px;
    border: 1px dashed var(--imh-border);
}
@media print {
    .no-print { display: none !important; }
}
</style>

<div class="menuBox imh-wrapper">
    <div class="menuBoxInner">
        <div class="per_title">
            <h2><?php echo __('Top Peminjam & Pengunjung'); ?></h2>
        </div>

        <div class="sub_section p-3">
        <div id="imhAppRoot">

            <!-- Navigasi Tab -->
            <div class="imh-tabs no-print">
                <a class="<?php echo ($tab === 'peminjam') ? 'active' : ''; ?>"
                   href="<?php echo htmlspecialchars(imh_build_url(array('tab' => 'peminjam'))); ?>">
                    <?php echo __('10 Peminjam Tersering'); ?>
                </a>
                <a class="<?php echo ($tab === 'pengunjung') ? 'active' : ''; ?>"
                   href="<?php echo htmlspecialchars(imh_build_url(array('tab' => 'pengunjung'))); ?>">
                    <?php echo __('10 Pengunjung Tersering'); ?>
                </a>
                <a class="<?php echo ($tab === 'aktif') ? 'active' : ''; ?>"
                   href="<?php echo htmlspecialchars(imh_build_url(array('tab' => 'aktif'))); ?>">
                    <?php echo __('10 Pengunjung Teraktif'); ?>
                </a>
            </div>

            <!-- Filter Tanggal -->
            <div class="imh-filter-box no-print">
                <form method="get" action="<?php echo $base_query_string; ?>" id="imhFilterForm">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <?php

                        imh_render_hidden_params(array('tab', 'start_date', 'end_date'));
                    ?>
                    <div class="imh-filter-row">
                        <span>📅 <?php echo __('Rentang Tanggal'); ?>:</span>
                        <input type="date" name="start_date" id="imh_start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        <span>&mdash;</span>
                        <input type="date" name="end_date" id="imh_end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <button type="submit" class="s-btn btn btn-primary"><?php echo __('Terapkan'); ?></button>

                        <div class="imh-quick-filters">
                            <button type="button" onclick="imhSetQuickDate('today')"><?php echo __('Hari Ini'); ?></button>
                            <button type="button" onclick="imhSetQuickDate('week')"><?php echo __('Minggu Ini'); ?></button>
                            <button type="button" onclick="imhSetQuickDate('month')"><?php echo __('Bulan Ini'); ?></button>
                            <button type="button" onclick="imhSetQuickDate('year')"><?php echo __('Tahun Ini'); ?></button>
                        </div>

                        <?php if ($has_date_filter): ?>
                            <a class="s-btn btn btn-secondary" style="border-radius:999px;"
                               href="<?php echo htmlspecialchars(imh_build_url(array('start_date' => null, 'end_date' => null))); ?>">
                                <?php echo __('Reset'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Info periode aktif -->
            <div class="imh-info">
                <?php if ($has_date_filter): ?>
                    <?php echo __('Menampilkan data periode'); ?>:
                    <strong>
                        <?php echo $start_date !== '' ? htmlspecialchars($start_date) : __('awal data'); ?>
                        &ndash;
                        <?php echo $end_date !== '' ? htmlspecialchars($end_date) : __('sekarang'); ?>
                    </strong>
                <?php else: ?>
                    <?php echo __('Menampilkan data untuk seluruh periode. Gunakan filter tanggal di atas untuk mempersempit hasil.'); ?>
                <?php endif; ?>
            </div>

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

                            <?php if ($tab === 'peminjam'): ?>
                                <th style="text-align:center;"><?php echo __('Jumlah Peminjaman'); ?></th>
                            <?php elseif ($tab === 'pengunjung'): ?>
                                <th style="text-align:center;"><?php echo __('Jumlah Kunjungan'); ?></th>
                            <?php else: ?>
                                <th style="text-align:center;"><?php echo __('Total Peminjaman'); ?></th>
                                <th style="text-align:center;"><?php echo __('Total Kunjungan'); ?></th>
                                <th style="text-align:center;"><?php echo __('Total Aktivitas'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td class="imh-rank"><?php echo imh_rank_badge($rank); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_id']) ? $row['member_id'] : '-'); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row['member_name']) ? $row['member_name'] : __('(Tanpa Nama)')); ?></td>

                                <?php if ($tab === 'peminjam' || $tab === 'pengunjung'): ?>
                                    <td class="imh-num"><?php echo (int) $row['jumlah']; ?></td>
                                <?php else: ?>
                                    <td class="imh-num"><?php echo (int) $row['total_pinjam']; ?></td>
                                    <td class="imh-num"><?php echo (int) $row['total_kunjungan']; ?></td>
                                    <td class="imh-num"><?php echo (int) $row['jumlah']; ?></td>
                                <?php endif; ?>
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

<script>
(function () {


    function imhGetRoot() {
        return document.getElementById('imhAppRoot');
    }

    function imhSwap(html, url) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newRoot = doc.getElementById('imhAppRoot');
        var curRoot = imhGetRoot();
        if (newRoot && curRoot) {
            curRoot.innerHTML = newRoot.innerHTML;
        }
        if (url && window.history && window.history.pushState) {
            try { window.history.pushState({ imhAjax: true }, '', url); } catch (e) { /* abaikan */ }
        }
    }

    function imhNavigate(url) {
        var root = imhGetRoot();
        if (root) { root.style.opacity = '0.5'; }

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.text(); })
            .then(function (html) { imhSwap(html, url); })
            .catch(function () {

                window.location.href = url;
            })
            .finally(function () {
                var r = imhGetRoot();
                if (r) { r.style.opacity = '1'; }
            });
    }

    document.addEventListener('click', function (e) {
        var el = e.target;
        while (el && el !== document) {
            if (el.tagName === 'A') {
                var root = imhGetRoot();
                if (root && root.contains(el)) {
                    var href = el.getAttribute('href');
                    if (href && href.charAt(0) !== '#') {
                        e.preventDefault();
                        imhNavigate(href);
                    }
                }
                return;
            }
            el = el.parentNode;
        }
    });

    document.addEventListener('submit', function (e) {
        if (e.target && e.target.id === 'imhFilterForm') {
            e.preventDefault();
            var form = e.target;
            var params = new URLSearchParams(new FormData(form));
            var action = form.getAttribute('action') || window.location.pathname;
            imhNavigate(action + '?' + params.toString());
        }
    });

    // Quick date filter (Hari Ini / Minggu Ini / Bulan Ini / Tahun Ini)
    function imhPad(n) { return n < 10 ? '0' + n : '' + n; }
    function imhFormatDate(d) { return d.getFullYear() + '-' + imhPad(d.getMonth() + 1) + '-' + imhPad(d.getDate()); }

    window.imhSetQuickDate = function (range) {
        var today = new Date();
        var start = new Date();
        var end = today;

        if (range === 'today') {
            start = today;
        } else if (range === 'week') {
            var day = today.getDay() === 0 ? 7 : today.getDay(); // Senin=1 ... Minggu=7
            start = new Date(today);
            start.setDate(today.getDate() - (day - 1));
        } else if (range === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        } else if (range === 'year') {
            start = new Date(today.getFullYear(), 0, 1);
        }

        var sEl = document.getElementById('imh_start_date');
        var eEl = document.getElementById('imh_end_date');
        if (sEl) { sEl.value = imhFormatDate(start); }
        if (eEl) { eEl.value = imhFormatDate(end); }

        var form = document.getElementById('imhFilterForm');
        if (form) {
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                var evt = document.createEvent('Event');
                evt.initEvent('submit', true, true);
                form.dispatchEvent(evt);
            }
        }
    };
})();
</script>
