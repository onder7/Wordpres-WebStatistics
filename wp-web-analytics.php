//wp-content/plugins/wp-web-analytics/
<?php
/**
 * Plugin Name: Gelişmiş Web Analitik
 * Plugin URI: https://ondernet.net
 * Description: WordPress için gelişmiş ziyaretçi analizi ve online kullanıcı takibi
 * Version: 1.0.0
 * Author: Önder Aköz
 * License: GPL v2 or later
 */

// WordPress dışından erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sınıfını tanımla
class WP_Web_Analytics {
    
    private $version = '1.0.0';
    private $plugin_name = 'wp-web-analytics';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_get_analytics_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nopriv_get_analytics_stats', array($this, 'ajax_get_stats'));
        add_action('wp_footer', array($this, 'track_visitor'));
        add_action('wp_head', array($this, 'add_analytics_head'));
        
        // Shortcode ekle
        add_shortcode('analytics_widget', array($this, 'analytics_widget_shortcode'));
        add_shortcode('analytics_dashboard', array($this, 'analytics_dashboard_shortcode'));
        
        // Plugin aktivasyon/deaktivasyon
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Orijinal WebStatistics sınıfını dahil et
        require_once plugin_dir_path(__FILE__) . 'includes/WebStatistics.php';
        
        // Ziyaretçi takibini başlat
        if (!is_admin()) {
            $this->record_visitor();
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('wp-analytics-js', plugin_dir_url(__FILE__) . 'assets/analytics.js', array('jquery'), $this->version, true);
        wp_enqueue_style('wp-analytics-css', plugin_dir_url(__FILE__) . 'assets/analytics.css', array(), $this->version);
        
        // AJAX URL'ini JavaScript'e gönder
        wp_localize_script('wp-analytics-js', 'wpAnalytics', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('analytics_nonce')
        ));
    }
    
    public function admin_menu() {
        add_menu_page(
            'Web Analitik',
            'Web Analitik',
            'manage_options',
            'wp-web-analytics',
            array($this, 'admin_page'),
            'dashicons-chart-area',
            30
        );
        
        add_submenu_page(
            'wp-web-analytics',
            'İstatistikler',
            'İstatistikler',
            'manage_options',
            'wp-web-analytics',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'wp-web-analytics',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'wp-analytics-settings',
            array($this, 'settings_page')
        );
    }
    
    public function record_visitor() {
        // Orijinal WebStatistics sınıfını kullan
        $webStats = new WebStatistics();
        $webStats->recordUserOnline();
    }
    
    public function track_visitor() {
        // Sadece frontend'de çalıştır
        if (!is_admin()) {
            echo '<script>
                // Ziyaretçi takibi için AJAX çağrısı
                jQuery(document).ready(function($) {
                    $.post(wpAnalytics.ajax_url, {
                        action: "get_analytics_stats",
                        nonce: wpAnalytics.nonce
                    });
                });
            </script>';
        }
    }
    
    public function add_analytics_head() {
        // Meta tag ekle (SEO ve analitik için)
        echo '<meta name="analytics-enabled" content="true">';
    }
    
    public function ajax_get_stats() {
        check_ajax_referer('analytics_nonce', 'nonce');
        
        // Orijinal WebStatistics sınıfını kullan
        $webStats = new WebStatistics();
        $stats = $webStats->getCompactStats();
        
        wp_send_json_success($stats);
    }
    
    public function analytics_widget_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'all', // all, online, today, week, month
            'style' => 'modern' // modern, classic, minimal
        ), $atts);
        
        // Orijinal WebStatistics sınıfını kullan
        $webStats = new WebStatistics();
        $stats = $webStats->getCompactStats();
        
        ob_start();
        ?>
        <div class="wp-analytics-widget wp-analytics-<?php echo esc_attr($atts['style']); ?>">
            <?php if ($atts['show'] == 'all' || $atts['show'] == 'online'): ?>
                <div class="analytics-item">
                    <span class="analytics-value"><?php echo $stats['online']; ?></span>
                    <span class="analytics-label">🟢 Online</span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show'] == 'all' || $atts['show'] == 'today'): ?>
                <div class="analytics-item">
                    <span class="analytics-value"><?php echo $stats['today']; ?></span>
                    <span class="analytics-label">📅 Bugün</span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show'] == 'all' || $atts['show'] == 'week'): ?>
                <div class="analytics-item">
                    <span class="analytics-value"><?php echo $stats['week']; ?></span>
                    <span class="analytics-label">📊 Bu Hafta</span>
                </div>
            <?php endif; ?>
            
            <?php if ($atts['show'] == 'all' || $atts['show'] == 'month'): ?>
                <div class="analytics-item">
                    <span class="analytics-value"><?php echo $stats['month']; ?></span>
                    <span class="analytics-label">📈 Bu Ay</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function analytics_dashboard_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p>Bu içeriği görüntüleme yetkiniz yok.</p>';
        }
        
        // Tam dashboard'u include et
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/dashboard.php';
        return ob_get_clean();
    }
    
    public function admin_page() {
        include plugin_dir_path(__FILE__) . 'admin/admin-page.php';
    }
    
    public function settings_page() {
        include plugin_dir_path(__FILE__) . 'admin/settings-page.php';
    }
    
    public function activate() {
        // Plugin aktivasyonu sırasında gerekli tabloları oluştur
        $this->create_tables();
        
        // Varsayılan ayarları ekle
        add_option('wp_analytics_settings', array(
            'track_admins' => false,
            'track_logged_users' => true,
            'online_timeout' => 300,
            'enable_widget' => true
        ));
    }
    
    public function deactivate() {
        // Temizlik işlemleri (isteğe bağlı)
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'analytics_visitors';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_ip varchar(45) NOT NULL,
            user_agent text,
            visit_date date NOT NULL,
            visit_time datetime DEFAULT CURRENT_TIMESTAMP,
            page_url varchar(255),
            PRIMARY KEY (id),
            KEY user_ip (user_ip),
            KEY visit_date (visit_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// WordPress WebStatistics sınıfı
class WP_WebStatistics {
    private $table_prefix;
    
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix;
    }
    
    public function recordUserOnline() {
        // WordPress veritabanı kullanarak kayıt
        global $wpdb;
        
        $user_ip = $this->getUserIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $current_url = home_url($_SERVER['REQUEST_URI']);
        $visit_date = current_time('Y-m-d');
        
        // Bugün bu IP'den ziyaret var mı kontrol et
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_prefix}analytics_visitors 
             WHERE user_ip = %s AND visit_date = %s",
            $user_ip, $visit_date
        ));
        
        if (!$existing) {
            // Yeni ziyaretçi kaydı
            $wpdb->insert(
                $this->table_prefix . 'analytics_visitors',
                array(
                    'user_ip' => $user_ip,
                    'user_agent' => $user_agent,
                    'visit_date' => $visit_date,
                    'page_url' => $current_url
                )
            );
        }
        
        // Online kullanıcıları WordPress transient ile yönet
        $online_users = get_transient('wp_analytics_online_users') ?: array();
        $online_users[$user_ip] = array(
            'ip' => $user_ip,
            'last_seen' => time(),
            'user_agent' => $user_agent
        );
        
        // 5 dakika boyunca sakla
        set_transient('wp_analytics_online_users', $online_users, 300);
    }
    
    public function getOnlineCount() {
        $online_users = get_transient('wp_analytics_online_users') ?: array();
        $current_time = time();
        $active_users = array();
        
        foreach ($online_users as $ip => $user) {
            if (($current_time - $user['last_seen']) <= 300) { // 5 dakika
                $active_users[$ip] = $user;
            }
        }
        
        // Güncellenmiş listeyi kaydet
        set_transient('wp_analytics_online_users', $active_users, 300);
        
        return count($active_users);
    }
    
    public function getTodayVisits() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_ip) FROM {$this->table_prefix}analytics_visitors 
             WHERE visit_date = %s",
            $today
        ));
    }
    
    public function getWeeklyStats() {
        global $wpdb;
        
        $week_start = date('Y-m-d', strtotime('monday this week'));
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_ip) FROM {$this->table_prefix}analytics_visitors 
             WHERE visit_date >= %s",
            $week_start
        ));
    }
    
    public function getMonthlyStats() {
        global $wpdb;
        
        $month_start = date('Y-m-01');
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_ip) FROM {$this->table_prefix}analytics_visitors 
             WHERE visit_date >= %s",
            $month_start
        ));
    }
    
    public function getCompactStats() {
        return array(
            'online' => $this->getOnlineCount(),
            'today' => $this->getTodayVisits(),
            'week' => $this->getWeeklyStats(),
            'month' => $this->getMonthlyStats()
        );
    }
    
    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}

// Plugin'i başlat
new WP_Web_Analytics();

// WordPress Widget sınıfı
class WP_Analytics_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'wp_analytics_widget',
            'Web Analitik Widget',
            array('description' => 'Site ziyaretçi istatistiklerini gösterir')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Orijinal WebStatistics sınıfını kullan
        $webStats = new WebStatistics();
        $stats = $webStats->getCompactStats();
        
        echo '<div class="wp-analytics-widget-content">';
        echo '<div class="stat-item"><strong>' . $stats['online'] . '</strong> Online</div>';
        echo '<div class="stat-item"><strong>' . $stats['today'] . '</strong> Bugün</div>';
        echo '<div class="stat-item"><strong>' . $stats['week'] . '</strong> Bu Hafta</div>';
        echo '<div class="stat-item"><strong>' . $stats['month'] . '</strong> Bu Ay</div>';
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Site İstatistikleri';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

// Widget'ı kaydet
function register_wp_analytics_widget() {
    register_widget('WP_Analytics_Widget');
}
add_action('widgets_init', 'register_wp_analytics_widget');

?>
