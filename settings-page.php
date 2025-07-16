<?php
// WordPress Settings Sayfası - admin/settings-page.php

if (!defined('ABSPATH')) {
    exit;
}

// Ayarları kaydet
if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['analytics_settings_nonce'], 'save_analytics_settings')) {
    $settings = array(
        'track_admins' => isset($_POST['track_admins']) ? 1 : 0,
        'track_logged_users' => isset($_POST['track_logged_users']) ? 1 : 0,
        'online_timeout' => intval($_POST['online_timeout']),
        'enable_widget' => isset($_POST['enable_widget']) ? 1 : 0,
        'enable_shortcodes' => isset($_POST['enable_shortcodes']) ? 1 : 0,
        'data_retention_days' => intval($_POST['data_retention_days']),
        'exclude_ips' => sanitize_textarea_field($_POST['exclude_ips']),
        'track_pages' => isset($_POST['track_pages']) ? 1 : 0,
        'show_in_footer' => isset($_POST['show_in_footer']) ? 1 : 0
    );
    
    update_option('wp_analytics_settings', $settings);
    
    echo '<div class="notice notice-success"><p><strong>✅ Ayarlar başarıyla kaydedildi!</strong></p></div>';
}

// Verileri temizle
if (isset($_POST['clear_data']) && wp_verify_nonce($_POST['analytics_clear_nonce'], 'clear_analytics_data')) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'analytics_visitors';
    
    $days = intval($_POST['clear_days']);
    if ($days > 0) {
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE visit_date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            $days
        ));
        
        echo '<div class="notice notice-success"><p><strong>🗑️ ' . $deleted . ' kayıt başarıyla silindi!</strong></p></div>';
    }
}

// Mevcut ayarları al
$default_settings = array(
    'track_admins' => 0,
    'track_logged_users' => 1,
    'online_timeout' => 300,
    'enable_widget' => 1,
    'enable_shortcodes' => 1,
    'data_retention_days' => 365,
    'exclude_ips' => '',
    'track_pages' => 1,
    'show_in_footer' => 0
);

$settings = wp_parse_args(get_option('wp_analytics_settings', array()), $default_settings);

// İstatistikler
global $wpdb;
$table_name = $wpdb->prefix . 'analytics_visitors';
$total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
$oldest_record = $wpdb->get_var("SELECT MIN(visit_date) FROM {$table_name}");
$database_size = $wpdb->get_var($wpdb->prepare(
    "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) 
     FROM information_schema.TABLES 
     WHERE table_schema = %s AND table_name = %s",
    DB_NAME, $table_name
));
?>

