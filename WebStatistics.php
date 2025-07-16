<?php
session_start();

class WebStatistics {
    private $dataDir = 'stats_data/';
    private $onlineFile = 'online_users.txt';
    private $dailyFile = 'daily_stats.txt';
    private $weeklyFile = 'weekly_stats.txt';
    private $monthlyFile = 'monthly_stats.txt';
    private $onlineTimeout = 300; // 5 dakika (saniye)
    
    public function __construct() {
        // Veri klasörünü oluştur
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    // Kullanıcıyı online olarak kaydet
    public function recordUserOnline() {
        $userIP = $this->getUserIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $timestamp = time();
        
        // IP adresini kullanıcı ID'si olarak kullan (tekil sayım için)
        $userId = $userIP;
        
        // Online kullanıcıları oku
        $onlineUsers = $this->getOnlineUsers();
        
        // Kullanıcıyı güncelle veya ekle (IP bazlı tekil sayım)
        $onlineUsers[$userId] = [
            'ip' => $userIP,
            'user_agent' => $userAgent,
            'last_seen' => $timestamp,
            'first_seen' => $onlineUsers[$userId]['first_seen'] ?? $timestamp
        ];
        
        // Eski kullanıcıları temizle
        $onlineUsers = $this->cleanOldUsers($onlineUsers);
        
        // Dosyaya kaydet
        file_put_contents($this->dataDir . $this->onlineFile, serialize($onlineUsers));
        
        // Günlük istatistikleri kaydet (sadece yeni IP ise)
        $this->recordDailyStats();
    }
    
    // Online kullanıcıları getir
    public function getOnlineUsers() {
        $file = $this->dataDir . $this->onlineFile;
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            return is_array($data) ? $data : [];
        }
        return [];
    }
    
    // Eski kullanıcıları temizle
    private function cleanOldUsers($users) {
        $currentTime = time();
        foreach ($users as $userId => $user) {
            if ($currentTime - $user['last_seen'] > $this->onlineTimeout) {
                unset($users[$userId]);
            }
        }
        return $users;
    }
    
    // Kullanıcı IP'sini al
    private function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Günlük istatistikleri kaydet
    private function recordDailyStats() {
        $userIP = $this->getUserIP();
        $date = date('Y-m-d');
        $hour = date('H');
        $statsFile = $this->dataDir . $this->dailyFile;
        $dailyVisitorsFile = $this->dataDir . 'daily_visitors.txt';
        
        // Bugün ziyaret eden IP'leri kontrol et
        $todayVisitors = [];
        if (file_exists($dailyVisitorsFile)) {
            $allVisitors = unserialize(file_get_contents($dailyVisitorsFile));
            $todayVisitors = isset($allVisitors[$date]) ? $allVisitors[$date] : [];
        }
        
        // Bu IP bugün ilk defa mı geliyor?
        $isNewVisitor = !in_array($userIP, $todayVisitors);
        
        if ($isNewVisitor) {
            // IP'yi bugünkü ziyaretçiler listesine ekle
            $todayVisitors[] = $userIP;
            $allVisitors[$date] = $todayVisitors;
            
            // Sadece son 30 günü tut
            $allVisitors = array_slice($allVisitors, -30, null, true);
            file_put_contents($dailyVisitorsFile, serialize($allVisitors));
            
            // Mevcut istatistikleri oku
            $stats = [];
            if (file_exists($statsFile)) {
                $stats = unserialize(file_get_contents($statsFile));
            }
            
            // Bugünkü istatistikleri güncelle (sadece yeni ziyaretçi için)
            if (!isset($stats[$date])) {
                $stats[$date] = ['total' => 0, 'hourly' => []];
            }
            
            if (!isset($stats[$date]['hourly'][$hour])) {
                $stats[$date]['hourly'][$hour] = 0;
            }
            
            $stats[$date]['total']++;
            $stats[$date]['hourly'][$hour]++;
            
            // Sadece son 30 günü tut
            $stats = array_slice($stats, -30, null, true);
            
            file_put_contents($statsFile, serialize($stats));
            
            // Haftalık ve aylık istatistikleri güncelle
            $this->updateWeeklyStats();
            $this->updateMonthlyStats();
        }
    }
    
