<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Metzler_AntiVPN_Core {

    private $api_url = 'https://antivpn.metzler-webseiten.de/premium/lookup/';

    public function __construct() {
        add_action( 'template_redirect', [ $this, 'check_visitor_ip' ], 1 );
    }

    public static function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'metzler_antivpn_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            ip varchar(45) NOT NULL,
            status varchar(20) NOT NULL,
            user_agent text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    public static function log_request( $ip, $status ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'metzler_antivpn_logs';
        $wpdb->insert(
            $table_name,
            [
                'ip' => $ip,
                'status' => $status,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250)
            ]
        );

        // Aufräumen: Nur die neuesten 500 behalten
        $wpdb->query("DELETE FROM $table_name WHERE id NOT IN (SELECT id FROM (SELECT id FROM $table_name ORDER BY id DESC LIMIT 500) x)");
    }

    private function get_client_ip() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ips = explode( ',', $_SERVER[ $header ] );
                $ip = trim( $ips[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    private function is_verified_bot( $ip, $user_agent ) {
        $ua_lower = strtolower($user_agent);
        $expected_domain = false;

        if ( strpos($ua_lower, 'googlebot') !== false ) $expected_domain = '.googlebot.com';
        elseif ( strpos($ua_lower, 'bingbot') !== false ) $expected_domain = '.search.msn.com';
        
        if ( ! $expected_domain ) return false; 

        $hostname = gethostbyaddr($ip);
        if ( substr($hostname, -strlen($expected_domain)) === $expected_domain ) {
            $resolved_ip = gethostbyname($hostname);
            if ( $resolved_ip === $ip ) return true;
        }

        return false;
    }

    public function check_visitor_ip() {
        if ( is_admin() || wp_doing_ajax() ) return;

        $api_key = get_option( 'metzler_antivpn_api_key' );
        if ( empty( $api_key ) ) return;

        $action = get_option( 'metzler_antivpn_action', 'default_page' );
        $redirect_url = get_option( 'metzler_antivpn_redirect_url' );

        if ( $action == 'redirect' && empty( $redirect_url ) ) return;
        if ( $action == 'redirect' && strpos( home_url($_SERVER['REQUEST_URI']), $redirect_url ) !== false ) return;
        if ( get_option( 'metzler_antivpn_mode' ) === 'form_submit' && $_SERVER['REQUEST_METHOD'] !== 'POST' ) return;

        $ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $transient_key = 'avpn_' . md5( $ip );
        
        $status = get_transient( $transient_key );

        if ( false === $status ) {
            if ( $this->is_verified_bot( $ip, $user_agent ) ) {
                $status = 'bot';
                set_transient( $transient_key, $status, 7 * DAY_IN_SECONDS );
            } else {
                $api_result = $this->query_api( $ip, $api_key );
                if ( $api_result !== 'error' ) {
                    $status = ($api_result === 'yes') ? 'blocked' : 'passed';
                    set_transient( $transient_key, $status, 12 * HOUR_IN_SECONDS );
                } else {
                    $status = 'passed'; // Fail-Open
                }
            }
            self::log_request($ip, $status);
        }

        if ( $status === 'blocked' ) {
            if ( $action === 'redirect' ) {
                wp_redirect( $redirect_url, 302 );
                exit;
            } else {
                $this->show_default_block_page($ip);
            }
        }
    }

    public function query_api( $ip, $api_key ) {
        $response = wp_remote_get( $this->api_url . $ip, [
            'timeout' => 5,
            'headers' => [ 'Authorization' => 'Bearer ' . $api_key, 'Accept' => 'application/json' ]
        ]);

        if ( is_wp_error( $response ) ) return 'error';

        $limit = wp_remote_retrieve_header( $response, 'x-monthly-quota-limit' );
        if($limit) {
            update_option( 'metzler_antivpn_quota_limit', $limit );
            update_option( 'metzler_antivpn_quota_used', wp_remote_retrieve_header( $response, 'x-monthly-quota-used' ) );
            update_option( 'metzler_antivpn_quota_remaining', wp_remote_retrieve_header( $response, 'x-monthly-quota-remaining' ) );
            update_option( 'metzler_antivpn_quota_reset', wp_remote_retrieve_header( $response, 'x-monthly-quota-reset-at' ) );
        }

        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return 'error';

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( (isset($data['data']['is_datacenter']) && $data['data']['is_datacenter']) || (isset($data['data']['is_vpn']) && $data['data']['is_vpn']) ) {
            return 'yes';
        }
        return 'no';
    }

    private function show_default_block_page($ip) {
        status_header( 403 ); 
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>403 | Zugriff verweigert</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: #202124; }
                .md-card { background: #fff; padding: 48px 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); max-width: 480px; text-align: center; width: 90%; }
                .icon-container { background: #fce8e6; color: #d93025; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto; }
                .icon-container svg { width: 40px; height: 40px; }
                h1 { font-size: 24px; margin: 0 0 16px 0; font-weight: 500; }
                p { color: #5f6368; line-height: 1.6; font-size: 15px; margin-bottom: 32px; }
                .ip-box { background: #f1f3f4; border: 1px solid #dadce0; padding: 12px 16px; border-radius: 6px; font-family: monospace; color: #3c4043; font-size: 14px; display: inline-block; }
                .footer-text { margin-top: 32px; font-size: 12px; color: #9aa0a6; }
            </style>
        </head>
        <body>
            <div class="md-card">
                <div class="icon-container">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h1>Sicherheitsblockade aktiv</h1>
                <p>Der Zugriff auf diese Website ist aus deinem aktuellen Netzwerk nicht gestattet. Unser System hat die Nutzung eines <strong>VPN-Dienstes, Proxys oder Datacenter-Netzwerks</strong> erkannt. <br><br>Bitte deaktiviere deinen Anonymisierungsdienst und lade die Seite neu, um fortzufahren.</p>
                <div class="ip-box">Client IP: <?php echo esc_html($ip); ?></div>
                <div class="footer-text">Protected by Metzler AntiVPN</div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}