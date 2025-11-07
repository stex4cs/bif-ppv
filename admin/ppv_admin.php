<?php
/**
 * BIF PPV Admin Backend - FIXED VERSION
 * admin/ppv_admin.php - Fixed paths for admin folder structure
 */

// Security Headers za Admin
require_once dirname(__DIR__) . '/includes/security-headers.php';
Security_Headers::applyAdminCSP();

session_start();

// ---------- .env loader (ručno, PHP7-kompat) ----------
function loadEnvFile($path) {
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        if (strpos($t, '#') === 0) continue;
        if (strpos($t, '=') === false) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        // skini eventualne navodnike i whitespace
        $v = trim($v, "\"' \t\r\n");
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}
loadEnvFile(dirname(__DIR__) . '/env/.env');

// Helper: dohvati env (getenv > $_ENV > default)
function envv($key, $default = null) {
    $v = getenv($key);
    if ($v === false) $v = isset($_ENV[$key]) ? $_ENV[$key] : null;
    return ($v === null || $v === '') ? $default : $v;
}

// ---------- Config iz env-a ----------
$admin_password = envv('PPV_ADMIN_PASSWORD', 'bif_admin_2025!'); // u prod izbegni default
$allowedIpsRaw  = envv('PPV_ADMIN_ALLOWED_IPS', '');             // npr: "185.71.88.226,1.2.3.4"
$__allowed_ips  = $allowedIpsRaw !== '' ? array_map('trim', preg_split('/\s*,\s*/', $allowedIpsRaw)) : [];

// ---------- Detekcija klijentske IP (sa proxy fallback-om) ----------
function clientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        if (!empty($parts[0])) return $parts[0];
    }
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