<div class="wrap wp-analytics-admin">
    <h1>⚙️ Web Analitik Ayarları</h1>
    
    <div style="margin: 20px 0;">
        <p>WordPress Web Analitik plugin'inin davranışını bu ayarlarla özelleştirebilirsiniz.</p>
    </div>

    <!-- Ana Ayarlar Formu -->
    <form method="post" action="">
        <?php wp_nonce_field('save_analytics_settings', 'analytics_settings_nonce'); ?>
        
        <div class="analytics-dashboard">
            <h2>🎯 Genel Ayarlar</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="track_admins">Admin Kullanıcılarını Takip Et</label>
                    </th>
                    <td>
                        <input type="checkbox" id="track_admins" name="track_admins" value="1" 
                               <?php checked($settings['track_admins'], 1); ?>>
                        <p class="description">Yönetici yetkisine sahip kullanıcıların ziyaretlerini say</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="track_logged_users">Giriş Yapmış Kullanıcıları Takip Et</label>
                    </th>
                    <td>
                        <input type="checkbox" id="track_logged_users" name="track_logged_users" value="1" 
                               <?php checked($settings['track_logged_users'], 1); ?>>
                        <p class="description">WordPress'e giriş yapmış kullanıcıların ziyaretlerini say</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="track_pages">Sayfa URL'lerini Takip Et</label>
                    </th>
                    <td>
                        <input type="checkbox" id="track_pages" name="track_pages" value="1" 
                               <?php checked($settings['track_pages'], 1); ?>>
                        <p class="description">Hangi sayfaların ziyaret edildiğini kaydet</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="online_timeout">Online Timeout (Saniye)</label>
                    </th>
                    <td>
                        <input type="number" id="online_timeout" name="online_timeout" 
                               value="<?php echo esc_attr($settings['online_timeout']); ?>" 
                               min="60" max="3600" class="small-text">
                        <p class="description">Kullanıcının ne kadar süre sonra offline sayılacağı (varsayılan: 300 saniye = 5 dakika)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="data_retention_days">Veri Saklama Süresi (Gün)</label>
                    </th>
                    <td>
                        <input type="number" id="data_retention_days" name="data_retention_days" 
                               value="<?php echo esc_attr($settings['data_retention_days']); ?>" 
                               min="30" max="3650" class="small-text">
                        <p class="description">Analitik verilerinin ne kadar süre saklanacağı (varsayılan: 365 gün)</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="analytics-dashboard">
            <h2>🎨 Görünüm Ayarları</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_widget">Widget'ı Etkinleştir</label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_widget" name="enable_widget" value="1" 
                               <?php checked($settings['enable_widget'], 1); ?>>
                        <p class="description">Sidebar widget'ını kullanılabilir yap</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_shortcodes">Shortcode'ları Etkinleştir</label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_shortcodes" name="enable_shortcodes" value="1" 
                               <?php checked($settings['enable_shortcodes'], 1); ?>>
                        <p class="description">[analytics_widget] gibi shortcode'ları kullanılabilir yap</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="show_in_footer">Footer'da Göster</label>
                    </th>
                    <td>
                        <input type="checkbox" id="show_in_footer" name="show_in_footer" value="1" 
                               <?php checked($settings['show_in_footer'], 1); ?>>
                        <p class="description">Site footer'ında küçük bir istatistik göster</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="analytics-dashboard">
            <h2>🚫 Hariç Tutma Ayarları</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="exclude_ips">Hariç Tutulacak IP Adresleri</label>
                    </th>
                    <td>
                        <textarea id="exclude_ips" name="exclude_ips" rows="5" cols="50" 
                                  class="large-text code"><?php echo esc_textarea($settings['exclude_ips']); ?></textarea>
                        <p class="description">
                            Her satıra bir IP adresi yazın. Bu IP'lerden gelen ziyaretler sayılmayacak.<br>
                            Örnek:<br>
                            192.168.1.1<br>
                            10.0.0.1
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="save_settings" class="button-primary" value="💾 Ayarları Kaydet">
        </p>
    </form>

    <!-- Veri Yönetimi -->
    <div class="analytics-dashboard">
        <h2>📊 Veri Yönetimi</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #1976d2;">📈 Toplam Kayıt</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #1976d2;">
                    <?php echo number_format($total_records); ?>
                </p>
                <p style="margin: 0; color: #666;">ziyaretçi kaydı</p>
            </div>
        
        <h3>Özel Tema Entegrasyonu:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>functions.php'ye eklenecek kod:</strong></p>
            <code>
