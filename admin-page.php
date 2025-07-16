<?php
// WordPress Admin Sayfası - admin/admin-page.php

if (!defined('ABSPATH')) {
    exit;
}

// Orijinal WebStatistics sınıfını kullan
$webStats = new WebStatistics();
$stats = $webStats->getCompactStats();

// Detaylı istatistikler için
global $wpdb;
$table_name = $wpdb->prefix . 'analytics_visitors';

// Son 7 günün verilerini al
$daily_stats = $wpdb->get_results($wpdb->prepare("
    SELECT 
        visit_date,
        COUNT(DISTINCT user_ip) as unique_visitors,
        COUNT(*) as total_visits
    FROM {$table_name} 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY visit_date 
    ORDER BY visit_date DESC
"), ARRAY_A);

// En çok ziyaret edilen sayfalar
$popular_pages = $wpdb->get_results($wpdb->prepare("
    SELECT 
        page_url,
        COUNT(DISTINCT user_ip) as unique_visitors
    FROM {$table_name} 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    AND page_url IS NOT NULL
    GROUP BY page_url 
    ORDER BY unique_visitors DESC 
    LIMIT 10
"), ARRAY_A);

// En aktif IP'ler
$top_ips = $wpdb->get_results($wpdb->prepare("
    SELECT 
        user_ip,
        COUNT(DISTINCT visit_date) as visit_days,
        MIN(visit_date) as first_visit,
        MAX(visit_date) as last_visit
    FROM {$table_name} 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY user_ip 
    ORDER BY visit_days DESC 
    LIMIT 10
"), ARRAY_A);
?>

<div class="wrap wp-analytics-admin">
    <h1>🚀 Web Analitik Dashboard</h1>
    
    <div style="margin: 20px 0;">
        <p>WordPress sitenizin detaylı ziyaretçi analizi ve istatistikleri</p>
    </div>

    <!-- Ana Metrik Kartları -->
    <div class="analytics-cards">
        <div class="analytics-card">
            <div class="analytics-card-icon">🟢</div>
            <div class="analytics-card-value"><?php echo $stats['online']; ?></div>
            <div class="analytics-card-label">Şu Anda Online</div>
        </div>
        
        <div class="analytics-card">
            <div class="analytics-card-icon">📅</div>
            <div class="analytics-card-value"><?php echo $stats['today']; ?></div>
            <div class="analytics-card-label">Bugün Tekil Ziyaretçi</div>
        </div>
        
        <div class="analytics-card">
            <div class="analytics-card-icon">📊</div>
            <div class="analytics-card-value"><?php echo $stats['week']; ?></div>
            <div class="analytics-card-label">Bu Hafta</div>
        </div>
        
        <div class="analytics-card">
            <div class="analytics-card-icon">📈</div>
            <div class="analytics-card-value"><?php echo $stats['month']; ?></div>
            <div class="analytics-card-label">Bu Ay</div>
        </div>
    </div>

    <!-- Shortcode Bilgisi -->
    <div class="analytics-dashboard">
        <h2>📋 Kullanım Kılavuzu</h2>
        
        <h3>Shortcode Kullanımı:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>Basit Widget:</strong></p>
            <code>[analytics_widget]</code>
            
            <p style="margin-top: 15px;"><strong>Özelleştirilmiş Widget:</strong></p>
            <code>[analytics_widget show="online,today" style="modern"]</code>
            
            <p style="margin-top: 15px;"><strong>Tam Dashboard:</strong></p>
            <code>[analytics_dashboard]</code>
            
            <p style="margin-top: 15px;"><strong>Sadece Online Sayısı:</strong></p>
            <code>[analytics_widget show="online" style="minimal"]</code>
        </div>

        <h3>Widget Kullanımı:</h3>
        <p>Görünüm → Widget'lar bölümünden "Web Analitik Widget"i sidebar'ınıza ekleyebilirsiniz.</p>

        <h3>Tema Entegrasyonu:</h3>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p><strong>PHP kodunda kullanım:</strong></p>
            <code>&lt;?php echo do_shortcode('[analytics_widget]'); ?&gt;</code>
            
            <p style="margin-top: 15px;"><strong>Direkt fonksiyon çağrısı:</strong></p>
            <code>
&lt;?php<br>
$webStats = new WP_WebStatistics();<br>
$stats = $webStats->getCompactStats();<br>
echo "Online: " . $stats['online'];<br>
?&gt;
            </code>
        </div>
    </div>

    <!-- Son 7 Günün Trendi -->
    <div class="analytics-dashboard">
        <h2>📈 Son 7 Günün Trendi</h2>
        
        <?php if (!empty($daily_stats)): ?>
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>Tekil Ziyaretçi</th>
                        <th>Gün</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $prev_visitors = 0;
                    foreach (array_reverse($daily_stats) as $index => $day): 
                        $trend = '';
                        if ($index > 0) {
                            $diff = $day['unique_visitors'] - $prev_visitors;
                            if ($diff > 0) {
                                $trend = '<span style="color: #28a745;">↗️ +' . $diff . '</span>';
                            } elseif ($diff < 0) {
                                $trend = '<span style="color: #dc3545;">↘️ ' . $diff . '</span>';
                            } else {
                                $trend = '<span style="color: #6c757d;">➡️ 0</span>';
                            }
                        }
                        $prev_visitors = $day['unique_visitors'];
                    ?>
                        <tr>
                            <td><?php echo date('d.m.Y', strtotime($day['visit_date'])); ?></td>
                            <td><strong><?php echo $day['unique_visitors']; ?></strong></td>
                            <td><?php echo date('l', strtotime($day['visit_date'])); ?></td>
                            <td><?php echo $trend; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px;">
                📊 Henüz yeterli veri bulunmuyor. Plugin aktif olduktan sonra veriler toplanmaya başlayacak.
            </p>
        <?php endif; ?>
    </div>

    <!-- İki Kolon Layout -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
        
        <!-- En Popüler Sayfalar -->
        <div class="analytics-dashboard">
            <h2>📄 En Popüler Sayfalar</h2>
            
            <?php if (!empty($popular_pages)): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>Sayfa</th>
                            <th>Tekil Ziyaretçi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_pages as $page): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($page['page_url']); ?>" target="_blank" style="text-decoration: none;">
                                        <?php 
                                        $url_path = parse_url($page['page_url'], PHP_URL_PATH);
                                        echo $url_path ?: '/';
                                        ?>
                                        <span style="font-size: 12px; color: #666;">🔗</span>
                                    </a>
                                </td>
                                <td><strong><?php echo $page['unique_visitors']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    📄 Henüz sayfa verisi bulunmuyor.
                </p>
            <?php endif; ?>
        </div>

        <!-- En Aktif Ziyaretçiler -->
        <div class="analytics-dashboard">
            <h2>🏆 En Aktif Ziyaretçiler</h2>
            
            <?php if (!empty($top_ips)): ?>
                <table class="analytics-table">
                    <thead>
                        <tr>
                            <th>IP Adresi</th>
                            <th>Ziyaret Günü</th>
                            <th>İlk/Son Ziyaret</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_ips as $ip): ?>
                            <tr>
                                <td>
                                    <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">
                                        <?php echo esc_html($ip['user_ip']); ?>
                                    </code>
                                </td>
                                <td>
                                    <strong><?php echo $ip['visit_days']; ?></strong> gün
                                    <?php if ($ip['visit_days'] >= 7): ?>
                                        <span title="Sadık ziyaretçi">🏆</span>
                                    <?php elseif ($ip['visit_days'] >= 3): ?>
                                        <span title="Düzenli ziyaretçi">⭐</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('d.m', strtotime($ip['first_visit'])); ?> - 
                                        <?php echo date('d.m', strtotime($ip['last_visit'])); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    🏆 Henüz ziyaretçi verisi bulunmuyor.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gerçek Zamanlı Bilgiler -->
    <div class="analytics-dashboard">
        <h2>⚡ Gerçek Zamanlı Bilgiler</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #1976d2;">📊 Bugünkü Performans</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #1976d2;">
                    <?php echo $stats['today']; ?>
                </p>
                <p style="margin: 0; color: #666;">tekil ziyaretçi</p>
            </div>
            
            <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #2e7d32;">🟢 Şu Anda Online</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #2e7d32;">
                    <span class="analytics-online-indicator"></span><?php echo $stats['online']; ?>
                </p>
                <p style="margin: 0; color: #666;">aktif kullanıcı</p>
            </div>
            
            <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #f57c00;">📈 Haftalık Toplam</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #f57c00;">
                    <?php echo $stats['week']; ?>
                </p>
                <p style="margin: 0; color: #666;">bu hafta toplam</p>
            </div>
            
            <div style="background: #fce4ec; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="margin: 0; color: #c2185b;">🗓️ Aylık Toplam</h3>
                <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #c2185b;">
                    <?php echo $stats['month']; ?>
                </p>
                <p style="margin: 0; color: #666;">bu ay toplam</p>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="margin: 30px 0; text-align: center;">
        <a href="<?php echo admin_url('admin.php?page=wp-analytics-settings'); ?>" class="analytics-btn">
            ⚙️ Ayarlar
        </a>
        
        <button onclick="exportAnalyticsData()" class="analytics-btn analytics-btn-secondary" style="margin-left: 10px;">
            📊 Verileri Dışa Aktar
        </button>
        
        <button onclick="refreshAnalyticsData()" class="analytics-btn analytics-btn-secondary" style="margin-left: 10px;">
            🔄 Yenile
        </button>
    </div>

    <!-- JavaScript -->
    <script>
    function exportAnalyticsData() {
        const data = {
            online: <?php echo $stats['online']; ?>,
            today: <?php echo $stats['today']; ?>,
            week: <?php echo $stats['week']; ?>,
            month: <?php echo $stats['month']; ?>,
            export_date: new Date().toISOString()
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'wp-analytics-' + new Date().toISOString().split('T')[0] + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        alert('📊 Analitik verileri başarıyla indirildi!');
    }
    
    function refreshAnalyticsData() {
        location.reload();
    }
    
    // Otomatik yenileme (her 2 dakikada bir)
    setInterval(function() {
        // AJAX ile sadece online sayısını güncelle
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
                // Online sayısını güncelle
                const onlineElements = document.querySelectorAll('.analytics-card-value');
                if (onlineElements[0]) {
                    onlineElements[0].textContent = data.data.online;
                }
            }
        })
        .catch(error => console.log('Auto-refresh error:', error));
    }, 120000); // 2 dakika
    </script>
</div>
