<?php
/**
 * Plugin Name: Metzler AntiVPN & Proxy Blocker
 * Description: Blockiert VPNs, Proxys und Datacenter-IPs mit professionellem Logging und echtem SEO-Bot-Bypass (DSGVO-konform).
 * Version: 1.0.0
 * Author: Tiziano Santo Metzler
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'METZLER_ANTIVPN_VERSION', '2.0.0' );
define( 'METZLER_ANTIVPN_DIR', plugin_dir_path( __FILE__ ) );
define( 'METZLER_ANTIVPN_URL', plugin_dir_url( __FILE__ ) );

// Klassen laden
require_once METZLER_ANTIVPN_DIR . 'includes/class-metzler-core.php';

if ( is_admin() ) {
    require_once METZLER_ANTIVPN_DIR . 'includes/class-metzler-admin.php';
    new Metzler_AntiVPN_Admin();
}

// Plugin initialisieren
new Metzler_AntiVPN_Core();

// Aktivierungs-Hook
register_activation_hook( __FILE__, [ 'Metzler_AntiVPN_Core', 'activate_plugin' ] );