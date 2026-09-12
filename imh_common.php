<?php
/**
 * imh_common.php
 * ---------------------------------------------------------------------
 * Kompatibel PHP 7.4 & PHP 8.0+
 * ---------------------------------------------------------------------
 */


if (!function_exists('imh_bootstrap')) {
    function imh_bootstrap()
    {
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
    }
}

if (!function_exists('imh_get_param')) {
    function imh_get_param($key, $default = '')
    {
        if (isset($_GET[$key])) {
            return trim((string) $_GET[$key]);
        }
        return $default;
    }
}

if (!function_exists('imh_is_valid_date')) {
    function imh_is_valid_date($date)
    {
        if (empty($date)) {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

if (!function_exists('imh_rank_badge')) {
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
}

if (!function_exists('imh_build_date_where')) {
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
}


if (!function_exists('imh_build_url')) {
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
}


if (!function_exists('imh_render_hidden_params')) {
    function imh_render_hidden_params(array $exclude = array())
    {
        foreach ($_GET as $key => $value) {
            if (in_array($key, $exclude, true) || is_array($value)) {
                continue;
            }
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . "\n";
        }
    }
}


if (!function_exists('imh_get_filter_dates')) {
    function imh_get_filter_dates()
    {
        global $dbs;

        $start_date = imh_get_param('start_date');
        $end_date   = imh_get_param('end_date');

        $start_date = imh_is_valid_date($start_date) ? $start_date : '';
        $end_date   = imh_is_valid_date($end_date) ? $end_date : '';

        if ($start_date !== '' && $end_date !== '' && strtotime($start_date) > strtotime($end_date)) {
            $tmp        = $start_date;
            $start_date = $end_date;
            $end_date   = $tmp;
        }

        return array(
            'start'      => $start_date,
            'end'        => $end_date,
            'start_esc'  => $dbs->escape_string($start_date),
            'end_esc'    => $dbs->escape_string($end_date),
            'has_filter' => ($start_date !== '' || $end_date !== ''),
        );
    }
}


if (!function_exists('imh_render_style')) {
    function imh_render_style()
    {
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
<?php
    }
}


if (!function_exists('imh_render_filter_bar')) {
    function imh_render_filter_bar($dates)
    {
        $start_date      = $dates['start'];
        $end_date        = $dates['end'];
        $has_date_filter = $dates['has_filter'];
?>
        <div class="imh-filter-box no-print">
            <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="imhFilterForm">
                <?php
  
                    imh_render_hidden_params(array('start_date', 'end_date'));
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
<?php
    }
}


if (!function_exists('imh_render_script')) {
    function imh_render_script()
    {
?>
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

    function imhPad(n) { return n < 10 ? '0' + n : '' + n; }
    function imhFormatDate(d) { return d.getFullYear() + '-' + imhPad(d.getMonth() + 1) + '-' + imhPad(d.getDate()); }

    window.imhSetQuickDate = function (range) {
        var today = new Date();
        var start = new Date();
        var end = today;

        if (range === 'today') {
            start = today;
        } else if (range === 'week') {
            var day = today.getDay() === 0 ? 7 : today.getDay();
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
<?php
    }
}