// Analitik widget'ını header'a ekle<br>
function add_analytics_to_header() {<br>
&nbsp;&nbsp;&nbsp;&nbsp;if (class_exists('WP_WebStatistics')) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$stats = new WP_WebStatistics();<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$data = $stats->getCompactStats();<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;echo '&lt;div class="site-analytics"&gt;Online: ' . $data['online'] . '&lt;/div&gt;';<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
}<br>
add_action('wp_head', 'add_analytics_to_header');
            </code>
        </div>
    </div>

    <!-- Plugin Bilgileri -->
    <div class="analytics-dashboard">
        <h2>ℹ️ Plugin Bilgileri</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h4>📋 Özellikler</h4>
                <ul>
                    <li>✅ Gerçek zamanlı online kullanıcı takibi</li>
                    <li>✅ Günlük, haftalık, aylık istatistikler</li>
                    <li>✅ IP bazlı tekil ziyaretçi sayımı</li>
                    <li>✅ Sayfa bazlı analiz</li>
                    <li>✅ WordPress widget desteği</li>
                    <li>✅ Shortcode entegrasyonu</li>
                    <li>✅ AJAX güncellemeler</li>
                    <li>✅ Veri dışa aktarma</li>
                </ul>
            </div>
            
            <div>
                <h4>🚀 Performans</h4>
                <ul>
                    <li>⚡ Hafif ve hızlı</li>
                    <li>🗃️ Veritabanı optimizasyonu</li>
                    <li>🔒 Güvenlik önlemleri</li>
                    <li>📱 Responsive tasarım</li>
                    <li>🎨 Özelleştirilebilir CSS</li>
                    <li>🔌 WordPress standartları</li>
                    <li>⚙️ Kolay kurulum</li>
                    <li>🔄 Otomatik güncellemeler</li>
                </ul>
            </div>
        </div>
        
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <h4 style="margin-top: 0; color: #1976d2;">📞 Destek</h4>
            <p>Plugin ile ilgili sorularınız için:</p>
            <ul>
                <li>🌐 Web: <a href="https://yourwebsite.com" target="_blank">yourwebsite.com</a></li>
                <li>📧 E-posta: support@yourwebsite.com</li>
                <li>📖 Dokümantasyon: <a href="https://docs.yourwebsite.com" target="_blank">docs.yourwebsite.com</a></li>
            </ul>
        </div>
    </div>

    <!-- Test Alanı -->
    <div class="analytics-dashboard">
        <h2>🧪 Test Alanı</h2>
        
        <p>Aşağıdaki butonlarla plugin'in çalışıp çalışmadığını test edebilirsiniz:</p>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0;">
            <button onclick="testAnalyticsAjax()" class="analytics-btn">
                🔄 AJAX Test Et
            </button>
            
            <button onclick="testShortcode()" class="analytics-btn analytics-btn-secondary">
                📝 Shortcode Test Et
            </button>
            
            <button onclick="showCurrentStats()" class="analytics-btn analytics-btn-secondary">
                📊 Anlık İstatistik
            </button>
        </div>
        
        <div id="test-results" style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 100px; display: none;">
            <h4>Test Sonuçları:</h4>
            <div id="test-output"></div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    function testAnalyticsAjax() {
        const testResults = document.getElementById('test-results');
        const testOutput = document.getElementById('test-output');
        
        testResults.style.display = 'block';
        testOutput.innerHTML = '<p>🔄 AJAX testi başlatılıyor...</p>';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_analytics_stats&nonce=<?php echo wp_create_nonce('analytics_nonce'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                testOutput.innerHTML = `
                    <p>✅ <strong>AJAX testi başarılı!</strong></p>
                    <ul>
                        <li>Online: ${data.data.online}</li>
                        <li>Bugün: ${data.data.today}</li>
                        <li>Bu hafta: ${data.data.week}</li>
                        <li>Bu ay: ${data.data.month}</li>
                    </ul>
                `;
            } else {
                testOutput.innerHTML = '<p>❌ <strong>AJAX testi başarısız!</strong> Hata: ' + (data.data || 'Bilinmeyen hata') + '</p>';
            }
        })
        .catch(error => {
            testOutput.innerHTML = '<p>❌ <strong>AJAX hatası:</strong> ' + error.message + '</p>';
        });
    }
    
    function testShortcode() {
        const testResults = document.getElementById('test-results');
        const testOutput = document.getElementById('test-output');
        
        testResults.style.display = 'block';
        testOutput.innerHTML = `
            <p>✅ <strong>Shortcode örnekleri:</strong></p>
            <div style="background: white; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <p><strong>Basit widget:</strong></p>
                <code>[analytics_widget]</code>
                
                <p style="margin-top: 15px;"><strong>Sadece online:</strong></p>
                <code>[analytics_widget show="online" style="modern"]</code>
                
                <p style="margin-top: 15px;"><strong>Bugün ve online:</strong></p>
                <code>[analytics_widget show="online,today" style="classic"]</code>
            </div>
            <p>Bu kodları sayfa veya yazı içeriğine ekleyerek test edebilirsiniz.</p>
        `;
    }
    
    function showCurrentStats() {
        const testResults = document.getElementById('test-results');
        const testOutput = document.getElementById('test-output');
        
        testResults.style.display = 'block';
        testOutput.innerHTML = `
            <p>📊 <strong>Şu anki istatistikler:</strong></p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 15px 0;">
                <div style="background: white; padding: 15px; text-align: center; border-radius: 5px;">
                    <div style="font-size: 2em; font-weight: bold; color: #28a745;"><?php echo $webStats->getOnlineCount(); ?></div>
                    <div>🟢 Online</div>
                </div>
                <div style="background: white; padding: 15px; text-align: center; border-radius: 5px;">
                    <div style="font-size: 2em; font-weight: bold; color: #007bff;"><?php echo $webStats->getTodayVisits(); ?></div>
                    <div>📅 Bugün</div>
                </div>
                <div style="background: white; padding: 15px; text-align: center; border-radius: 5px;">
                    <div style="font-size: 2em; font-weight: bold; color: #fd7e14;"><?php echo $webStats->getWeeklyStats(); ?></div>
                    <div>📊 Bu Hafta</div>
                </div>
                <div style="background: white; padding: 15px; text-align: center; border-radius: 5px;">
                    <div style="font-size: 2em; font-weight: bold; color: #6f42c1;"><?php echo $webStats->getMonthlyStats(); ?></div>
                    <div>📈 Bu Ay</div>
                </div>
            </div>
            <p><small>⏰ Son güncelleme: ${new Date().toLocaleTimeString('tr-TR')}</small></p>
        `;
    }
    
    // Sayfa yüklendiğinde otomatik test
    document.addEventListener('DOMContentLoaded', function() {
        // 2 saniye sonra otomatik AJAX testi
        setTimeout(function() {
            console.log('WordPress Analytics Plugin yüklendi ✅');
        }, 2000);
    });
    
    // Form submit confirmation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (e.target.name === 'save_settings') {
            const confirmSave = confirm('Ayarları kaydetmek istediğinizden emin misiniz?');
            if (!confirmSave) {
                e.preventDefault();
            }
        }
    });
    </script>