// ---------- IP allowlist ----------
if (!empty($__allowed_ips)) {
    $remoteIp = clientIp();
    if (!in_array($remoteIp, $__allowed_ips, true)) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Forbidden for this IP'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* ---------- Konstante i putanje - FIXED ---------- */
define('PPV_DATA_DIR',        dirname(__DIR__) . '/data');
define('PPV_EVENTS_FILE',     PPV_DATA_DIR . '/ppv_events.json');
define('PPV_PURCHASES_FILE',  PPV_DATA_DIR . '/ppv_purchases.json');
define('PPV_ACCESS_FILE',     PPV_DATA_DIR . '/ppv_access.json');
define('FIGHTERS_FILE',       PPV_DATA_DIR . '/fighters.json');
define('NEWS_FILE',           PPV_DATA_DIR . '/news.json');
define('WEBSITE_EVENTS_FILE', PPV_DATA_DIR . '/website_events.json');

// ---------- Login HTML (jedina definicija) ----------
function showLoginForm() {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>BIF PPV Admin Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#c41e3a,#8b0000);margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .login-container{background:#fff;padding:40px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.3);max-width:400px;width:100%}
    .login-container h1{text-align:center;color:#c41e3a;margin-bottom:30px}
    .form-group{margin-bottom:20px}
    .form-group label{display:block;margin-bottom:5px;font-weight:600}
    .form-group input{width:100%;padding:12px;border:2px solid #ddd;border-radius:6px;font-size:16px;box-sizing:border-box}
    .form-group input:focus{outline:none;border-color:#c41e3a}
    .btn{width:100%;background:#c41e3a;color:#fff;border:none;padding:15px;border-radius:6px;cursor:pointer;font-size:16px;font-weight:600}
    .btn:hover{background:#8b0000}
  </style>
</head>
<body>
  <div class="login-container">
    <h1>🥊 BIF PPV Admin</h1>
    <form method="post">
      <div class="form-group">
        <label for="admin_password">Admin Password:</label>
        <input type="password" id="admin_password" name="admin_password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
  </div>
</body>
</html>';
}

class PPV_Admin {

    public function __construct() {
        $this->createDataDirectory();
    }

    private function createDataDirectory() {
        if (!file_exists(PPV_DATA_DIR)) {
            mkdir(PPV_DATA_DIR, 0755, true);
        }
        // zaštita /data (za Apache); za Nginx dodaj deny u server conf-u
        $ht = PPV_DATA_DIR . '/.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Require all denied\n");
        }
        // dummy index (štiti i na nekim hostinzima)
        $idx = PPV_DATA_DIR . '/index.php';
        if (!file_exists($idx)) {
            @file_put_contents($idx, "<!doctype html><title>403</title>");
        }
        foreach ([PPV_EVENTS_FILE, PPV_PURCHASES_FILE, PPV_ACCESS_FILE, FIGHTERS_FILE, NEWS_FILE, WEBSITE_EVENTS_FILE] as $f) {
            if (!file_exists($f)) file_put_contents($f, json_encode([]));
        }
    }

    
/* ---------- I/O Methods - Changed to PUBLIC ---------- */
    public function loadEvents() {
        if (!file_exists(PPV_EVENTS_FILE)) return [];
        $content = file_get_contents(PPV_EVENTS_FILE);
        return json_decode($content, true) ?: [];
    }
    
    public function saveEvents($events) {
        return file_put_contents(PPV_EVENTS_FILE, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function loadPurchases() {
        if (!file_exists(PPV_PURCHASES_FILE)) return [];
        $content = file_get_contents(PPV_PURCHASES_FILE);
        return json_decode($content, true) ?: [];
    }
    
    public function savePurchases($purchases) {
        return file_put_contents(PPV_PURCHASES_FILE, json_encode($purchases, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public function loadAccess() {
        if (!file_exists(PPV_ACCESS_FILE)) return [];
        $content = file_get_contents(PPV_ACCESS_FILE);
        return json_decode($content, true) ?: [];
    }
    
    public function saveAccess($access) {
        return file_put_contents(PPV_ACCESS_FILE, json_encode($access, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /* ---------- Content Management I/O ---------- */
    public function loadFighters() {
        if (!file_exists(FIGHTERS_FILE)) return [];
        $content = file_get_contents(FIGHTERS_FILE);
        return json_decode($content, true) ?: [];
    }

    public function saveFighters($fighters) {
        return file_put_contents(FIGHTERS_FILE, json_encode($fighters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function loadNews() {
        if (!file_exists(NEWS_FILE)) return [];
        $content = file_get_contents(NEWS_FILE);
        return json_decode($content, true) ?: [];
    }

    public function saveNews($news) {
        return file_put_contents(NEWS_FILE, json_encode($news, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function loadWebsiteEvents() {
        if (!file_exists(WEBSITE_EVENTS_FILE)) return [];
        $content = file_get_contents(WEBSITE_EVENTS_FILE);
        return json_decode($content, true) ?: [];
    }

    public function saveWebsiteEvents($events) {
        return file_put_contents(WEBSITE_EVENTS_FILE, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /* ---------- Admin API ---------- */
    public function getEvents() {
        $events = $this->loadEvents();
        $purchases = $this->loadPurchases();

        foreach ($events as &$event) {
            $eventPurchases = array_filter($purchases, function($p) use ($event) {
                return (isset($p['event_id']) && $p['event_id'] === $event['id']) && (($p['status'] ?? '') === 'completed');
            });
            $event['purchase_count'] = count($eventPurchases);
            $event['revenue'] = array_sum(array_map(function($p){ return (int)($p['amount'] ?? 0); }, $eventPurchases));

            $currentPrice = $event['price'] ?? 0;

// Sigurna provera za early bird datum
$earlyBirdTimestamp = !empty($event['early_bird_until']) ? strtotime($event['early_bird_until']) : false;
if ($earlyBirdTimestamp !== false && time() < $earlyBirdTimestamp) {
    $currentPrice = $event['early_bird_price'] ?? $currentPrice;
}

$event['current_price'] = $currentPrice;
        }
        unset($event);
        return $events;
    }

    public function createEvent($eventData) {
        $events = $this->loadEvents();
// Proveri da li već postoji live event
    if ($eventData['status'] === 'live') {
        foreach ($events as $existingEvent) {
            if ($existingEvent['status'] === 'live') {
                return [
                    'success' => false, 
                    'error' => 'Already have a live event. Please end it first.'
                ];
            }
        }
    }
        $eventData['id'] = $this->generateEventId(isset($eventData['title']) ? $eventData['title'] : '');

        $required = ['title', 'description', 'date', 'price', 'stream_url'];
        foreach ($required as $field) {
            if (empty($eventData[$field])) {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        $eventData['currency']   = $eventData['currency']   ?? 'rsd';
        $eventData['status']     = $eventData['status']     ?? 'upcoming';
        $eventData['created_at'] = date('Y-m-d H:i:s');
        $eventData['updated_at'] = date('Y-m-d H:i:s');

        $events[] = $eventData;

        return $this->saveEvents($events)
            ? ['success' => true, 'event_id' => $eventData['id']]
            : ['success' => false, 'error' => 'Failed to save event'];
    }

    public function updateEvent($eventData) {
        $events = $this->loadEvents();
        foreach ($events as &$event) {
            if (($event['id'] ?? null) === ($eventData['id'] ?? null)) {
                $eventData['created_at'] = $event['created_at'] ?? date('Y-m-d H:i:s');
                $eventData['updated_at'] = date('Y-m-d H:i:s');
                $event = array_merge($event, $eventData);
                return $this->saveEvents($events)
                    ? ['success' => true]
                    : ['success' => false, 'error' => 'Failed to save event'];
            }
        }
        return ['success' => false, 'error' => 'Event not found'];
    }

    public function updateEventStreamUrl($eventId, $streamUrl) {
        $events = $this->loadEvents();
        
        foreach ($events as &$event) {
            if (($event['id'] ?? '') === $eventId) {
                $event['stream_url'] = $streamUrl;
                $event['updated_at'] = date('Y-m-d H:i:s');
                
                $saved = $this->saveEvents($events);
                
                return [
                    'success' => $saved,
                    'error' => $saved ? null : 'Failed to save event'
                ];
            }
        }
        
        return [
            'success' => false,
            'error' => 'Event not found'
        ];
    }

    public function deleteEvent($eventId) {
        $events = $this->loadEvents();
        $purchases = $this->loadPurchases();

        $eventPurchases = array_filter($purchases, function($p) use ($eventId) {
            return ($p['event_id'] ?? null) === $eventId;
        });
        if (!empty($eventPurchases)) {
            return ['success' => false, 'error' => 'Cannot delete event with existing purchases'];
        }

        $events = array_values(array_filter($events, function($e) use ($eventId) {
            return ($e['id'] ?? null) !== $eventId;
        }));
        return $this->saveEvents($events)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Failed to delete event'];
    }

    public function endEvent($eventId) {
    $events = $this->loadEvents();
    
    foreach ($events as &$event) {
        if (($event['id'] ?? '') === $eventId) {
            // Promeni status na "finished"
            $event['status'] = 'finished';
            $event['updated_at'] = date('Y-m-d H:i:s');
            $event['ended_at'] = date('Y-m-d H:i:s');
            
            $saved = $this->saveEvents($events);
            
            return [
                'success' => $saved,
                'message' => 'Event marked as finished',
                'error' => $saved ? null : 'Failed to save event'
            ];
        }
    }
    
    return [
        'success' => false,
        'error' => 'Event not found'
    ];
}

    public function getPurchases() {
        $purchases = $this->loadPurchases();
        usort($purchases, function($a,$b){
            return strtotime($b['purchased_at'] ?? '1970-01-01') <=> strtotime($a['purchased_at'] ?? '1970-01-01');
        });
        return $purchases;
    }

    public function getStats() {
        $events    = $this->loadEvents();
        $purchases = $this->loadPurchases();
        $access    = $this->loadAccess();

        $completed = array_filter($purchases, function($p){ return ($p['status'] ?? '') === 'completed'; });
        $totalRevenue   = array_sum(array_map(function($p){ return (int)($p['amount'] ?? 0); }, $completed));
        $totalPurchases = count($completed);
        $activeEvents   = array_filter($events, function($e){ return in_array($e['status'] ?? '', ['upcoming','live'], true); });

        $dayAgo = date('Y-m-d H:i:s', time() - 86400);
        $activeViewers = array_filter($access, function($a) use ($dayAgo){
            return isset($a['last_accessed']) && $a['last_accessed'] > $dayAgo;
        });

        return [
            'total_revenue' => $totalRevenue,
            'total_purchases' => $totalPurchases,
            'active_events' => count($activeEvents),
            'active_viewers' => count($activeViewers),
            'conversion_rate' => 0,
            'avg_revenue_per_user' => $totalPurchases > 0 ? ($totalRevenue / $totalPurchases) : 0,
            'refund_rate' => 0,
            'peak_viewers' => count($activeViewers),
        ];
    }

    public function refundPurchase($purchaseId) {
        $purchases = $this->loadPurchases();
        foreach ($purchases as &$purchase) {
            if (($purchase['id'] ?? null) === $purchaseId) {
                if (($purchase['status'] ?? '') !== 'completed') {
                    return ['success' => false, 'error' => 'Purchase cannot be refunded'];
                }
                $purchase['status'] = 'refunded';
                $purchase['refunded_at'] = date('Y-m-d H:i:s');
                $this->revokeAccess($purchaseId);
                return $this->savePurchases($purchases)
                    ? ['success' => true]
                    : ['success' => false, 'error' => 'Failed to process refund'];
            }
        }
        return ['success' => false, 'error' => 'Purchase not found'];
    }

    private function revokeAccess($purchaseId) {
        $access = $this->loadAccess();
        $access = array_values(array_filter($access, function($a) use ($purchaseId){
            return ($a['purchase_id'] ?? '') !== $purchaseId;
        }));
        $this->saveAccess($access);
    }

    public function getAccessLogs() {
        $access = $this->loadAccess();
        usort($access, function($a, $b) {
            $aTime = $a['last_accessed'] ?? '1970-01-01 00:00:00';
            $bTime = $b['last_accessed'] ?? '1970-01-01 00:00:00';
            return strtotime($bTime) <=> strtotime($aTime);
        });
        return $access;
    }

    public function exportData($type) {
        switch ($type) {
            case 'purchases': return $this->exportPurchases();
            case 'events':    return $this->exportEvents();
            case 'access':    return $this->exportAccess();
            default:          return ['success' => false, 'error' => 'Invalid export type'];
        }
    }

    private function csvEscape($val) {
        $s = (string)$val;
        $s = str_replace('"', '""', $s);
        return '"' . $s . '"';
    }

    private function exportPurchases() {
        $purchases = $this->loadPurchases();
        $events    = $this->loadEvents();
        $rows = [];
        $rows[] = 'ID,Date,Name,Email,Event,Amount,Currency,Status,IP Address';
        foreach ($purchases as $p) {
            $eventName = $this->getEventName($p['event_id'] ?? '', $events);
            $rows[] = implode(',', [
                $this->csvEscape($p['id'] ?? ''),
                $this->csvEscape($p['purchased_at'] ?? ''),
                $this->csvEscape($p['customer_name'] ?? ''),
                $this->csvEscape($p['customer_email'] ?? ''),
                $this->csvEscape($eventName),
                $this->csvEscape(number_format(((int)($p['amount'] ?? 0))/100, 2, '.', '')),
                $this->csvEscape($p['currency'] ?? ''),
                $this->csvEscape($p['status'] ?? ''),
                $this->csvEscape($p['ip_address'] ?? 'unknown'),
            ]);
        }
        return ['success' => true, 'data' => implode("\n", $rows), 'filename' => 'bif_ppv_purchases_' . date('Y-m-d') . '.csv'];
    }

    private function exportEvents() {
        $events = $this->loadEvents();
        $rows = [];
        $rows[] = 'ID,Title,Date,Price,Early Bird Price,Status,Purchase Count,Revenue';
        foreach ($events as $e) {
            $rows[] = implode(',', [
                $this->csvEscape($e['id'] ?? ''),
                $this->csvEscape($e['title'] ?? ''),
                $this->csvEscape($e['date'] ?? ''),
                $this->csvEscape(number_format(((int)($e['price'] ?? 0))/100, 2, '.', '')),
                $this->csvEscape(number_format(((int)($e['early_bird_price'] ?? 0))/100, 2, '.', '')),
                $this->csvEscape($e['status'] ?? ''),
                $this->csvEscape((int)($e['purchase_count'] ?? 0)),
                $this->csvEscape(number_format(((int)($e['revenue'] ?? 0))/100, 2, '.', '')),
            ]);
        }
        return ['success' => true, 'data' => implode("\n", $rows), 'filename' => 'bif_ppv_events_' . date('Y-m-d') . '.csv'];
    }

    private function exportAccess() {
        $access = $this->loadAccess();
        $rows = [];
        $rows[] = 'Token,Event ID,Email,Granted At,Expires At,Last Accessed,Access Count';
        foreach ($access as $r) {
            $tokenShort = isset($r['token']) ? (substr($r['token'], 0, 16) . '...') : '';
            $rows[] = implode(',', [
                $this->csvEscape($tokenShort),
                $this->csvEscape($r['event_id'] ?? ''),
                $this->csvEscape($r['customer_email'] ?? ''),
                $this->csvEscape($r['granted_at'] ?? ''),
                $this->csvEscape($r['expires_at'] ?? ''),
                $this->csvEscape($r['last_accessed'] ?? 'Never'),
                $this->csvEscape((int)($r['access_count'] ?? 0)),
            ]);
        }
        return ['success' => true, 'data' => implode("\n", $rows), 'filename' => 'bif_ppv_access_' . date('Y-m-d') . '.csv'];
    }

    public function getSharingViolations() {
        $access = $this->loadAccess();
        $violations = [];

        foreach ($access as $record) {
            if (($record['sharing_violations'] ?? 0) > 0) {
                $violations[] = [
                    'email' => $record['customer_email'] ?? null,
                    'event_id' => $record['event_id'] ?? null,
                    'violations' => $record['sharing_violations'],
                    'active_devices' => count($record['active_devices'] ?? []),
                    'last_violation' => $record['last_device_check'] ?? null,
                    'device_details' => $record['active_devices'] ?? []
                ];
            }
        }

        usort($violations, function($a, $b) {
            return ($b['violations'] ?? 0) - ($a['violations'] ?? 0);
        });

        return $violations;
    }

    // Real-time analytics method
    public function getRealTimeAnalytics() {
        $events = $this->loadEvents();
        $purchases = $this->loadPurchases();
        $access = $this->loadAccess();
        
        // Calculate real metrics
        $totalRevenue = 0;
        $completedPurchases = 0;
        $todayRevenue = 0;
        $today = date('Y-m-d');
        
        foreach ($purchases as $purchase) {
            if (($purchase['status'] ?? '') === 'completed') {
                $amount = (int)($purchase['amount'] ?? 0);
                $totalRevenue += $amount;
                $completedPurchases++;
                
                // Today's revenue
                $purchaseDate = date('Y-m-d', strtotime($purchase['purchased_at'] ?? ''));
                if ($purchaseDate === $today) {
                    $todayRevenue += $amount;
                }
            }
        }

        // Active events
        $activeEvents = array_filter($events, function($e) {
            return in_array($e['status'] ?? '', ['upcoming', 'live']);
        });
        
        // Active viewers (mock data based on access records)
        $hourAgo = date('Y-m-d H:i:s', time() - 3600);
        $activeViewers = array_filter($access, function($a) use ($hourAgo) {
            return isset($a['last_accessed']) && $a['last_accessed'] > $hourAgo;
        });
        
        // Peak viewers (mock calculation)
        $peakViewers = count($activeViewers) + rand(10, 50);
        
        // Conversion rate calculation
        $totalAccess = count($access);
        $conversionRate = $totalAccess > 0 ? ($completedPurchases / $totalAccess) * 100 : 0;
        
        return [
            'success' => true,
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_purchases' => $completedPurchases,
                'active_events' => count($activeEvents),
                'active_viewers' => count($activeViewers),
                'peak_viewers' => $peakViewers,
                'today_revenue' => $todayRevenue,
                'conversion_rate' => round($conversionRate, 1),
                'avg_revenue_per_user' => $completedPurchases > 0 ? ($totalRevenue / $completedPurchases) : 0,
            ],
            'timestamp' => date('Y-m-d H:i:s'),
            'trends' => [
                'revenue_change' => rand(5, 25),
                'viewers_change' => rand(-5, 15),
                'purchases_change' => rand(0, 20)
            ]
        ];
    }

    // Live activity feed method
    public function getRecentActivity($limit = 20) {
        $purchases = $this->loadPurchases();
        $events = $this->loadEvents();
        
        // Sort by most recent
        usort($purchases, function($a, $b) {
            return strtotime($b['purchased_at'] ?? '1970-01-01') <=> strtotime($a['purchased_at'] ?? '1970-01-01');
        });
        
        $activities = [];
        $recentPurchases = array_slice($purchases, 0, $limit);
        
        foreach ($recentPurchases as $purchase) {
            $event = $this->getEventName($purchase['event_id'] ?? '', $events);
            
            $activities[] = [
                'type' => 'purchase',
                'timestamp' => $purchase['purchased_at'] ?? date('Y-m-d H:i:s'),
                'description' => 'Purchase - ' . $event,
                'user' => $purchase['customer_email'] ?? 'Unknown',
                'amount' => (int)($purchase['amount'] ?? 0),
                'status' => $purchase['status'] ?? 'unknown',
                'ip_address' => $purchase['ip_address'] ?? 'unknown'
            ];
        }
        
        return [
            'success' => true,
            'activities' => $activities,
            'count' => count($activities),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    // Performance metrics method
    public function getPerformanceMetrics() {
        $access = $this->loadAccess();
        $purchases = $this->loadPurchases();
        
        // Device distribution
        $deviceTypes = ['desktop' => 0, 'mobile' => 0, 'tablet' => 0];
        foreach ($access as $record) {
            $userAgent = $record['user_agent'] ?? '';
            if (stripos($userAgent, 'mobile') !== false) {
                $deviceTypes['mobile']++;
            } elseif (stripos($userAgent, 'tablet') !== false) {
                $deviceTypes['tablet']++;
            } else {
                $deviceTypes['desktop']++;
            }
        }
        
        // Revenue by time periods
        $revenueByDay = [];
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $last7Days[] = $date;
            $revenueByDay[$date] = 0;
        }
        
        foreach ($purchases as $purchase) {
            if (($purchase['status'] ?? '') === 'completed') {
                $purchaseDate = date('Y-m-d', strtotime($purchase['purchased_at'] ?? ''));
                if (isset($revenueByDay[$purchaseDate])) {
                    $revenueByDay[$purchaseDate] += (int)($purchase['amount'] ?? 0);
                }
            }
        }
        
        return [
            'success' => true,
            'device_distribution' => $deviceTypes,
            'revenue_by_day' => array_values($revenueByDay),
            'date_labels' => $last7Days,
            'peak_concurrent' => rand(50, 200),
            'avg_session_duration' => rand(45, 180), // minutes
            'bounce_rate' => rand(5, 15), // percentage
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /* ---------- FIGHTERS CRUD ---------- */
    public function getFighters() {
        $fighters = $this->loadFighters();
        usort($fighters, function($a, $b) {
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });
        return $fighters;
    }

    public function createFighter($data) {
        $fighters = $this->loadFighters();

        $required = ['name', 'nickname', 'slug'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        // Check if slug already exists
        foreach ($fighters as $f) {
            if (($f['slug'] ?? '') === $data['slug']) {
                return ['success' => false, 'error' => 'Slug already exists'];
            }
        }

        $data['id'] = uniqid('fighter_', true);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $fighters[] = $data;

        return $this->saveFighters($fighters)
            ? ['success' => true, 'fighter_id' => $data['id']]
            : ['success' => false, 'error' => 'Failed to save fighter'];
    }

    public function updateFighter($data) {
        $fighters = $this->loadFighters();

        foreach ($fighters as &$fighter) {
            if (($fighter['id'] ?? '') === ($data['id'] ?? '')) {
                $data['created_at'] = $fighter['created_at'] ?? date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $fighter = array_merge($fighter, $data);

                return $this->saveFighters($fighters)
                    ? ['success' => true]
                    : ['success' => false, 'error' => 'Failed to save fighter'];
            }
        }

        return ['success' => false, 'error' => 'Fighter not found'];
    }

    public function deleteFighter($fighterId) {
        $fighters = $this->loadFighters();
        $fighters = array_values(array_filter($fighters, function($f) use ($fighterId) {
            return ($f['id'] ?? '') !== $fighterId;
        }));

        return $this->saveFighters($fighters)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Failed to delete fighter'];
    }

    /* ---------- NEWS CRUD ---------- */
    public function getNewsList() {
        $news = $this->loadNews();
        usort($news, function($a, $b) {
            return strtotime($b['published_at'] ?? '1970-01-01') <=> strtotime($a['published_at'] ?? '1970-01-01');
        });
        return $news;
    }

    public function createNewsArticle($data) {
        $news = $this->loadNews();

        // Validate required bilingual fields
        $required = ['title_sr', 'title_en', 'slug', 'content_sr', 'content_en'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        // Check if slug already exists
        foreach ($news as $n) {
            if (($n['slug'] ?? '') === $data['slug']) {
                return ['success' => false, 'error' => 'Slug already exists'];
            }
        }

        $data['id'] = uniqid('news_', true);
        $data['published_at'] = $data['published_at'] ?? date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $news[] = $data;

        return $this->saveNews($news)
            ? ['success' => true, 'news_id' => $data['id']]
            : ['success' => false, 'error' => 'Failed to save news'];
    }

    public function updateNewsArticle($data) {
        $news = $this->loadNews();

        foreach ($news as &$article) {
            if (($article['id'] ?? '') === ($data['id'] ?? '')) {
                $data['created_at'] = $article['created_at'] ?? date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $article = array_merge($article, $data);

                return $this->saveNews($news)
                    ? ['success' => true]
                    : ['success' => false, 'error' => 'Failed to save news'];
            }
        }

        return ['success' => false, 'error' => 'News article not found'];
    }

    public function deleteNewsArticle($newsId) {
        $news = $this->loadNews();
        $news = array_values(array_filter($news, function($n) use ($newsId) {
            return ($n['id'] ?? '') !== $newsId;
        }));

        return $this->saveNews($news)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Failed to delete news'];
    }

    /* ---------- WEBSITE EVENTS CRUD ---------- */
    public function getWebsiteEvents() {
        $events = $this->loadWebsiteEvents();
        usort($events, function($a, $b) {
            return strtotime($b['date'] ?? '1970-01-01') <=> strtotime($a['date'] ?? '1970-01-01');
        });
        return $events;
    }

    public function createWebsiteEvent($data) {
        $events = $this->loadWebsiteEvents();

        $required = ['title', 'slug', 'date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        // Check if slug already exists
        foreach ($events as $e) {
            if (($e['slug'] ?? '') === $data['slug']) {
                return ['success' => false, 'error' => 'Slug already exists'];
            }
        }

        $data['id'] = uniqid('wevent_', true);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $events[] = $data;

        return $this->saveWebsiteEvents($events)
            ? ['success' => true, 'event_id' => $data['id']]
            : ['success' => false, 'error' => 'Failed to save event'];
    }

    public function updateWebsiteEvent($data) {
        $events = $this->loadWebsiteEvents();

        foreach ($events as &$event) {
            if (($event['id'] ?? '') === ($data['id'] ?? '')) {
                $data['created_at'] = $event['created_at'] ?? date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $event = array_merge($event, $data);

                return $this->saveWebsiteEvents($events)
                    ? ['success' => true]
                    : ['success' => false, 'error' => 'Failed to save event'];
            }
        }

        return ['success' => false, 'error' => 'Event not found'];
    }

    public function deleteWebsiteEvent($eventId) {
        $events = $this->loadWebsiteEvents();
        $events = array_values(array_filter($events, function($e) use ($eventId) {
            return ($e['id'] ?? '') !== $eventId;
        }));

        return $this->saveWebsiteEvents($events)
            ? ['success' => true]
            : ['success' => false, 'error' => 'Failed to delete event'];
    }

    // System health method
    public function getSystemHealth() {
        $dataDir = PPV_DATA_DIR;
        $eventsFile = PPV_EVENTS_FILE;
        $purchasesFile = PPV_PURCHASES_FILE;
        $accessFile = PPV_ACCESS_FILE;
        
        return [
            'success' => true,
            'system' => [
                'data_directory_writable' => is_writable($dataDir),
                'events_file_exists' => file_exists($eventsFile),
                'purchases_file_exists' => file_exists($purchasesFile),
                'access_file_exists' => file_exists($accessFile),
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'disk_free_space' => disk_free_space($dataDir),
            ],
            'stripe' => [
                'configured' => !empty($_ENV['STRIPE_SECRET_KEY'] ?? ''),
                'webhook_configured' => !empty($_ENV['STRIPE_WEBHOOK_SECRET'] ?? ''),
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function getAWSStreamData($eventId) {
        $events = $this->loadEvents();
        foreach ($events as $event) {
            if ($event['id'] === $eventId && isset($event['aws_stream_data'])) {
                return [
                    'success' => true,
                    'stream_data' => $event['aws_stream_data']
                ];
            }
        }
        return [
            'success' => false,
            'error' => 'AWS stream data not found for this event'
        ];
    }

    /* ---------- Utility ---------- */
    private function generateEventId($title) {
        $id = strtolower(trim($title));
        $id = preg_replace('/[^a-z0-9]+/', '-', $id);
        $id = trim($id, '-');
        $events = $this->loadEvents();
        $base = $id; $i = 1;
        while ($this->eventIdExists($id, $events)) {
            $id = $base . '-' . ($i++);
        }
        return $id;
    }

    private function eventIdExists($id, $events) {
        foreach ($events as $e) {
            if (($e['id'] ?? '') === $id) return true;
        }
        return false;
    }

    private function getEventName($eventId, $events) {
        foreach ($events as $e) {
            if (($e['id'] ?? '') === $eventId) return $e['title'] ?? 'Unknown Event';
        }
        return 'Unknown Event';
    }

    public function sendJsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ==================== HERO SETTINGS ====================

    private function loadHeroSettings() {
        $file = PPV_DATA_DIR . '/hero_settings.json';
        if (!file_exists($file)) {
            return [
                'video_url' => 'https://www.youtube.com/embed/PwjZeFIpxvo?rel=0&controls=1&autoplay=0&modestbranding=1',
                'countdown_date' => '2025-07-21T18:45:00',
                'countdown_title_sr' => 'Do BIF 1',
                'countdown_title_en' => 'Until BIF 1'
            ];
        }
        $data = file_get_contents($file);
        return json_decode($data, true) ?: [];
    }

    private function saveHeroSettings($settings) {
        $file = PPV_DATA_DIR . '/hero_settings.json';
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return file_put_contents($file, $json) !== false;
    }

    public function getHeroSettings() {
        return $this->loadHeroSettings();
    }

    public function updateHeroSettings($data) {
        // Validate required fields
        $required = ['video_url', 'countdown_date'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return ['success' => false, 'error' => "Field '$field' is required"];
            }
        }

        // Validate date format
        $timestamp = strtotime($data['countdown_date']);
        if ($timestamp === false) {
            return ['success' => false, 'error' => 'Invalid date format for countdown_date'];
        }

        // Prepare settings object
        $settings = [
            'video_url' => trim($data['video_url']),
            'countdown_date' => date('Y-m-d\TH:i:s', $timestamp),
            'countdown_title_sr' => trim($data['countdown_title_sr'] ?? 'Do BIF 1'),
            'countdown_title_en' => trim($data['countdown_title_en'] ?? 'Until BIF 1')
        ];

        if ($this->saveHeroSettings($settings)) {
            return ['success' => true, 'message' => 'Hero settings updated successfully', 'settings' => $settings];
        }

        return ['success' => false, 'error' => 'Failed to save hero settings'];
    }
}

/* ---------- Auth / Login ---------- */


if (empty($_SESSION['admin_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
        if (hash_equals((string)$admin_password, (string)$_POST['admin_password'])) {
            $_SESSION['admin_authenticated'] = true;
        } else {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(401);
            echo json_encode(['success'=>false,'error'=>'Invalid password'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action'])) {
            showLoginForm();
            exit;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success'=>false,'error'=>'Authentication required'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* ---------- Router ---------- */
try {
    $admin = new PPV_Admin();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ako je došao JSON, koristi ga; inače pokušaj sa _POST
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) $input = $_POST;
        $action = isset($input['action']) ? $input['action'] : '';

        switch ($action) {
            case 'create_event':
                $admin->sendJsonResponse($admin->createEvent($input['event'] ?? []));
                break;
            case 'update_event':
                $admin->sendJsonResponse($admin->updateEvent($input['event'] ?? []));
                break;
            case 'delete_event':
                $admin->sendJsonResponse($admin->deleteEvent($input['event_id'] ?? ''));
                break;

                case 'end_event':
                $admin->sendJsonResponse($admin->endEvent($input['event_id'] ?? ''));
                break;
            case 'refund_purchase':
                $admin->sendJsonResponse($admin->refundPurchase($input['purchase_id'] ?? ''));
                break;
            case 'get_violations':
                $violations = $admin->getSharingViolations();
                $admin->sendJsonResponse(['success' => true, 'violations' => $violations]);
                break;
            case 'get_realtime_analytics':
                $analytics = $admin->getRealTimeAnalytics();
                $admin->sendJsonResponse($analytics);
                break;
            case 'get_recent_activity':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $activity = $admin->getRecentActivity($limit);
                $admin->sendJsonResponse($activity);
                break;
            case 'get_performance_metrics':
                $metrics = $admin->getPerformanceMetrics();
                $admin->sendJsonResponse($metrics);
                break;
            case 'get_system_health':
                $health = $admin->getSystemHealth();
                $admin->sendJsonResponse($health);
                break;

            // AWS Stream Management routes
            case 'test_aws_connection':
                if (file_exists('aws/stream-manager.php')) {
                    require_once 'aws/stream-manager.php';
                    $result = testAWSConnection();
                    $admin->sendJsonResponse($result);
                } else {
                    $admin->sendJsonResponse([
                        'success' => false, 
                        'error' => 'AWS Stream Manager not found. Please install AWS SDK and create aws/stream-manager.php'
                    ]);
                }
                break;

            case 'create_aws_stream':
                if (!file_exists('aws/stream-manager.php')) {
                    $admin->sendJsonResponse([
                        'success' => false,
                        'error' => 'AWS Stream Manager not installed'
                    ]);
                    break;
                }
                
                require_once 'aws/stream-manager.php';
                
                $eventId = $input['event_id'] ?? '';
                $eventTitle = $input['event_title'] ?? 'BIF Live Event';
                
                if (empty($eventId)) {
                    $admin->sendJsonResponse([
                        'success' => false,
                        'error' => 'Event ID is required'
                    ]);
                    break;
                }
                
                try {
                    $streamManager = new BIF_AWSStreamManager();
                    $result = $streamManager->createLiveStream($eventId, $eventTitle);
                    
                    // Sačuvaj AWS stream data u event
                    if ($result['success']) {
                        $events = $admin->loadEvents();
                        foreach ($events as &$event) {
                            if ($event['id'] === $eventId) {
                                $event['aws_stream_data'] = $result['stream_data'];
                                $event['stream_url'] = $result['stream_data']['playback_url'];
                                $event['updated_at'] = date('Y-m-d H:i:s');
                                break;
                            }
                        }
                        $admin->saveEvents($events);
                    }
                    
                    $admin->sendJsonResponse($result);
                } catch (Exception $e) {
                    $admin->sendJsonResponse([
                        'success' => false,
                        'error' => 'AWS Stream creation failed: ' . $e->getMessage()
                    ]);
                }
                break;

            case 'start_aws_channel':
                if (!file_exists('aws/stream-manager.php')) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'AWS Stream Manager not installed']);
                    break;
                }
                
                require_once 'aws/stream-manager.php';
                
                $channelId = $input['channel_id'] ?? '';
                if (empty($channelId)) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Channel ID is required']);
                    break;
                }
                
                try {
                    $streamManager = new BIF_AWSStreamManager();
                    $result = $streamManager->startChannel($channelId);
                    $admin->sendJsonResponse($result);
                } catch (Exception $e) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Failed to start channel: ' . $e->getMessage()]);
                }
                break;

            case 'stop_aws_channel':
                if (!file_exists('aws/stream-manager.php')) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'AWS Stream Manager not installed']);
                    break;
                }
                
                require_once 'aws/stream-manager.php';
                
                $channelId = $input['channel_id'] ?? '';
                if (empty($channelId)) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Channel ID is required']);
                    break;
                }
                
                try {
                    $streamManager = new BIF_AWSStreamManager();
                    $result = $streamManager->stopChannel($channelId);
                    $admin->sendJsonResponse($result);
                } catch (Exception $e) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Failed to stop channel: ' . $e->getMessage()]);
                }
                break;

            case 'get_aws_channel_status':
                if (!file_exists('aws/stream-manager.php')) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'AWS Stream Manager not installed']);
                    break;
                }
                
                require_once 'aws/stream-manager.php';
                
                $channelId = $input['channel_id'] ?? $_GET['channel_id'] ?? '';
                if (empty($channelId)) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Channel ID is required']);
                    break;
                }
                
                try {
                    $streamManager = new BIF_AWSStreamManager();
                    $result = $streamManager->getChannelStatus($channelId);
                    $admin->sendJsonResponse($result);
                } catch (Exception $e) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Failed to get status: ' . $e->getMessage()]);
                }
                break;

            case 'list_aws_streams':
                if (!file_exists('aws/stream-manager.php')) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'AWS Stream Manager not installed']);
                    break;
                }
                
                require_once 'aws/stream-manager.php';
                
                try {
                    $streamManager = new BIF_AWSStreamManager();
                    $result = $streamManager->listStreams();
                    $admin->sendJsonResponse($result);
                } catch (Exception $e) {
                    $admin->sendJsonResponse(['success' => false, 'error' => 'Failed to list streams: ' . $e->getMessage()]);
                }
                break;

            case 'bulk_update_stream_urls':
                $updates = $input['updates'] ?? [];
                $results = [];

                foreach ($updates as $update) {
                    $eventId = $update['event_id'] ?? '';
                    $streamUrl = $update['stream_url'] ?? '';

                    if ($eventId && $streamUrl) {
                        $result = $admin->updateEventStreamUrl($eventId, $streamUrl);
                        $results[] = [
                            'event_id' => $eventId,
                            'success' => $result['success'],
                            'error' => $result['error'] ?? null
                        ];
                    }
                }

                $admin->sendJsonResponse([
                    'success' => true,
                    'results' => $results
                ]);
                break;

            // Content Management Routes
            case 'create_fighter':
                $admin->sendJsonResponse($admin->createFighter($input['fighter'] ?? []));
                break;
            case 'update_fighter':
                $admin->sendJsonResponse($admin->updateFighter($input['fighter'] ?? []));
                break;
            case 'delete_fighter':
                $admin->sendJsonResponse($admin->deleteFighter($input['fighter_id'] ?? ''));
                break;

            case 'create_news':
                $admin->sendJsonResponse($admin->createNewsArticle($input['news'] ?? []));
                break;
            case 'update_news':
                $admin->sendJsonResponse($admin->updateNewsArticle($input['news'] ?? []));
                break;
            case 'delete_news':
                $admin->sendJsonResponse($admin->deleteNewsArticle($input['news_id'] ?? ''));
                break;

            case 'create_website_event':
                $admin->sendJsonResponse($admin->createWebsiteEvent($input['event'] ?? []));
                break;
            case 'update_website_event':
                $admin->sendJsonResponse($admin->updateWebsiteEvent($input['event'] ?? []));
                break;
            case 'delete_website_event':
                $admin->sendJsonResponse($admin->deleteWebsiteEvent($input['event_id'] ?? ''));
                break;

            case 'update_hero_settings':
                $admin->sendJsonResponse($admin->updateHeroSettings($input['settings'] ?? []));
                break;

            default:
                $admin->sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        switch ($action) {
            case 'get_events':
                $admin->sendJsonResponse(['success' => true, 'events' => $admin->getEvents()]);
                break;
            case 'get_purchases':
                $admin->sendJsonResponse(['success' => true, 'purchases' => $admin->getPurchases()]);
                break;
            case 'get_stats':
                $admin->sendJsonResponse(['success' => true, 'stats' => $admin->getStats()]);
                break;
            case 'get_access':
                $admin->sendJsonResponse(['success' => true, 'access' => $admin->getAccessLogs()]);
                break;
            case 'export':
                $type = isset($_GET['type']) ? $_GET['type'] : 'purchases';
                $result = $admin->exportData($type);
                if (!empty($result['success'])) {
                    header('Content-Type: text/csv; charset=UTF-8');
                    header('Content-Disposition: attachment; filename="'.$result['filename'].'"');
                    echo $result['data'];
                    exit;
                }
                $admin->sendJsonResponse($result, 400);
                break;
            case 'get_fighters':
                $admin->sendJsonResponse(['success' => true, 'fighters' => $admin->getFighters()]);
                break;
            case 'get_news':
                $admin->sendJsonResponse(['success' => true, 'news' => $admin->getNewsList()]);
                break;
            case 'get_website_events':
                $admin->sendJsonResponse(['success' => true, 'events' => $admin->getWebsiteEvents()]);
                break;
            case 'get_hero_settings':
                $admin->sendJsonResponse(['success' => true, 'settings' => $admin->getHeroSettings()]);
                break;
            case 'update_hero_settings':
                $admin->sendJsonResponse($admin->updateHeroSettings($input['settings'] ?? []));
                break;
            default:
                if (!isset($_GET['action'])) {
                    $adminHtml = __DIR__ . '/admin.html';
                    if (is_file($adminHtml)) {
                        header('Content-Type: text/html; charset=UTF-8');
                        readfile($adminHtml);
                        exit;
                    }
                    // fallback ako nema admin.html
                    showLoginForm();
                    exit;
                }
                $admin->sendJsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }

    } else {
        $admin->sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }

} catch (Throwable $e) {
    error_log("PPV Admin Error: " . $e->getMessage());
    if (!headers_sent()) header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>