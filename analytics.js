/**
 * WordPress Web Analytics JavaScript
 * assets/analytics.js
 */

(function($) {
    'use strict';
    
    // WordPress Analytics Ana Sınıfı
    class WPAnalytics {
        constructor() {
            this.refreshInterval = null;
            this.lastUpdate = null;
            this.isVisible = true;
            this.retryCount = 0;
            this.maxRetries = 3;
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.startAutoRefresh();
            this.handleVisibilityChange();
            this.initWidgets();
            
            // Debug modu
            if (window.location.search.includes('analytics_debug=1')) {
                this.enableDebugMode();
            }
        }
        
        bindEvents() {
            $(document).ready(() => {
                this.onDocumentReady();
            });
            
            // Sayfa değişimlerini izle (SPA'lar için)
            $(window).on('popstate', () => {
                this.trackPageView();
            });
            
            // Scroll tracking (opsiyonel)
            let scrollTimeout;
            $(window).on('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    this.trackScroll();
                }, 1000);
            });
        }
        
        onDocumentReady() {
            console.log('WP Analytics initialized ✅');
            
            // İlk veri yüklemesi
            this.refreshStats();
            
            // Widget'ları güncelle
            this.refreshWidgets();
            
            // Sayfa yüklendiğini kaydet
            this.trackPageView();
        }
        
        // Ana istatistik yenileme fonksiyonu
        refreshStats() {
            if (!wpAnalytics || !wpAnalytics.ajax_url) {
                console.warn('WP Analytics: AJAX URL bulunamadı');
                return;
            }
            
            $.ajax({
                url: wpAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_analytics_stats',
                    nonce: wpAnalytics.nonce
                },
                timeout: 10000,
                success: (response) => {
                    this.handleStatsSuccess(response);
                },
                error: (xhr, status, error) => {
                    this.handleStatsError(xhr, status, error);
                }
            });
        }
        
        handleStatsSuccess(response) {
            this.retryCount = 0; // Başarılı, retry sayacını sıfırla
            
            if (response.success && response.data) {
                this.updateUI(response.data);
                this.lastUpdate = new Date();
                
                // Custom event dispatch
                $(document).trigger('wpAnalytics:statsUpdated', [response.data]);
                
                this.debug('Stats updated successfully', response.data);
            } else {
                this.debug('Invalid response format', response);
            }
        }
        
        handleStatsError(xhr, status, error) {
            this.retryCount++;
            
            if (this.retryCount <= this.maxRetries) {
                // Exponential backoff ile yeniden dene
                const delay = Math.pow(2, this.retryCount) * 1000;
                setTimeout(() => {
                    this.debug(`Retrying stats refresh (${this.retryCount}/${this.maxRetries})`);
                    this.refreshStats();
                }, delay);
            } else {
                this.debug('Max retries reached, stopping auto-refresh', error);
                this.stopAutoRefresh();
            }
        }
        
        // UI güncelleme
        updateUI(data) {
            // Ana sayfa metriklerini güncelle
            this.updateMetricCards(data);
            
            // Widget'ları güncelle
            this.updateWidgets(data);
            
            // Online indicator'ları güncelle
            this.updateOnlineIndicators(data.online);
            
            // Son güncelleme zamanını göster
            this.updateTimestamp();
        }
        
        updateMetricCards(data) {
            $('.analytics-card-value').each(function(index) {
                const $card = $(this);
                const metric = ['online', 'today', 'week', 'month'][index];
                
                if (data[metric] !== undefined) {
                    $card.text(data[metric]);
                    
                    // Animasyon efekti
                    $card.addClass('updated');
                    setTimeout(() => $card.removeClass('updated'), 500);
                }
            });
        }
        
        updateWidgets(data) {
            $('.wp-analytics-widget').each(function() {
                const $widget = $(this);
                
                $widget.find('.analytics-item').each(function() {
                    const $item = $(this);
                    const $value = $item.find('.analytics-value');
                    const $label = $item.find('.analytics-label');
                    
                    const labelText = $label.text().toLowerCase();
                    
                    if (labelText.includes('online') && data.online !== undefined) {
                        $value.text(data.online);
                    } else if (labelText.includes('bugün') && data.today !== undefined) {
                        $value.text(data.today);
                    } else if (labelText.includes('hafta') && data.week !== undefined) {
                        $value.text(data.week);
                    } else if (labelText.includes('ay') && data.month !== undefined) {
                        $value.text(data.month);
                    }
                });
            });
        }
        
        updateOnlineIndicators(onlineCount) {
            $('.analytics-online-indicator').each(function() {
                const $indicator = $(this);
                $indicator.removeClass('pulse-animation');
                
                if (onlineCount > 0) {
                    $indicator.addClass('active pulse-animation');
                } else {
                    $indicator.removeClass('active');
                }
            });
        }
        
        updateTimestamp() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('tr-TR');
            
            $('.last-update, #last-update').text(timeString);
            
            // Relative time güncelleme
            $('.analytics-last-update').text(`Son güncelleme: ${timeString}`);
        }
        
        // Otomatik yenileme
        startAutoRefresh() {
            // Mevcut interval'i temizle
            this.stopAutoRefresh();
            
            // 30 saniyede bir yenile
            this.refreshInterval = setInterval(() => {
                if (this.isVisible) {
                    this.refreshStats();
                }
            }, 30000);
            
            this.debug('Auto-refresh started (30s interval)');
        }
        
        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
                this.debug('Auto-refresh stopped');
            }
        }
        
        // Sayfa görünürlük değişimini handle et
        handleVisibilityChange() {
            document.addEventListener('visibilitychange', () => {
                this.isVisible = !document.hidden;
                
                if (this.isVisible) {
                    // Sayfa görünür olduğunda hemen yenile
                    this.refreshStats();
                    this.debug('Page became visible, refreshing stats');
                } else {
                    this.debug('Page became hidden');
                }
            });
        }
        
        // Widget başlatma
        initWidgets() {
            // Hover efektleri
            $('.analytics-card, .wp-analytics-widget .analytics-item').hover(
                function() {
                    $(this).addClass('hover-effect');
                },
                function() {
                    $(this).removeClass('hover-effect');
                }
            );
            
            // Click to refresh
            $('.analytics-card').on('click', () => {
                this.refreshStats();
            });
        }
        
        refreshWidgets() {
            // Tüm widget'ları yeniden başlat
            $('.wp-analytics-widget').each(function() {
                const $widget = $(this);
                $widget.addClass('loading');
                
                setTimeout(() => {
                    $widget.removeClass('loading');
                }, 1000);
            });
        }
        
        // Sayfa view tracking
        trackPageView() {
            const currentUrl = window.location.href;
            const referrer = document.referrer;
            
            $.ajax({
                url: wpAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'track_page_view',
                    nonce: wpAnalytics.nonce,
                    url: currentUrl,
                    referrer: referrer
                },
                success: (response) => {
                    this.debug('Page view tracked', currentUrl);
                },
                error: (error) => {
                    this.debug('Page view tracking failed', error);
                }
            });
        }
        
        // Scroll tracking
        trackScroll() {
            const scrollPercent = Math.round(
                (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100
            );
            
            // %50 ve %90 scroll noktalarını kaydet
            if (scrollPercent >= 50 && !this.scrollTracked50) {
                this.trackEvent('scroll', '50_percent');
                this.scrollTracked50 = true;
            }
            
            if (scrollPercent >= 90 && !this.scrollTracked90) {
                this.trackEvent('scroll', '90_percent');
                this.scrollTracked90 = true;
            }
        }
        
        // Event tracking
        trackEvent(category, action, label = null, value = null) {
            $.ajax({
                url: wpAnalytics.ajax_url,
                type: 'POST',
                data: {
                    action: 'track_event',
                    nonce: wpAnalytics.nonce,
                    category: category,
                    event_action: action,
                    label: label,
                    value: value
                },
                success: () => {
                    this.debug('Event tracked', { category, action, label, value });
                }
            });
        }
        
        // Public API methods
        getCurrentStats() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: wpAnalytics.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_analytics_stats',
                        nonce: wpAnalytics.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(response);
                        }
                    },
                    error: reject
                });
            });
        }
        
        // Utility methods
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
        
        enableDebugMode() {
            this.debugMode = true;
            console.log('WP Analytics Debug Mode enabled');
            
            // Debug panel oluştur
            const debugPanel = $(`
                <div id="wp-analytics-debug" style="
                    position: fixed;
                    top: 32px;
                    right: 20px;
                    background: rgba(0,0,0,0.9);
                    color: white;
                    padding: 15px;
                    border-radius: 5px;
                    z-index: 9999;
                    font-family: monospace;
                    font-size: 12px;
                    max-width: 300px;
                    max-height: 400px;
                    overflow-y: auto;
                ">
                    <strong>WP Analytics Debug</strong>
                    <div id="debug-content"></div>
                    <button onclick="$('#wp-analytics-debug').remove()" style="
                        background: #dc3545;
                        color: white;
                        border: none;
                        padding: 5px 10px;
                        border-radius: 3px;
                        margin-top: 10px;
                        cursor: pointer;
                    ">Close</button>
                </div>
            `);
            
            $('body').append(debugPanel);
        }
        
        debug(message, data = null) {
            if (this.debugMode || window.location.search.includes('analytics_debug=1')) {
                const timestamp = new Date().toLocaleTimeString();
                console.log(`[WP Analytics ${timestamp}]`, message, data);
                
                // Debug panel'e ekle
                const debugContent = $('#debug-content');
                if (debugContent.length) {
                    debugContent.prepend(`<div>${timestamp}: ${message}</div>`);
                    
                    // Max 20 log entry tut
                    debugContent.children().slice(20).remove();
                }
            }
        }
    }
    
    // Global instance
    window.wpAnalyticsInstance = null;
    
    // jQuery ready
    $(document).ready(function() {
        // Instance oluştur
        if (!window.wpAnalyticsInstance) {
            window.wpAnalyticsInstance = new WPAnalytics();
        }
        
        // Global fonksiyonları expose et
        window.wpAnalytics = {
            refresh: () => window.wpAnalyticsInstance.refreshStats(),
            getCurrentStats: () => window.wpAnalyticsInstance.getCurrentStats(),
            trackEvent: (category, action, label, value) => 
                window.wpAnalyticsInstance.trackEvent(category, action, label, value),
            enableDebug: () => window.wpAnalyticsInstance.enableDebugMode()
        };
    });
    
    // CSS animasyonları
    const styles = `
        <style>
        .analytics-card.updated {
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }
        
        .hover-effect {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .wp-analytics-widget.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .analytics-online-indicator.active {
            background-color: #28a745;
        }
        
        .pulse-animation {
            animation: wp-analytics-pulse 2s infinite;
        }
        
        @keyframes wp-analytics-pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .analytics-updated-indicator {
            position: relative;
        }
        
        .analytics-updated-indicator::after {
            content: '🔄';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 12px;
            animation: spin 1s linear;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>
    `;
    
    $('head').append(styles);
    
})(jQuery);