</div>

<?php
// Eğer settings kaydedildiyse, sayfayı yenile
if (isset($_POST['save_settings'])) {
    echo '<script>
        setTimeout(function() {
            window.location.href = window.location.href.split("&")[0];
        }, 2000);
    </script>';
}
?>        
            <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #2e7d32;">📅 En Eski Veri</h3>
                <p style="font-size: 1.5em; margin: 10px 0; font-weight: bold; color: #2e7d32;">
                    <?php echo $oldest_record ? date('d.m.Y', strtotime($oldest_record)) : 'Veri yok'; ?>
                </p>
                <p style="margin: 0; color: #666;">ilk kayıt tarihi</p>
            </div>
            
            <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #f57c00;">💾 Veritabanı Boyutu</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #f57c00;">
                    <?php echo $database_size ? $database_size . ' MB' : '< 1 MB'; ?>
                </p>
                <p style="margin: 0; color: #666;">disk kullanımı</p>
            </div>
        </div>

        <form method="post" action="" style="border: 2px solid #dc3545; border-radius: 8px; padding: 20px; background: #fff5f5;">
            <?php wp_nonce_field('clear_analytics_data', 'analytics_clear_nonce'); ?>
            
            <h3 style="color: #dc3545; margin-top: 0;">🗑️ Veri Temizleme</h3>
            <p style="color: #666;">
                Eski analitik verilerini temizleyerek veritabanı boyutunu azaltabilirsiniz. 
                <strong>Bu işlem geri alınamaz!</strong>
            </p>
            
            <div style="margin: 15px 0;">
                <label for="clear_days" style="font-weight: bold;">Kaç günden eski veriler silinsin?</label><br>
                <select id="clear_days" name="clear_days" style="margin-top: 5px;">
                    <option value="30">30 günden eski</option>
                    <option value="90">90 günden eski</option>
                    <option value="180">180 günden eski</option>
                    <option value="365">1 yıldan eski</option>
                    <option value="730">2 yıldan eski</option>
                </select>
            </div>
            
            <p>
                <input type="submit" name="clear_data" class="button button-secondary" 
                       value="🗑️ Eski Verileri Temizle" 
                       onclick="return confirm('Seçilen süre aralığındaki tüm veriler silinecek. Emin misiniz?');">
            </p>
        </form>
    </div>

    <!-- API Bilgileri -->
    <div class="analytics-dashboard">
        <h2>🔧 Gelişmiş Kullanım</h2>
        
        <h3>REST API Endpoints:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Anlık İstatistikler:</strong></p>
            <code><?php echo home_url('/wp-json/analytics/v1/stats'); ?></code>
            
            <p style="margin-top: 15px;"><strong>Günlük Veriler:</strong></p>
            <code><?php echo home_url('/wp-json/analytics/v1/daily'); ?></code>
            
            <p style="margin-top: 15px;"><strong>AJAX Endpoint:</strong></p>
            <code><?php echo admin_url('admin-ajax.php?action=get_analytics_stats'); ?></code>
        </div>

        <h3>JavaScript Entegrasyonu:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <code>
// AJAX ile anlık veri alma<br>
fetch('<?php echo admin_url('admin-ajax.php'); ?>', {<br>
&nbsp;&nbsp;&nbsp;&nbsp;method: 'POST',<br>
&nbsp;&nbsp;&nbsp;&nbsp;headers: { 'Content-Type': 'application/x-www-form-urlencoded' },<br>
&nbsp;&nbsp;&nbsp;&nbsp;body: 'action=get_analytics_stats&nonce=NONCE_HERE'<br>
})<br>
.then(response => response.json())<br>
.then(data => console.log(data));
            </code>
        </div>

        <h3>CSS Sınıfları:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                <li><code>.wp-analytics-widget</code> - Ana widget konteyneri</li>
                <li><code>.analytics-item</code> - Her bir istatistik öğesi</li>
                <li><code>.analytics-value</code> - Sayısal değerler</li>
                <li><code>.analytics-label</code> - Açıklama metinleri</li>
                <li><code>.analytics-card</code> - Kart görünümü</li>
            </ul>
        </div>
