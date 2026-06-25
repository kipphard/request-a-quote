<?php
/**
 * Uninstall-Routine: Plugin-Optionen entfernen.
 *
 * Angebotsanfragen (CPT aga_quote) werden NICHT gelöscht – sie sind Geschäftsdaten.
 *
 * @package Kipphard\Angebotsanfrage
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'aga_settings' );
