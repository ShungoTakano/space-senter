<?php
/**
 * 宇宙コックピット - Space Control Center
 * TONIGHT画面（今夜の観測ガイド）
 *
 * MVC構造:
 * - Model: データ取得関数
 * - Controller: 初期データ準備
 * - View: HTML出力
 */

// ========== 設定読み込み ==========
require_once __DIR__ . '/config.php';

// ========== MODEL ==========

/**
 * 日没・日の出情報を取得
 */
function getSunriseSunset($lat, $lon, $date = null) {
    $date = $date ?? date('Y-m-d');
    $url = API_SUNRISE_SUNSET . "?lat={$lat}&lng={$lon}&date={$date}&formatted=0";
    $data = fetchApi($url);

    if ($data && isset($data['results'])) {
        return [
            'sunrise' => $data['results']['sunrise'] ?? null,
            'sunset' => $data['results']['sunset'] ?? null,
            'solar_noon' => $data['results']['solar_noon'] ?? null,
            'day_length' => $data['results']['day_length'] ?? null,
            'civil_twilight_begin' => $data['results']['civil_twilight_begin'] ?? null,
            'civil_twilight_end' => $data['results']['civil_twilight_end'] ?? null,
            'nautical_twilight_begin' => $data['results']['nautical_twilight_begin'] ?? null,
            'nautical_twilight_end' => $data['results']['nautical_twilight_end'] ?? null,
            'astronomical_twilight_begin' => $data['results']['astronomical_twilight_begin'] ?? null,
            'astronomical_twilight_end' => $data['results']['astronomical_twilight_end'] ?? null
        ];
    }
    return null;
}

/**
 * ISS通過時刻を取得
 * 注意: Open Notify APIのiss-passエンドポイントは現在利用不可の場合があります
 * その場合は計算で代替表示を行います
 */
