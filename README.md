# Wordpres-WebStatistics

<img width="385" height="228" alt="image" src="https://github.com/user-attachments/assets/32606def-1394-4831-944b-b19f17a1891e" />

<img width="568" height="303" alt="image" src="https://github.com/user-attachments/assets/5e15fce5-4f90-4ca2-a486-3b95496bf181" />

WordPress WebStatistics entegrasyonu kullanım talimatları:

wp-content/plugins/wp-web-analytics/
├── wp-web-analytics.php (ana plugin dosyası)
├── includes/
│   └── WebStatistics.php
├── admin/
│   ├── admin-page.php
│   └── settings-page.php
├── assets/
│   ├── analytics.js
│   └── analytics.css
└── templates/
    └── dashboard.php




## 📁 **Dosya Yapısı**

```
wp-content/plugins/wp-web-analytics/
├── wp-web-analytics.php (ana plugin dosyası)
├── includes/
│   └── WebStatistics.php
├── admin/
│   ├── admin-page.php
│   └── settings-page.php
├── assets/
│   ├── analytics.js
│   └── analytics.css
└── templates/
    └── dashboard.php
```

## 🚀 **Kurulum Adımları**

### 1. **Plugin Dosyalarını Oluşturun**
- `wp-content/plugins/` klasöründe `wp-web-analytics` klasörü oluşturun
- Yukarıdaki dosya yapısına göre tüm dosyaları yerleştirin

### 2. **Plugin'i Aktive Edin**
```php
// WordPress admin panelinde:
// Eklentiler → Yüklü Eklentiler → "Gelişmiş Web Analitik" → Etkinleştir
```

### 3. **Temel Kullanım**

#### **Shortcode Kullanımı:**
```php
// Basit widget
[analytics_widget]

// Özelleştirilmiş widget
[analytics_widget show="online,today" style="modern"]

// Sadece online sayısı
[analytics_widget show="online" style="minimal"]

// Tam dashboard (admin yetkisi gerekli)
[analytics_dashboard]
```

#### **Tema Entegrasyonu:**
```php
// functions.php veya tema dosyalarında
<?php
if (class_exists('WP_WebStatistics')) {
    $webStats = new WP_WebStatistics();
    $stats = $webStats->getCompactStats();
    echo "Online: " . $stats['online'] . " | Bugün: " . $stats['today'];
}
?>

// Veya shortcode ile
<?php echo do_shortcode('[analytics_widget show="online"]'); ?>
```

#### **Widget Kullanımı:**
```
Görünüm → Widget'lar → "Web Analitik Widget"i sidebar'a sürükleyin
```

## ⚙️ **Özelleştirme Seçenekleri**

### **Shortcode Parametreleri:**
- `show`: `"online"`, `"today"`, `"week"`, `"month"`, `"all"`
- `style`: `"modern"`, `"classic"`, `"minimal"`

### **CSS Sınıfları:**
- `.wp-analytics-widget` - Ana widget
- `.analytics-item` - Her istatistik
- `.analytics-value` - Sayısal değerler
- `.analytics-label` - Etiketler

## 📊 **Admin Paneli**

### **Menü Lokasyonu:**
```
WordPress Admin → Web Analitik
├── İstatistikler (ana dashboard)
└── Ayarlar
```

### **Ayarlar:**
- ✅ Admin kullanıcılarını takip et/etme
- ✅ Giriş yapmış kullanıcıları say/sayma
- ✅ Online timeout süresi
- ✅ Widget ve shortcode'ları aktif/pasif
- ✅ IP hariç tutma
- ✅ Veri saklama süresi

## 🔧 **Gelişmiş Kullanım**

### **JavaScript API:**
```javascript
// Anlık istatistikleri al
wpAnalytics.getCurrentStats().then(data => {
    console.log('Online:', data.online);
});

// Manuel yenileme
wpAnalytics.refresh();

// Event tracking
wpAnalytics.trackEvent('user_interaction', 'button_click', 'header_cta');

// Debug modu
wpAnalytics.enableDebug();
```

### **AJAX Endpoints:**
```javascript
// WordPress AJAX
fetch(ajaxurl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=get_analytics_stats&nonce=NONCE_HERE'
})
.then(response => response.json())
.then(data => console.log(data));
```

### **PHP Hooks:**
```php
// Özel tracking ekleme
add_action('wp_footer', function() {
    if (class_exists('WP_WebStatistics')) {
        $webStats = new WP_WebStatistics();
        $webStats->recordUserOnline();
    }
});

// Şartlı tracking
add_filter('wp_analytics_should_track', function($should_track, $user_id) {
    // VIP kullanıcıları takip etme
    if (user_can($user_id, 'administrator')) {
        return false;
    }
    return $should_track;
}, 10, 2);
```

## 🎨 **Görünüm Özelleştirme**

