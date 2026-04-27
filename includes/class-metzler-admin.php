<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Metzler_AntiVPN_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_metzler-antivpn' !== $hook ) return;
        wp_enqueue_style( 'metzler-antivpn-admin', METZLER_ANTIVPN_URL . 'assets/admin.css', [], METZLER_ANTIVPN_VERSION );
    }

    public function add_settings_page() {
        add_menu_page( 'Metzler AntiVPN', 'AntiVPN', 'manage_options', 'metzler-antivpn', [ $this, 'render_admin_page' ], 'dashicons-shield', 80 );
    }

    public function register_settings() {
        register_setting( 'metzler_antivpn_options', 'metzler_antivpn_api_key', [ 'sanitize_callback' => [ $this, 'test_api_on_save' ] ] );
        register_setting( 'metzler_antivpn_options', 'metzler_antivpn_mode' );
        register_setting( 'metzler_antivpn_options', 'metzler_antivpn_action' );
        register_setting( 'metzler_antivpn_options', 'metzler_antivpn_redirect_url' );
    }

    public function test_api_on_save( $key ) {
        if ( ! empty( $key ) ) {
            $core = new Metzler_AntiVPN_Core();
            $core->query_api( '8.8.8.8', $key );
        }
        return $key;
    }

    public function handle_admin_actions() {
        if ( isset($_POST['metzler_clear_cache']) && current_user_can('manage_options') ) {
            global $wpdb;
            $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_avpn_%' OR option_name LIKE '_transient_timeout_avpn_%'" );
            wp_redirect( add_query_arg( ['page' => 'metzler-antivpn', 'cleared' => '1'] ) );
            exit;
        }
        if ( isset($_POST['metzler_clear_logs']) && current_user_can('manage_options') ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'metzler_antivpn_logs';
            $wpdb->query("TRUNCATE TABLE $table_name");
            wp_redirect( add_query_arg( ['page' => 'metzler-antivpn', 'tab' => 'logs', 'cleared_logs' => '1'] ) );
            exit;
        }
    }

    public function admin_notices() {
        if ( empty( get_option( 'metzler_antivpn_api_key' ) ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Metzler AntiVPN:</strong> Der Schutz ist inaktiv. <a href="https://portal.metzler-webseiten.de/antivpn/api-keys" target="_blank">Bitte API-Key eintragen</a>.</p></div>';
        }
        if ( isset($_GET['cleared']) ) echo '<div class="notice notice-success is-dismissible"><p>Der IP-Cache wurde erfolgreich geleert!</p></div>';
        if ( isset($_GET['cleared_logs']) ) echo '<div class="notice notice-success is-dismissible"><p>Das Logbuch wurde geleert!</p></div>';
    }

    public function render_admin_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'dashboard';
        ?>
        <div class="wrap metzler-wrap">
            <h1 style="display:none;">AntiVPN Einstellungen</h1>
            <div class="metzler-page-title">
                <span class="dashicons dashicons-shield" style="color: #1a73e8; font-size: 28px; width: 28px; height: 28px; margin-right: 10px;"></span>
                Metzler AntiVPN Protection
            </div>

            <div class="md-tabs">
                <a href="?page=metzler-antivpn&tab=dashboard" class="md-tab <?php echo $active_tab == 'dashboard' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-chart-bar"></span> Dashboard
                </a>
                <a href="?page=metzler-antivpn&tab=settings" class="md-tab <?php echo $active_tab == 'settings' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span> Einstellungen
                </a>
                <a href="?page=metzler-antivpn&tab=logs" class="md-tab <?php echo $active_tab == 'logs' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span> Logbuch
                </a>
            </div>

            <?php if ( $active_tab == 'dashboard' ): ?>
                <div class="md-card">
                    <div class="md-card-header">
                        <h2 class="md-card-title">API Quota & Status</h2>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="metzler_clear_cache" value="1">
                            <button type="submit" class="md-btn md-btn-outline">
                                <span class="dashicons dashicons-update-alt"></span> Cache leeren
                            </button>
                        </form>
                    </div>
                    
                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-label"><span class="dashicons dashicons-calendar-alt"></span> Monats-Limit</div>
                            <div class="stat-value"><?php echo esc_html(get_option('metzler_antivpn_quota_limit', '0')); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><span class="dashicons dashicons-chart-pie"></span> Genutzt</div>
                            <div class="stat-value danger"><?php echo esc_html(get_option('metzler_antivpn_quota_used', '0')); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><span class="dashicons dashicons-yes-alt"></span> Verbleibend</div>
                            <div class="stat-value success"><?php echo esc_html(get_option('metzler_antivpn_quota_remaining', '0')); ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><span class="dashicons dashicons-update"></span> Nächster Reset</div>
                            <div class="stat-value" style="font-size: 20px; margin-top: 8px;"><?php echo esc_html(get_option('metzler_antivpn_quota_reset', '-')); ?></div>
                        </div>
                    </div>

                    <div class="info-box">
                        <span class="dashicons dashicons-info" style="margin-top: 2px;"></span>
                        <p><strong>Hinweis zur Synchronisation:</strong> Die Daten aktualisieren sich automatisch bei jedem echten (nicht gecachten) API-Aufruf. Um dein Kontingent zu schonen, werden geprüfte IPs für 12 Stunden lokal zwischengespeichert.</p>
                    </div>
                </div>

            <?php elseif ( $active_tab == 'settings' ): ?>
                <div class="md-card">
                    <h2 class="md-card-title" style="margin-bottom: 24px;">Schutz-Konfiguration</h2>
                    <form method="post" action="options.php">
                        <?php settings_fields( 'metzler_antivpn_options' ); ?>
                        
                        <div class="md-input-group">
                            <label class="md-label">API-Key (Bearer Token)</label>
                            <input type="password" name="metzler_antivpn_api_key" value="<?php echo esc_attr( get_option('metzler_antivpn_api_key') ); ?>" class="md-input" />
                            <span class="md-hint">Erforderlich für die Erkennung. <a href="https://portal.metzler-webseiten.de/antivpn/api-keys" target="_blank">Hier neuen Key generieren</a>.</span>
                        </div>

                        <div class="md-input-group">
                            <label class="md-label">Prüf-Modus</label>
                            <select name="metzler_antivpn_mode" class="md-select">
                                <option value="page_load" <?php selected( get_option('metzler_antivpn_mode', 'page_load'), 'page_load' ); ?>>Jeder Seitenaufruf (Sicherste Methode)</option>
                                <option value="form_submit" <?php selected( get_option('metzler_antivpn_mode'), 'form_submit' ); ?>>Nur bei Formular-POSTs (Ressourcenschonend)</option>
                            </select>
                        </div>

                        <div class="md-input-group">
                            <label class="md-label">Aktion bei Erkennung</label>
                            <select name="metzler_antivpn_action" id="metzler_action_select" class="md-select">
                                <option value="default_page" <?php selected( get_option('metzler_antivpn_action', 'default_page'), 'default_page' ); ?>>Integrierte Block-Seite anzeigen (Empfohlen)</option>
                                <option value="redirect" <?php selected( get_option('metzler_antivpn_action'), 'redirect' ); ?>>Auf eigene URL weiterleiten</option>
                            </select>
                        </div>

                        <div class="md-input-group" id="metzler_redirect_row" style="<?php echo get_option('metzler_antivpn_action') == 'redirect' ? '' : 'display:none;'; ?>">
                            <label class="md-label">Weiterleitungs-URL</label>
                            <input type="url" name="metzler_antivpn_redirect_url" value="<?php echo esc_attr( get_option('metzler_antivpn_redirect_url') ); ?>" class="md-input" placeholder="https://deine-seite.de/zugriff-verweigert" />
                        </div>

                        <script>
                            document.getElementById('metzler_action_select').addEventListener('change', function() {
                                document.getElementById('metzler_redirect_row').style.display = (this.value === 'redirect') ? 'block' : 'none';
                            });
                        </script>
                        
                        <div class="info-box" style="background: #f1f3f4; color: #5f6368; margin-bottom: 24px;">
                            <span class="dashicons dashicons-saved" style="color: #188038; margin-top: 2px;"></span>
                            <p><strong>SEO & Bot Bypass aktiv:</strong> Google, Bing und andere verifizierte Suchmaschinen-Bots werden automatisch durch einen sicheren Reverse-DNS Check validiert. Dies garantiert <strong>0% negativen Impact auf dein SEO-Ranking</strong>.</p>
                        </div>
                        
                        <button type="submit" class="md-btn">
                            <span class="dashicons dashicons-saved"></span> Einstellungen speichern
                        </button>
                    </form>
                </div>

            <?php elseif ( $active_tab == 'logs' ): 
                global $wpdb;
                $table_name = $wpdb->prefix . 'metzler_antivpn_logs';
                $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 100");
            ?>
                <div class="md-card">
                    <div class="md-card-header">
                        <h2 class="md-card-title">Letzte 100 Zugriffsversuche</h2>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="metzler_clear_logs" value="1">
                            <button type="submit" class="md-btn md-btn-outline" onclick="return confirm('Möchtest du das Logbuch wirklich leeren?');">
                                <span class="dashicons dashicons-trash"></span> Logbuch leeren
                            </button>
                        </form>
                    </div>
                    
                    <div class="md-table-wrapper">
                        <table class="md-table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>IP Adresse</th>
                                    <th>Status</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($logs): foreach($logs as $log): ?>
                                <tr>
                                    <td style="color: #5f6368;"><?php echo date_i18n('d.m.Y H:i', strtotime($log->time)); ?></td>
                                    <td><strong><?php echo esc_html($log->ip); ?></strong></td>
                                    <td>
                                        <?php 
                                            if($log->status == 'blocked') echo '<span class="md-chip chip-blocked">Blockiert</span>';
                                            elseif($log->status == 'passed') echo '<span class="md-chip chip-passed">Erlaubt</span>';
                                            elseif($log->status == 'bot') echo '<span class="md-chip chip-bot">SEO Bot</span>';
                                            else echo esc_html($log->status);
                                        ?>
                                    </td>
                                    <td style="font-size:12px; color:#80868b;" title="<?php echo esc_attr($log->user_agent); ?>">
                                        <?php echo esc_html(mb_strimwidth($log->user_agent, 0, 45, '...')); ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 30px; color: #80868b;">Noch keine Protokolldaten vorhanden.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}