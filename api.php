<?php
/**
 * 宇宙コックピット - Space Control Center
 * AJAX用APIエンドポイント
 *
 * MVC構造:
 * - Model: 外部API呼び出し・JSONファイル操作
 * - Controller: アクション分岐・データ検証
 * - View: JSON形式でレスポンス出力
 */

// ========== 設定読み込み ==========
require_once __DIR__ . '/config.php';

// ========== CORSヘッダー（開発用） ==========
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// ========== MODEL ==========

/**
 * ISS現在位置を取得
 */
function fetchIssLocation() {
    $data = fetchApi(API_ISS_LOCATION);
    if ($data && isset($data['iss_position'])) {
        return [
            'latitude' => floatval($data['iss_position']['latitude']),
            'longitude' => floatval($data['iss_position']['longitude']),
            'timestamp' => $data['timestamp']
        ];
    }
    return null;
}

/**
 * 宇宙飛行士情報を取得
 */
function fetchAstronauts() {
    $data = fetchApi(API_ASTRONAUTS);
    if ($data && isset($data['people'])) {
        return [
            'number' => $data['number'],
            'people' => $data['people']
        ];
    }
    return null;
}

/**
 * ISS通過時刻を取得
 */
function fetchIssPass($lat, $lon, $n = 5) {
    $url = API_ISS_PASS . "?lat={$lat}&lon={$lon}&n={$n}";
    $data = fetchApi($url);

    if ($data && isset($data['response'])) {
        $passes = [];
        foreach ($data['response'] as $pass) {
            $passes[] = [
                'risetime' => $pass['risetime'],
                'duration' => $pass['duration']
            ];
        }
        return $passes;
    }
    return null;
}

/**
 * NASA APOD取得
 */
function fetchApod($date = null) {
    $url = API_NASA_APOD . "?api_key=" . NASA_API_KEY;
    if ($date) {
        $url .= "&date=" . urlencode($date);
    }

    $data = fetchApi($url);
    if ($data && isset($data['url'])) {
        return [
            'title' => $data['title'] ?? '',
            'date' => $data['date'] ?? '',
            'explanation' => $data['explanation'] ?? '',
            'url' => $data['url'] ?? '',
            'hdurl' => $data['hdurl'] ?? $data['url'] ?? '',
            'media_type' => $data['media_type'] ?? 'image'
        ];
    }
    return null;
}

/**
 * 地球接近天体（NEO）を取得
 */
function fetchNeo() {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+7 days'));
    $url = API_NASA_NEO . "?start_date={$startDate}&end_date={$endDate}&api_key=" . NASA_API_KEY;

    $data = fetchApi($url);
    if ($data && isset($data['near_earth_objects'])) {
        $allNeos = [];
        foreach ($data['near_earth_objects'] as $dateKey => $neos) {
            foreach ($neos as $neo) {
                $approachData = $neo['close_approach_data'][0] ?? null;
                if ($approachData) {
                    $allNeos[] = [
                        'name' => $neo['name'],
                        'id' => $neo['id'],
                        'date' => $approachData['close_approach_date'],
                        'distance_km' => floatval($approachData['miss_distance']['kilometers']),
                        'velocity_kmh' => floatval($approachData['relative_velocity']['kilometers_per_hour']),
                        'diameter_min' => $neo['estimated_diameter']['meters']['estimated_diameter_min'],
                        'diameter_max' => $neo['estimated_diameter']['meters']['estimated_diameter_max'],
                        'is_hazardous' => $neo['is_potentially_hazardous_asteroid']
                    ];
                }
            }
        }
        // 距離でソート
        usort($allNeos, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });
        return array_slice($allNeos, 0, 5);
    }
    return null;
}

/**
 * 日没・日の出情報を取得
 */
function fetchSunriseSunset($lat, $lon, $date = null) {
    $date = $date ?? date('Y-m-d');
    $url = API_SUNRISE_SUNSET . "?lat={$lat}&lng={$lon}&date={$date}&formatted=0";
    $data = fetchApi($url);

    if ($data && isset($data['results'])) {
        return $data['results'];
    }
    return null;
}

/**
 * 誕生日を保存
 */
function storeBirthday($nickname, $date, $apodData) {
    $birthdays = loadBirthdays();

    $newEntry = [
        'id' => generateUniqueId(),
        'nickname' => $nickname,
        'date' => $date,
        'saved_at' => date('c'),
        'apod_data' => $apodData
    ];

    array_unshift($birthdays['birthdays'], $newEntry);

    // 最大件数制限
    if (count($birthdays['birthdays']) > GALLERY_MAX_ITEMS * 2) {
        $birthdays['birthdays'] = array_slice($birthdays['birthdays'], 0, GALLERY_MAX_ITEMS * 2);
    }

    return saveBirthdays($birthdays) ? $newEntry : null;
}

/**
 * 誕生日ギャラリー取得
 */