    // Haftalık istatistikleri güncelle
    private function updateWeeklyStats() {
        $userIP = $this->getUserIP();
        $week = date('Y-W');
        $statsFile = $this->dataDir . $this->weeklyFile;
        $weeklyVisitorsFile = $this->dataDir . 'weekly_visitors.txt';
        
        // Bu haftaki ziyaretçileri kontrol et
        $weekVisitors = [];
        if (file_exists($weeklyVisitorsFile)) {
            $allWeekVisitors = unserialize(file_get_contents($weeklyVisitorsFile));
            $weekVisitors = isset($allWeekVisitors[$week]) ? $allWeekVisitors[$week] : [];
        }
        
        // Bu IP bu hafta ilk defa mı geliyor?
        if (!in_array($userIP, $weekVisitors)) {
            // IP'yi bu haftaki ziyaretçiler listesine ekle
            $weekVisitors[] = $userIP;
            $allWeekVisitors[$week] = $weekVisitors;
            
            // Sadece son 12 haftayı tut
            $allWeekVisitors = array_slice($allWeekVisitors, -12, null, true);
            file_put_contents($weeklyVisitorsFile, serialize($allWeekVisitors));
            
            $stats = [];
            if (file_exists($statsFile)) {
                $stats = unserialize(file_get_contents($statsFile));
            }
            
            if (!isset($stats[$week])) {
                $stats[$week] = 0;
            }
            
            $stats[$week]++;
            
            // Sadece son 12 haftayı tut
            $stats = array_slice($stats, -12, null, true);
            
            file_put_contents($statsFile, serialize($stats));
        }
    }
    
    // Aylık istatistikleri güncelle
    private function updateMonthlyStats() {
        $userIP = $this->getUserIP();
        $month = date('Y-m');
        $statsFile = $this->dataDir . $this->monthlyFile;
        $monthlyVisitorsFile = $this->dataDir . 'monthly_visitors.txt';
        
        // Bu ayki ziyaretçileri kontrol et
        $monthVisitors = [];
        if (file_exists($monthlyVisitorsFile)) {
            $allMonthVisitors = unserialize(file_get_contents($monthlyVisitorsFile));
            $monthVisitors = isset($allMonthVisitors[$month]) ? $allMonthVisitors[$month] : [];
        }
        
        // Bu IP bu ay ilk defa mı geliyor?
        if (!in_array($userIP, $monthVisitors)) {
            // IP'yi bu ayki ziyaretçiler listesine ekle
            $monthVisitors[] = $userIP;
            $allMonthVisitors[$month] = $monthVisitors;
            
            // Sadece son 12 ayı tut
            $allMonthVisitors = array_slice($allMonthVisitors, -12, null, true);
            file_put_contents($monthlyVisitorsFile, serialize($allMonthVisitors));
            
            $stats = [];
            if (file_exists($statsFile)) {
                $stats = unserialize(file_get_contents($statsFile));
            }
            
            if (!isset($stats[$month])) {
                $stats[$month] = 0;
            }
            
            $stats[$month]++;
            
            // Sadece son 12 ayı tut
            $stats = array_slice($stats, -12, null, true);
            
            file_put_contents($statsFile, serialize($stats));
        }
    }
    
    // Günlük istatistikleri getir
    public function getDailyStats() {
        $file = $this->dataDir . $this->dailyFile;
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        }
        return [];
    }
    
    // Haftalık istatistikleri getir
    public function getWeeklyStats() {
        $file = $this->dataDir . $this->weeklyFile;
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        }
        return [];
    }
    
    // Aylık istatistikleri getir
    public function getMonthlyStats() {
        $file = $this->dataDir . $this->monthlyFile;
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        }
        return [];
    }
    
    // Online kullanıcı sayısını getir
    public function getOnlineCount() {
        return count($this->getOnlineUsers());
    }
    
    // Bugünkü toplam ziyareti getir
    public function getTodayVisits() {
        $stats = $this->getDailyStats();
        $today = date('Y-m-d');
        return isset($stats[$today]) ? $stats[$today]['total'] : 0;
    }
    
    // Küçük widget için kompakt istatistikler
    public function getCompactStats() {
        return [
            'online' => $this->getOnlineCount(),
            'today' => $this->getTodayVisits(),
            'week' => array_sum($this->getWeeklyStats()),
            'month' => array_sum($this->getMonthlyStats())
        ];
    }
}
?>
