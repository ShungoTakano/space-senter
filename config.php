<?php
/**
 * 宇宙コックピット - Space Control Center
 * 共通設定ファイル
 */

// ========== API設定 ==========
// NASA APIキー（https://api.nasa.gov/ で取得可能）
// DEMO_KEYは1時間あたり30リクエストまで制限あり
define('NASA_API_KEY', 'BuPA75u8Lqd0wMC0fYpL3OQUGN7immG5hPrt4TcJ');

// ========== パス設定 ==========
define('DATA_DIR', __DIR__ . '/data');
define('BIRTHDAYS_FILE', DATA_DIR . '/birthdays.json');

// ========== API エンドポイント ==========
define('API_NASA_APOD', 'https://api.nasa.gov/planetary/apod');
define('API_NASA_NEO', 'https://api.nasa.gov/neo/rest/v1/feed');
define('API_ISS_LOCATION', 'http://api.open-notify.org/iss-now.json');
define('API_ASTRONAUTS', 'http://api.open-notify.org/astros.json');
define('API_ISS_PASS', 'http://api.open-notify.org/iss-pass.json');
define('API_SUNRISE_SUNSET', 'https://api.sunrise-sunset.org/json');
define('API_MYMEMORY_TRANSLATE', 'https://api.mymemory.translated.net/get');

// ========== APOD開始日 ==========
define('APOD_START_DATE', '1995-06-16');

// ========== デフォルト位置（東京） ==========
define('DEFAULT_LAT', 35.6762);
define('DEFAULT_LON', 139.6503);

// ========== タイムアウト設定 ==========
define('API_TIMEOUT', 10); // 秒
define('API_MAX_RETRIES', 3);

// ========== キャッシュ設定 ==========
define('NEO_CACHE_DURATION', 3600); // 1時間
define('ISS_UPDATE_INTERVAL', 10000); // 10秒（JavaScript用、ミリ秒）

// ========== 表示設定 ==========
define('GALLERY_MAX_ITEMS', 30); // ギャラリー最大表示数

// ========== 天体周回データ（日数） ==========
define('EARTH_ROTATION_DAYS', 1); // 地球の自転周期
define('MOON_ORBIT_DAYS', 27.3); // 月の公転周期
define('MARS_ORBIT_DAYS', 687); // 火星の公転周期
define('MERCURY_ORBIT_DAYS', 88); // 水星の公転周期
define('VENUS_ORBIT_DAYS', 225); // 金星の公転周期
define('JUPITER_ORBIT_DAYS', 4333); // 木星の公転周期

// ========== エラーレポート設定 ==========
// 開発時はエラーを表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== タイムゾーン設定 ==========
date_default_timezone_set('Asia/Tokyo');

// ========== ユーティリティ関数 ==========

/**
 * APIリクエストを実行（リトライ機能付き）
 *
 * @param string $url リクエストURL
 * @param int $timeout タイムアウト秒数
 * @param int $maxRetries 最大リトライ回数
 * @return array|null レスポンスデータまたはnull
 */
function fetchApi($url, $timeout = API_TIMEOUT, $maxRetries = API_MAX_RETRIES) {
    $retries = 0;

    while ($retries < $maxRetries) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'SpaceControlCenter/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        $retries++;
        if ($retries < $maxRetries) {
            usleep(500000); // 0.5秒待機
        }
    }

    return null;
}

/**
 * HTMLエスケープ（XSS対策）
 *
 * @param string $str 入力文字列
 * @return string エスケープされた文字列
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 日付が有効なAPOD範囲内かチェック
 *
 * @param string $date 日付（YYYY-MM-DD形式）
 * @return bool 有効な場合true
 */
function isValidApodDate($date) {
    $inputDate = strtotime($date);
    $startDate = strtotime(APOD_START_DATE);
    $today = strtotime(date('Y-m-d'));

    return $inputDate >= $startDate && $inputDate <= $today;
}

/**
 * 誕生日データの読み込み
 *
 * @return array 誕生日データの配列
 */
function loadBirthdays() {
    if (!file_exists(BIRTHDAYS_FILE)) {
        return ['birthdays' => []];
    }

    $content = file_get_contents(BIRTHDAYS_FILE);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['birthdays' => []];
    }

    return $data;
}

/**
 * 誕生日データの保存（ファイルロック付き）
 *
 * @param array $data 保存するデータ
 * @return bool 成功した場合true
 */
function saveBirthdays($data) {
    $fp = fopen(BIRTHDAYS_FILE, 'c+');
    if ($fp === false) {
        return false;
    }

    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    fclose($fp);
    return false;
}

/**
 * ユニークIDの生成
 *
 * @return string ユニークID
 */
function generateUniqueId() {
    return uniqid('bd_', true);
}

/**
 * 経過日数から各天体の周回数を計算
 *
 * @param int $days 経過日数
 * @return array 天体ごとの周回数
 */
function calculateOrbits($days) {
    return [
        'earth' => round($days / EARTH_ROTATION_DAYS),
        'moon' => round($days / MOON_ORBIT_DAYS, 1),
        'mercury' => round($days / MERCURY_ORBIT_DAYS, 1),
        'venus' => round($days / VENUS_ORBIT_DAYS, 1),
        'mars' => round($days / MARS_ORBIT_DAYS, 1),
        'jupiter' => round($days / JUPITER_ORBIT_DAYS, 2)
    ];
}

/**
 * 英語テキストを日本語に翻訳
 *
 * @param string $text 翻訳するテキスト
 * @return string|null 翻訳されたテキストまたはnull
 */
function translateToJapanese($text) {
    if (empty($text)) {
        return null;
    }

    // テキストが長すぎる場合は分割（MyMemory APIの制限は500文字）
    $maxLength = 500;
    if (mb_strlen($text) > $maxLength) {
        // 文で分割して翻訳
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $translatedParts = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($currentChunk . ' ' . $sentence) < $maxLength) {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            } else {
                if ($currentChunk) {
                    $translated = translateChunk($currentChunk);
                    if ($translated) {
                        $translatedParts[] = $translated;
                    }
                }
                $currentChunk = $sentence;
            }
        }
        if ($currentChunk) {
            $translated = translateChunk($currentChunk);
            if ($translated) {
                $translatedParts[] = $translated;
            }
        }
        return implode(' ', $translatedParts);
    }

    return translateChunk($text);
}

/**
 * テキストチャンクを翻訳
 *
 * @param string $text 翻訳するテキスト
 * @return string|null 翻訳されたテキストまたはnull
 */
function translateChunk($text) {
    $url = API_MYMEMORY_TRANSLATE . '?' . http_build_query([
        'q' => $text,
        'langpair' => 'en|ja'
    ]);

    $data = fetchApi($url);

    if ($data && isset($data['responseStatus']) && $data['responseStatus'] == 200) {
        return $data['responseData']['translatedText'] ?? null;
    }

    return null;
}