function fetchBirthdays($limit = GALLERY_MAX_ITEMS) {
    $birthdays = loadBirthdays();
    return array_slice($birthdays['birthdays'], 0, $limit);
}

/**
 * 特定の誕生日詳細取得
 */
function fetchBirthdayDetail($id) {
    $birthdays = loadBirthdays();
    foreach ($birthdays['birthdays'] as $birthday) {
        if ($birthday['id'] === $id) {
            return $birthday;
        }
    }
    return null;
}

// ========== CONTROLLER ==========

/**
 * レスポンスを送信
 */
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// アクション取得
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ISS現在位置取得
        case 'iss_location':
            $data = fetchIssLocation();
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, 'ISS位置情報を取得できませんでした');
            }
            break;

        // 宇宙飛行士情報取得
        case 'astronauts':
            $data = fetchAstronauts();
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, '宇宙飛行士情報を取得できませんでした');
            }
            break;

        // ISS通過時刻取得
        case 'iss_pass':
            $lat = floatval($_GET['lat'] ?? DEFAULT_LAT);
            $lon = floatval($_GET['lon'] ?? DEFAULT_LON);
            $n = intval($_GET['n'] ?? 5);

            // 緯度経度の検証
            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                sendResponse(false, null, '無効な緯度経度です');
            }

            $data = fetchIssPass($lat, $lon, $n);
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, 'ISS通過時刻を取得できませんでした');
            }
            break;

        // NASA APOD取得
        case 'apod':
            $date = $_GET['date'] ?? null;

            // 日付の検証
            if ($date && !isValidApodDate($date)) {
                sendResponse(false, null, 'NASA APODは1995年6月16日から利用可能です');
            }

            $data = fetchApod($date);
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, 'APODを取得できませんでした');
            }
            break;

        // 地球接近天体取得
        case 'neo':
            $data = fetchNeo();
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, '地球接近天体情報を取得できませんでした');
            }
            break;

        // 日没・日の出取得
        case 'sunrise_sunset':
            $lat = floatval($_GET['lat'] ?? DEFAULT_LAT);
            $lon = floatval($_GET['lon'] ?? DEFAULT_LON);
            $date = $_GET['date'] ?? null;

            // 緯度経度の検証
            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                sendResponse(false, null, '無効な緯度経度です');
            }

            $data = fetchSunriseSunset($lat, $lon, $date);
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, '日没・日の出情報を取得できませんでした');
            }
            break;

        // 誕生日保存
        case 'save_birthday':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, null, 'POSTメソッドを使用してください');
            }

            $nickname = trim($_POST['nickname'] ?? '');
            $date = $_POST['date'] ?? '';
            $apodDataJson = $_POST['apod_data'] ?? '';

            // 入力検証
            if (empty($nickname)) {
                sendResponse(false, null, 'ニックネームを入力してください');
            }
            if (strlen($nickname) > 20) {
                sendResponse(false, null, 'ニックネームは20文字以内で入力してください');
            }
            if (empty($date) || !isValidApodDate($date)) {
                sendResponse(false, null, '無効な日付です');
            }

            $apodData = json_decode($apodDataJson, true);
            if (!$apodData) {
                sendResponse(false, null, 'APODデータが無効です');
            }

            $result = storeBirthday($nickname, $date, $apodData);
            if ($result) {
                sendResponse(true, $result);
            } else {
                sendResponse(false, null, '保存に失敗しました');
            }
            break;

        // 誕生日ギャラリー取得
        case 'get_birthdays':
            $limit = intval($_GET['limit'] ?? GALLERY_MAX_ITEMS);
            $data = fetchBirthdays($limit);
            sendResponse(true, $data);
            break;

        // 誕生日詳細取得
        case 'get_birthday_detail':
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                sendResponse(false, null, 'IDを指定してください');
            }

            $data = fetchBirthdayDetail($id);
            if ($data) {
                sendResponse(true, $data);
            } else {
                sendResponse(false, null, 'データが見つかりませんでした');
            }
            break;

        // テキスト翻訳（英語→日本語）
        case 'translate':
            $text = $_GET['text'] ?? $_POST['text'] ?? '';
            if (empty($text)) {
                sendResponse(false, null, '翻訳するテキストを指定してください');
            }

            // テキストが長すぎる場合は制限
            if (mb_strlen($text) > 2000) {
                sendResponse(false, null, 'テキストが長すぎます（最大2000文字）');
            }

            $translatedText = translateToJapanese($text);
            if ($translatedText) {
                sendResponse(true, ['translated' => $translatedText, 'original' => $text]);
            } else {
                sendResponse(false, null, '翻訳に失敗しました');
            }
            break;

        // 不明なアクション
        default:
            sendResponse(false, null, '不明なアクションです: ' . h($action));
    }
} catch (Exception $e) {
    sendResponse(false, null, 'エラーが発生しました: ' . $e->getMessage());
}
