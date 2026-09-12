<?php
/**
 * Plugin Name: Top Peminjam & Pengunjung
 * Plugin URI: https://github.com/ImamHasanudin/Top10-Peminjam-dan-Pengunjung/
 * Description: 3 laporan terpisah - 10 Peminjam Tersering, 10 Pengunjung
 *              Tersering, dan 10 Pengunjung Teraktif (Peminjaman + Kunjungan
 *              terbanyak) - masing-masing dengan filter rentang tanggal.
 * Version: 2.0.0
 * Author: Imam Hasanudin
 * Author URI: https://github.com/ImamHasanudin/
 * Compatibility: SLiMS 9 (Bulian) ke atas | PHP 7.4 dan PHP 8.0 ke atas
 *

 */

$plugin = \SLiMS\Plugins::getInstance();

$plugin->registerMenu('reporting', '10 Peminjam Tersering', __DIR__ . '/index_peminjam.php');
$plugin->registerMenu('reporting', '10 Pengunjung Tersering', __DIR__ . '/index_pengunjung.php');
$plugin->registerMenu('reporting', '10 Pengunjung Teraktif', __DIR__ . '/index_aktif.php');