### **CSS Özelleştirme:**
```css
/* Tema CSS dosyanızda */

/* Modern stil */
.wp-analytics-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
}

/* Minimal stil */
.wp-analytics-minimal {
    background: none;
    border: none;
    padding: 10px 0;
}

/* Classic stil */
.wp-analytics-classic {
    background: white;
    border: 2px solid #ddd;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Online indicator */
.analytics-online-indicator {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
    animation: pulse 2s infinite;
}
```

### **Widget Örnekleri:**
```php
<!-- Header'da küçük widget -->
<div class="site-stats">
    <?php echo do_shortcode('[analytics_widget show="online" style="minimal"]'); ?>
</div>

<!-- Footer'da detaylı widget -->
<div class="footer-analytics">
    <?php echo do_shortcode('[analytics_widget style="classic"]'); ?>
</div>

<!-- Sidebar'da modern widget -->
<?php echo do_shortcode('[analytics_widget style="modern"]'); ?>
```

## 📱 **Responsive Tasarım**

Plugin otomatik olarak responsive'dir. Mobil cihazlarda:
- Widget'lar dikey düzende gösterilir
- Font boyutları otomatik ayarlanır
- Touch-friendly butonlar kullanılır

## 🔒 **Güvenlik Özellikleri**

- ✅ WordPress nonce koruması
- ✅ SQL injection koruması
- ✅ XSS koruması
- ✅ CSRF koruması
- ✅ Capability kontrolleri
- ✅ Input sanitization

## 📈 **Performans**

- ⚡ Hafif veritabanı kullanımı
- 🚀 AJAX ile asenkron güncellemeler
- 💾 Akıllı caching
- 🔄 Otomatik eski veri temizleme
- 📊 Optimize edilmiş sorgular

## 🛠️ **Troubleshooting**

### **Plugin Çalışmıyor:**
```php
// Debug modu açın
add_to_wp_config: define('WP_DEBUG', true);

// URL'ye ekleyin: ?analytics_debug=1

// Konsolu kontrol edin (F12)
```

### **Veriler Görünmüyor:**
1. Plugin aktif mi kontrol edin
2. Veritabanı tabloları oluştu mu: `wp_analytics_visitors`
3. AJAX çalışıyor mu test edin
4. Ayarlardan "Track Admins" açık mı kontrol edin

### **Widget Görünmüyor:**
```php
// functions.php'ye ekleyin:
add_action('widgets_init', function() {
    if (class_exists('WP_Analytics_Widget')) {
        register_widget('WP_Analytics_Widget');
    }
});
```

## 🚀 **Gelişmiş Özellikler**

### **REST API Endpoints:**
```
GET /wp-json/analytics/v1/stats
GET /wp-json/analytics/v1/daily
GET /wp-json/analytics/v1/weekly
POST /wp-json/analytics/v1/track
```

### **Özel Event Tracking:**
```javascript
// Button clicks
jQuery('.important-button').on('click', function() {
    wpAnalytics.trackEvent('engagement', 'button_click', jQuery(this).text());
});

// Form submissions
jQuery('form').on('submit', function() {
    wpAnalytics.trackEvent('conversion', 'form_submit', jQuery(this).attr('id'));
});

// Scroll depth
jQuery(window).on('scroll', function() {
    const scrollPercent = (jQuery(window).scrollTop() / (jQuery(document).height() - jQuery(window).height())) * 100;
    if (scrollPercent > 75) {
        wpAnalytics.trackEvent('engagement', 'scroll_75_percent');
    }
});
```

### **Veri Export:**
```php
// Admin panelinde veri export butonu
// JSON, CSV formatlarında
// Tarih aralığı seçimi
// Otomatik backup
```

## 📊 **Raporlama**

Plugin şu metrikleri takip eder:
- 🟢 Anlık online kullanıcılar
- 📅 Günlük tekil ziyaretçiler
- 📊 Haftalık/aylık trendler
- 🏆 En aktif IP'ler
- 📄 En popüler sayfalar
- ⏰ Saatlik dağılım
- 🌍 Coğrafi analiz (basit)
- 🔄 Tekrar gelen ziyaretçiler

Bu plugin ile WordPress sitenizde profesyonel seviyede analitik takibi yapabilirsiniz. Tüm kodlar WordPress standartlarına uygun olarak hazırlanmıştır ve production ortamında güvenle kullanılabilir.
Plugin **iki farklı yerde** veri tutuyor:

## 📁 **Dosya Tabanlı Veriler** (Orijinal sisteminiz)
```
public_html/stats_data/
├── online_users.txt
├── daily_stats.txt
├── weekly_stats.txt
├── monthly_stats.txt
├── daily_visitors.txt
├── weekly_visitors.txt
└── monthly_visitors.txt
```

## 🗄️ **WordPress Veritabanı** (Plugin eklentisi)
```sql
wp_analytics_visitors tablosu
- Sayfa URL'leri
- Detaylı ziyaretçi kayıtları
- Admin paneli verileri
```

**Ana istatistikler** = `stats_data/` klasöründe (değişiklik yok)
**Ek özellikler** = WordPress veritabanında

Mevcut verileriniz korunur! 💾