function getIssPassTimes($lat, $lon, $n = 5) {
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
 * 月齢を計算（簡易版）
 */
function getMoonPhase($date = null) {
    $date = $date ?? date('Y-m-d');
    $timestamp = strtotime($date);

    // 2000年1月6日の新月を基準
    $knownNewMoon = strtotime('2000-01-06');
    $lunarCycle = 29.530588853; // 日数

    $daysSinceNewMoon = ($timestamp - $knownNewMoon) / 86400;
    $currentAge = fmod($daysSinceNewMoon, $lunarCycle);
    if ($currentAge < 0) $currentAge += $lunarCycle;

    // 月齢から月相を判定
    $phase = '';
    $illumination = 0;

    if ($currentAge < 1.85) {
        $phase = '新月';
        $illumination = 0;
    } elseif ($currentAge < 7.38) {
        $phase = '三日月（上弦へ）';
        $illumination = round(($currentAge / 14.77) * 100);
    } elseif ($currentAge < 9.23) {
        $phase = '上弦の月';
        $illumination = 50;
    } elseif ($currentAge < 14.77) {
        $phase = '十三夜月';
        $illumination = round(($currentAge / 14.77) * 100);
    } elseif ($currentAge < 16.61) {
        $phase = '満月';
        $illumination = 100;
    } elseif ($currentAge < 22.15) {
        $phase = '十六夜月';
        $illumination = round(100 - (($currentAge - 14.77) / 14.77) * 100);
    } elseif ($currentAge < 24.00) {
        $phase = '下弦の月';
        $illumination = 50;
    } else {
        $phase = '二十六夜月';
        $illumination = round(100 - (($currentAge - 14.77) / 14.77) * 100);
    }

    return [
        'age' => round($currentAge, 1),
        'phase' => $phase,
        'illumination' => max(0, min(100, $illumination))
    ];
}

/**
 * 観測条件スコアを計算
 */
function calculateObservationScore($moonPhase) {
    // 月齢による減点（満月に近いほど星が見えにくい）
    $moonScore = 5 - round(($moonPhase['illumination'] / 100) * 3);

    return [
        'total' => max(1, min(5, $moonScore)),
        'moon_impact' => $moonPhase['illumination'] > 70 ? '月明かりが強い' : ($moonPhase['illumination'] > 30 ? '月明かりあり' : '月明かり弱い')
    ];
}

/**
 * 今夜見える惑星情報を取得（簡易計算）
 */
function getVisiblePlanets($date = null) {
    $date = $date ?? date('Y-m-d');
    $month = intval(date('n', strtotime($date)));
    $dayOfYear = intval(date('z', strtotime($date)));

    // 惑星の可視性データ（季節と時刻に基づく簡易版）
    $planets = [
        [
            'name' => '金星',
            'name_en' => 'Venus',
            'symbol' => '♀',
            'magnitude' => -4.5,
            'visible' => true,
            'best_time' => ($month >= 1 && $month <= 5) ? '明け方' : '夕方',
            'direction' => ($month >= 1 && $month <= 5) ? '東' : '西',
            'note' => '最も明るい惑星。肉眼で簡単に見える'
        ],
        [
            'name' => '火星',
            'name_en' => 'Mars',
            'symbol' => '♂',
            'magnitude' => 0.5,
            'visible' => ($month % 2 == 0),
            'best_time' => '深夜',
            'direction' => '南',
            'note' => '赤く輝く惑星'
        ],
        [
            'name' => '木星',
            'name_en' => 'Jupiter',
            'symbol' => '♃',
            'magnitude' => -2.5,
            'visible' => true,
            'best_time' => ($month >= 9 || $month <= 3) ? '夜半' : '明け方',
            'direction' => ($month >= 9 || $month <= 3) ? '南' : '東',
            'note' => '金星に次いで明るい惑星'
        ],
        [
            'name' => '土星',
            'name_en' => 'Saturn',
            'symbol' => '♄',
            'magnitude' => 0.5,
            'visible' => ($month >= 6 && $month <= 12),
            'best_time' => '夜半',
            'direction' => '南',
            'note' => '環が美しい惑星（望遠鏡推奨）'
        ],
        [
            'name' => '水星',
            'name_en' => 'Mercury',
            'symbol' => '☿',
            'magnitude' => 0.0,
            'visible' => ($dayOfYear % 30 < 10),
            'best_time' => '日没直後または日の出直前',
            'direction' => '西または東（低空）',
            'note' => '観測が難しい惑星'
        ]
    ];

    return array_filter($planets, function($p) { return $p['visible']; });
}

/**
 * 今夜見える主な星座を取得
 */
function getVisibleConstellations($date = null) {
    $date = $date ?? date('Y-m-d');
    $month = intval(date('n', strtotime($date)));

    // 季節ごとの代表的な星座
    $constellations = [
        // 冬（12-2月）
        [
            'name' => 'オリオン座',
            'name_en' => 'Orion',
            'season' => [12, 1, 2],
            'main_star' => 'ベテルギウス、リゲル',
            'direction' => '南',
            'difficulty' => '簡単',
            'description' => '冬の代表的な星座。三つ星が目印'
        ],
        [
            'name' => 'おおいぬ座',
            'name_en' => 'Canis Major',
            'season' => [12, 1, 2],
            'main_star' => 'シリウス（全天で最も明るい恒星）',
            'direction' => '南東',
            'difficulty' => '簡単',
            'description' => '全天一明るいシリウスが輝く'
        ],
        [
            'name' => 'ふたご座',
            'name_en' => 'Gemini',
            'season' => [12, 1, 2, 3],
            'main_star' => 'カストル、ポルックス',
            'direction' => '南〜天頂',
            'difficulty' => '普通',
            'description' => '二つの明るい星が並ぶ'
        ],
        // 春（3-5月）
        [
            'name' => 'しし座',
            'name_en' => 'Leo',
            'season' => [3, 4, 5],
            'main_star' => 'レグルス',
            'direction' => '南',
            'difficulty' => '普通',
            'description' => '？マークを逆にした形が目印'
        ],
        [
            'name' => 'おとめ座',
            'name_en' => 'Virgo',
            'season' => [4, 5, 6],
            'main_star' => 'スピカ',
            'direction' => '南東',
            'difficulty' => '普通',
            'description' => '春の大三角の一つ'
        ],
        [
            'name' => 'うしかい座',
            'name_en' => 'Boötes',
            'season' => [4, 5, 6],
            'main_star' => 'アークトゥルス',
            'direction' => '東〜南',
            'difficulty' => '簡単',
            'description' => 'オレンジ色に輝くアークトゥルス'
        ],
        // 夏（6-8月）
        [
            'name' => 'こと座',
            'name_en' => 'Lyra',
            'season' => [6, 7, 8, 9],
            'main_star' => 'ベガ（織姫星）',
            'direction' => '天頂付近',
            'difficulty' => '簡単',
            'description' => '夏の大三角の一つ'
        ],
        [
            'name' => 'わし座',
            'name_en' => 'Aquila',
            'season' => [6, 7, 8, 9],
            'main_star' => 'アルタイル（彦星）',
            'direction' => '南〜天頂',
            'difficulty' => '簡単',
            'description' => '夏の大三角の一つ'
        ],
        [
            'name' => 'はくちょう座',
            'name_en' => 'Cygnus',
            'season' => [6, 7, 8, 9],
            'main_star' => 'デネブ',
            'direction' => '天頂付近',
            'difficulty' => '簡単',
            'description' => '十字形が目印、夏の大三角の一つ'
        ],
        [
            'name' => 'さそり座',
            'name_en' => 'Scorpius',
            'season' => [6, 7, 8],
            'main_star' => 'アンタレス',
            'direction' => '南（低空）',
            'difficulty' => '簡単',
            'description' => '赤く輝くアンタレスが目印'
        ],
        // 秋（9-11月）
        [
            'name' => 'ペガスス座',
            'name_en' => 'Pegasus',
            'season' => [9, 10, 11],
            'main_star' => 'ペガススの大四辺形',
            'direction' => '天頂付近',
            'difficulty' => '普通',
            'description' => '大きな四角形が目印'
        ],
        [
            'name' => 'アンドロメダ座',
            'name_en' => 'Andromeda',
            'season' => [9, 10, 11],
            'main_star' => 'アンドロメダ銀河（M31）',
            'direction' => '北東〜天頂',
            'difficulty' => '普通',
            'description' => '肉眼で見える最も遠い天体'
        ],
        [
            'name' => 'カシオペヤ座',
            'name_en' => 'Cassiopeia',
            'season' => [9, 10, 11, 12],
            'main_star' => 'W字型の5つの星',
            'direction' => '北',
            'difficulty' => '簡単',
            'description' => '一年中見えるがこの時期が見頃'
        ],
        // 通年
        [
            'name' => '北斗七星（おおぐま座）',
            'name_en' => 'Ursa Major',
            'season' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
            'main_star' => '北斗七星',
            'direction' => '北',
            'difficulty' => '簡単',
            'description' => '北極星を探す目印'
        ]
    ];

    // 現在の月に見える星座をフィルタ
    return array_filter($constellations, function($c) use ($month) {
        return in_array($month, $c['season']);
    });
}

/**
 * 季節の天文イベントを取得
 */
function getUpcomingEvents($date = null) {
    $date = $date ?? date('Y-m-d');
    $year = intval(date('Y', strtotime($date)));
    $currentTimestamp = strtotime($date);

    // 主な天文イベント（年間固定のもの）
    $events = [
        ['date' => "$year-01-04", 'name' => 'しぶんぎ座流星群', 'type' => '流星群'],
        ['date' => "$year-04-22", 'name' => 'こと座流星群', 'type' => '流星群'],
        ['date' => "$year-05-06", 'name' => 'みずがめ座η流星群', 'type' => '流星群'],
        ['date' => "$year-08-12", 'name' => 'ペルセウス座流星群', 'type' => '流星群'],
        ['date' => "$year-10-21", 'name' => 'オリオン座流星群', 'type' => '流星群'],
        ['date' => "$year-11-17", 'name' => 'しし座流星群', 'type' => '流星群'],
        ['date' => "$year-12-14", 'name' => 'ふたご座流星群', 'type' => '流星群'],
        ['date' => "$year-03-20", 'name' => '春分の日', 'type' => '季節'],
        ['date' => "$year-06-21", 'name' => '夏至', 'type' => '季節'],
        ['date' => "$year-09-23", 'name' => '秋分の日', 'type' => '季節'],
        ['date' => "$year-12-22", 'name' => '冬至', 'type' => '季節'],
    ];

    // 今後30日以内のイベントをフィルタ
    $upcoming = [];
    foreach ($events as $event) {
        $eventTimestamp = strtotime($event['date']);
        $daysUntil = ($eventTimestamp - $currentTimestamp) / 86400;
        if ($daysUntil >= 0 && $daysUntil <= 30) {
            $event['days_until'] = intval($daysUntil);
            $upcoming[] = $event;
        }
    }

    // 日付でソート
    usort($upcoming, function($a, $b) {
        return strtotime($a['date']) <=> strtotime($b['date']);
    });

    return $upcoming;
}

// ========== CONTROLLER ==========

// デフォルト位置（東京）を使用（JavaScript側で位置情報を取得して更新）
$lat = DEFAULT_LAT;
$lon = DEFAULT_LON;
$locationName = '東京（デフォルト）';

// 今日の日没・日の出
$sunData = getSunriseSunset($lat, $lon);
// 明日の日の出
$tomorrowDate = date('Y-m-d', strtotime('+1 day'));
$tomorrowSunData = getSunriseSunset($lat, $lon, $tomorrowDate);

// 月齢
$moonPhase = getMoonPhase();

// 観測スコア
$observationScore = calculateObservationScore($moonPhase);

// ISS通過時刻（APIが利用可能な場合）
$issPasses = getIssPassTimes($lat, $lon);

// 今夜見える惑星
$visiblePlanets = getVisiblePlanets();

// 今夜見える星座
$visibleConstellations = getVisibleConstellations();

// 今後の天文イベント
$upcomingEvents = getUpcomingEvents();

// JavaScript用データ
$jsData = [
    'defaultLat' => DEFAULT_LAT,
    'defaultLon' => DEFAULT_LON,
    'sunData' => $sunData,
    'tomorrowSunData' => $tomorrowSunData,
    'moonPhase' => $moonPhase,
    'observationScore' => $observationScore,
    'issPasses' => $issPasses,
    'visiblePlanets' => array_values($visiblePlanets),
    'visibleConstellations' => array_values($visibleConstellations),
    'upcomingEvents' => $upcomingEvents
];

// ========== VIEW ==========
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TONIGHT - Space Control Center</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- ヘッダー -->
    <header class="header">
        <div class="header__logo">
            <h1 class="header__title">SPACE CONTROL CENTER</h1>
            <div class="header__subtitle">宇宙コックピット</div>
        </div>
        <div class="header__time">
            <div class="header__time-utc">
                <span class="time-label">UTC</span>
                <span id="utc-time" class="time-value">--:--:--</span>
            </div>
            <div class="header__time-local">
                <span class="time-label">LOCAL</span>
                <span id="local-time" class="time-value">--:--:--</span>
            </div>
        </div>
    </header>

    <!-- ナビゲーション -->
    <nav class="nav">
        <a href="index.php" class="nav__link">
            <span class="nav__icon">&#9679;</span>
            NOW
        </a>
        <a href="time-machine.php" class="nav__link">
            <span class="nav__icon">&#8987;</span>
            TIME MACHINE
        </a>
        <a href="tonight.php" class="nav__link nav__link--active">
            <span class="nav__icon">&#9734;</span>
            TONIGHT
        </a>
    </nav>

    <!-- メインコンテンツ -->
    <main class="main">
        <!-- 画面説明 -->
        <div class="page-intro">
            <h2 class="page-intro__title">今夜の観測ガイド</h2>
            <p class="page-intro__desc">
                今夜の天体観測に最適な情報をお届けします。日没・日の出時刻、月齢、ISSの通過時刻、
                そして観測条件のスコアを確認できます。位置情報を許可すると、あなたの場所に合わせた情報が表示されます。
            </p>
        </div>

        <div class="tonight">
            <!-- 位置情報ステータス -->
            <div class="location-status" id="location-status">
                <span class="location-status__icon">&#128205;</span>
                <span class="location-status__text" id="location-text"><?= h($locationName) ?></span>
                <button class="location-status__btn" id="get-location-btn">位置情報を取得</button>
            </div>

            <div class="tonight__grid">
                <!-- 日没・日の出情報 -->
                <section class="panel panel--sun">
                    <div class="panel__header">
                        <h2 class="panel__title">
                            <span class="panel__icon">&#127773;</span>
                            日没・日の出
                        </h2>
                    </div>
                    <p class="panel__desc">今日の日没と明日の日の出時刻。天体観測に最適な暗闘時間を確認できます。</p>
                    <div class="panel__content">
                        <div class="sun-times">
                            <div class="sun-time sun-time--sunset">
                                <div class="sun-time__icon">&#127774;</div>
                                <div class="sun-time__info">
                                    <div class="sun-time__label">今日の日没</div>
                                    <div class="sun-time__value" id="sunset-time">
                                        <?php if ($sunData && $sunData['sunset']): ?>
                                            <?= h(date('H:i', strtotime($sunData['sunset']))) ?>
                                        <?php else: ?>
                                            --:--
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="sun-time sun-time--sunrise">
                                <div class="sun-time__icon">&#127749;</div>
                                <div class="sun-time__info">
                                    <div class="sun-time__label">明日の日の出</div>
                                    <div class="sun-time__value" id="sunrise-time">
                                        <?php if ($tomorrowSunData && $tomorrowSunData['sunrise']): ?>
                                            <?= h(date('H:i', strtotime($tomorrowSunData['sunrise']))) ?>
                                        <?php else: ?>
                                            --:--
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="darkness-countdown">
                            <div class="darkness-countdown__label">完全な暗闘まで</div>
                            <div class="darkness-countdown__value" id="darkness-countdown">計算中...</div>
                        </div>

                        <div class="observation-window">
                            <div class="observation-window__label">天体観測ベストタイム</div>
                            <div class="observation-window__value" id="best-time">
                                <?php if ($sunData && $sunData['astronomical_twilight_end'] && $tomorrowSunData && $tomorrowSunData['astronomical_twilight_begin']): ?>
                                    <?= h(date('H:i', strtotime($sunData['astronomical_twilight_end']))) ?> 〜
                                    <?= h(date('H:i', strtotime($tomorrowSunData['astronomical_twilight_begin']))) ?>
                                <?php else: ?>
                                    --:-- 〜 --:--
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 月齢情報 -->
                <section class="panel panel--moon">
                    <div class="panel__header">
                        <h2 class="panel__title">
                            <span class="panel__icon">&#127769;</span>
                            今夜の月
                        </h2>
                    </div>
                    <p class="panel__desc">現在の月齢と月相。満月に近いほど月明かりが強く、暗い天体の観測は難しくなります。</p>
                    <div class="panel__content">
                        <div class="moon-info">
                            <div class="moon-visual" id="moon-visual">
                                <div class="moon-visual__circle" style="--illumination: <?= h($moonPhase['illumination']) ?>%"></div>
                            </div>
                            <div class="moon-data">
                                <div class="moon-data__row">
                                    <span class="moon-data__label">月齢</span>
                                    <span class="moon-data__value"><?= h($moonPhase['age']) ?></span>
                                </div>
                                <div class="moon-data__row">
                                    <span class="moon-data__label">月相</span>
                                    <span class="moon-data__value"><?= h($moonPhase['phase']) ?></span>
                                </div>
                                <div class="moon-data__row">
                                    <span class="moon-data__label">輝面比</span>
                                    <span class="moon-data__value"><?= h($moonPhase['illumination']) ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ISS観測チャンス -->
                <section class="panel panel--iss-pass">
                    <div class="panel__header">
                        <h2 class="panel__title">
                            <span class="panel__icon">&#128752;</span>
                            ISS観測チャンス
                        </h2>
                    </div>
                    <p class="panel__desc">ISSが夜空を通過する時刻。晴れた夜なら肉眼でも明るく動く光点として観測できます。</p>
                    <div class="panel__content">
                        <div class="iss-passes" id="iss-passes">
                            <?php if ($issPasses && !empty($issPasses)): ?>
                                <?php foreach ($issPasses as $index => $pass): ?>
                                    <div class="iss-pass">
                                        <div class="iss-pass__time">
                                            <?= h(date('Y/m/d H:i', $pass['risetime'])) ?>
                                        </div>
                                        <div class="iss-pass__duration">
                                            観測時間: <?= h(floor($pass['duration'] / 60)) ?>分<?= h($pass['duration'] % 60) ?>秒
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="iss-pass iss-pass--unavailable">
                                    <p>ISS通過時刻データは現在取得できません。</p>
                                    <p class="iss-pass__note">ISSは約90分で地球を一周しています。晴れた夜空で明るく動く光点を探してみてください。</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="iss-countdown" id="iss-countdown">
                            <!-- JavaScriptで更新 -->
                        </div>
                    </div>
                </section>

                <!-- 観測条件スコア -->
                <section class="panel panel--score">
                    <div class="panel__header">
                        <h2 class="panel__title">
                            <span class="panel__icon">&#11088;</span>
                            今夜の観測条件
                        </h2>
                    </div>
                    <p class="panel__desc">月明かりや天候を考慮した観測条件の総合評価。星5つが最高の条件です。</p>
                    <div class="panel__content">
                        <div class="observation-score">
                            <div class="observation-score__stars" id="observation-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="star <?= $i < $observationScore['total'] ? 'star--active' : '' ?>">&#9733;</span>
                                <?php endfor; ?>
                            </div>
                            <div class="observation-score__label">
                                <?php
                                $scoreLabels = [
                                    1 => '観測困難',
                                    2 => 'まずまず',
                                    3 => '良好',
                                    4 => '好条件',
                                    5 => '最高の条件'
                                ];
                                echo h($scoreLabels[$observationScore['total']] ?? '不明');
                                ?>
                            </div>
                        </div>

                        <div class="observation-factors">
                            <div class="observation-factor">
                                <span class="observation-factor__icon">&#127769;</span>
                                <span class="observation-factor__text"><?= h($observationScore['moon_impact']) ?></span>
                            </div>
                            <div class="observation-factor">
                                <span class="observation-factor__icon">&#127782;</span>
                                <span class="observation-factor__text" id="weather-status">天気情報は位置情報取得後に表示</span>
                            </div>
                        </div>

                        <div class="observation-advice" id="observation-advice">
                            <h4>観測アドバイス</h4>
                            <ul>
                                <?php if ($moonPhase['illumination'] > 70): ?>
                                    <li>月明かりが強いため、明るい天体（惑星など）の観測がおすすめです</li>
                                <?php else: ?>
                                    <li>暗い天体や星雲の観測に適しています</li>
                                <?php endif; ?>
                                <li>観測の15分前には暗い場所で目を慣らしましょう</li>
                                <li>防寒対策をしっかりと行いましょう</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- 今夜見える惑星 -->
            <section class="panel panel--planets">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#127774;</span>
                        今夜見える惑星
                    </h2>
                    <div class="panel__badge"><?= count($visiblePlanets) ?>個</div>
                </div>
                <p class="panel__desc">今夜観測可能な惑星とその見つけ方。明るい惑星は肉眼でも確認できます。</p>
                <div class="panel__content">
                    <div class="planets-list">
                        <?php if (!empty($visiblePlanets)): ?>
                            <?php foreach ($visiblePlanets as $planet): ?>
                                <div class="planet-card">
                                    <div class="planet-card__symbol"><?= h($planet['symbol']) ?></div>
                                    <div class="planet-card__info">
                                        <div class="planet-card__name"><?= h($planet['name']) ?></div>
                                        <div class="planet-card__name-en"><?= h($planet['name_en']) ?></div>
                                    </div>
                                    <div class="planet-card__details">
                                        <div class="planet-card__detail">
                                            <span class="planet-card__label">観測時間</span>
                                            <span class="planet-card__value"><?= h($planet['best_time']) ?></span>
                                        </div>
                                        <div class="planet-card__detail">
                                            <span class="planet-card__label">方角</span>
                                            <span class="planet-card__value"><?= h($planet['direction']) ?></span>
                                        </div>
                                        <div class="planet-card__detail">
                                            <span class="planet-card__label">等級</span>
                                            <span class="planet-card__value"><?= h($planet['magnitude']) ?></span>
                                        </div>
                                    </div>
                                    <div class="planet-card__note"><?= h($planet['note']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">今夜観測可能な惑星はありません</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- 今夜見える星座 -->
            <section class="panel panel--constellations">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#10024;</span>
                        今夜見える星座
                    </h2>
                    <div class="panel__badge"><?= count($visibleConstellations) ?>星座</div>
                </div>
                <p class="panel__desc">今の季節に見られる代表的な星座。星座探しの参考にしてください。</p>
                <div class="panel__content">
                    <div class="constellations-grid">
                        <?php foreach ($visibleConstellations as $constellation): ?>
                            <div class="constellation-card">
                                <div class="constellation-card__header">
                                    <div class="constellation-card__name"><?= h($constellation['name']) ?></div>
                                    <div class="constellation-card__difficulty difficulty--<?= $constellation['difficulty'] === '簡単' ? 'easy' : 'normal' ?>">
                                        <?= h($constellation['difficulty']) ?>
                                    </div>
                                </div>
                                <div class="constellation-card__star">
                                    <span class="label">主な星:</span> <?= h($constellation['main_star']) ?>
                                </div>
                                <div class="constellation-card__direction">
                                    <span class="label">方角:</span> <?= h($constellation['direction']) ?>
                                </div>
                                <div class="constellation-card__desc"><?= h($constellation['description']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- 今後の天文イベント -->
            <?php if (!empty($upcomingEvents)): ?>
            <section class="panel panel--events">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128197;</span>
                        今後の天文イベント
                    </h2>
                    <div class="panel__badge">30日以内</div>
                </div>
                <p class="panel__desc">近日中に予定されている天文イベント。流星群や季節の節目をチェック。</p>
                <div class="panel__content">
                    <div class="events-list">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-card event-card--<?= $event['type'] === '流星群' ? 'meteor' : 'season' ?>">
                                <div class="event-card__date">
                                    <div class="event-card__month"><?= h(date('n月', strtotime($event['date']))) ?></div>
                                    <div class="event-card__day"><?= h(date('j', strtotime($event['date']))) ?></div>
                                </div>
                                <div class="event-card__info">
                                    <div class="event-card__name"><?= h($event['name']) ?></div>
                                    <div class="event-card__type"><?= h($event['type']) ?></div>
                                </div>
                                <div class="event-card__countdown">
                                    <?php if ($event['days_until'] === 0): ?>
                                        <span class="today">今日!</span>
                                    <?php else: ?>
                                        あと<span class="days"><?= h($event['days_until']) ?></span>日
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 星空マップ -->
            <section class="panel panel--sky">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#127776;</span>
                        今夜の星空マップ
                    </h2>
                </div>
                <p class="panel__desc">今夜の星空のイメージ図。実際の星空と見比べてみてください。</p>
                <div class="panel__content">
                    <div class="sky-canvas" id="sky-canvas">
                        <div class="sky-canvas__stars"></div>
                        <div class="sky-canvas__compass">
                            <span class="compass-n">北</span>
                            <span class="compass-e">東</span>
                            <span class="compass-s">南</span>
                            <span class="compass-w">西</span>
                        </div>
                        <div class="sky-canvas__zenith">天頂</div>
                        <?php
                        // 星座の位置を簡易的に表示
                        $positions = ['top-left', 'top-right', 'center', 'bottom-left', 'bottom-right'];
                        $i = 0;
                        foreach (array_slice($visibleConstellations, 0, 5) as $c):
                            $pos = $positions[$i % 5];
                        ?>
                            <div class="sky-canvas__constellation sky-canvas__constellation--<?= $pos ?>">
                                <?= h($c['name']) ?>
                            </div>
                        <?php
                            $i++;
                        endforeach;
                        ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- フッター -->
    <footer class="footer">
        <div class="footer__credits">
            <p>Data provided by NASA, Open Notify API, Sunrise-Sunset.org</p>
        </div>
        <div class="footer__info">
            <p>Space Control Center - 宇宙コックピット</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // PHPから渡すデータ
        window.TONIGHT_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
