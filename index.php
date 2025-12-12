<?php

// 設定読み込み
require_once __DIR__ . '/config.php';


// ISS現在位置を取得
function getIssLocation() {
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

// 宇宙飛行士情報を取得
function getAstronauts() {
    $data = fetchApi(API_ASTRONAUTS);
    if ($data && isset($data['people'])) {
        return [
            'number' => $data['number'],
            'people' => $data['people']
        ];
    }
    return null;
}

// 地球接近天体を取得
function getNearEarthObjects() {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+7 days'));
    $url = API_NASA_NEO . "?start_date={$startDate}&end_date={$endDate}&api_key=" . NASA_API_KEY;

    $data = fetchApi($url);
    if ($data && isset($data['near_earth_objects'])) {
        $allNeos = [];
        foreach ($data['near_earth_objects'] as $date => $neos) {
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
        // 距離でソートしてTOP5を取得
        usort($allNeos, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });
        return array_slice($allNeos, 0, 5);
    }
    return null;
}

// 今日のNASA APODを取得
function getTodayApod() {
    $url = API_NASA_APOD . "?api_key=" . NASA_API_KEY;
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

// 宇宙統計データを取得
function getSpaceStatistics() {
    // 誕生日ギャラリーの保存件数
    $birthdays = loadBirthdays();
    $savedBirthdays = count($birthdays['birthdays']);

    // 現在の日時情報
    $now = new DateTime('now', new DateTimeZone('UTC'));

    // ISSの累計周回数（1998年11月20日から）
    $issLaunch = new DateTime('1998-11-20', new DateTimeZone('UTC'));
    $daysSinceLaunch = $issLaunch->diff($now)->days;
    $issOrbits = round($daysSinceLaunch * 15.54); // 1日あたり約15.54周

    // ボイジャー1号の地球からの距離
    // 2024年1月時点で約244億km、年間約5億km増加
    $voyagerBaseDist = 24400000000; // km
    $voyagerBaseDate = new DateTime('2024-01-01', new DateTimeZone('UTC'));
    $daysSinceBase = $voyagerBaseDate->diff($now)->days;
    $voyagerDistance = $voyagerBaseDist + ($daysSinceBase * 1369863); // 1日あたり約137万km

    // 光年での距離
    $lightYear = 9460730472580.8; // km
    $voyagerLightYears = $voyagerDistance / $lightYear;

    return [
        'saved_birthdays' => $savedBirthdays,
        'iss_orbits' => $issOrbits,
        'iss_days_in_space' => $daysSinceLaunch,
        'voyager_distance_km' => $voyagerDistance,
        'voyager_distance_au' => round($voyagerDistance / 149597870.7, 2), // AU
        'voyager_light_hours' => round(($voyagerDistance / 299792.458) / 3600, 1), // 光時間
        'current_utc' => $now->format('Y-m-d H:i:s')
    ];
}

// 初期データの取得
$issLocation = getIssLocation();
$astronauts = getAstronauts();
$nearEarthObjects = getNearEarthObjects();
$todayApod = getTodayApod();
$spaceStats = getSpaceStatistics();

// JavaScript用のデータをJSON化
$jsData = [
    'issLocation' => $issLocation,
    'astronauts' => $astronauts,
    'nearEarthObjects' => $nearEarthObjects,
    'todayApod' => $todayApod,
    'spaceStats' => $spaceStats,
    'updateInterval' => ISS_UPDATE_INTERVAL
];

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOW - Space Control Center</title>
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
            <div class="header__subtitle">スペース コントロール センター</div>
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
        <a href="index.php" class="nav__link nav__link--active">
            <span class="nav__icon">&#9679;</span>
            NOW
        </a>
        <a href="time-machine.php" class="nav__link">
            <span class="nav__icon">&#8987;</span>
            TIME MACHINE
        </a>
        <a href="tonight.php" class="nav__link">
            <span class="nav__icon">&#9734;</span>
            TONIGHT
        </a>
        <a href="help.php" class="nav__link">
            <span class="nav__icon">&#10067;</span>
            HELP
        </a>
    </nav>

    <!-- メインコンテンツ -->
    <main class="main">
        <!-- 画面説明 -->
        <div class="page-intro">
            <h2 class="page-intro__title">リアルタイム宇宙モニタリング</h2>
            <p class="page-intro__desc">
                NOW画面では，国際宇宙ステーション（ISS）の現在位置，宇宙にいる飛行士，
                地球に接近する小惑星，そしてNASAの今日の天文写真をリアルタイムで確認できます．
                データは10秒ごとに自動更新されます．
            </p>
        </div>

        <div class="dashboard">
            <!-- ISSトラッカー -->
            <section class="panel panel--iss">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128752;</span>
                        ISS LIVE TRACKER
                    </h2>
                    <div class="panel__status">
                        <span class="status-dot status-dot--live"></span>
                        LIVE
                    </div>
                </div>
                <p class="panel__desc">国際宇宙ステーションは時速約27,600kmで地球を周回しています．現在の位置を追跡します．</p>
                <div class="panel__content">
                    <!-- ISS ライブ映像 -->
                    <div class="iss-live-video">
                        <div class="iss-live-video__toggle">
                            <button type="button" class="btn btn--secondary iss-view-btn iss-view-btn--active" data-view="video">
                                <span class="btn__icon">&#127909;</span>
                                ライブ映像
                            </button>
                            <button type="button" class="btn btn--secondary iss-view-btn" data-view="globe">
                                <span class="btn__icon">&#127760;</span>
                                位置表示
                            </button>
                        </div>
                        <div class="iss-live-video__container" id="iss-video-container">
                            <iframe
                                id="iss-live-iframe"
                                src="https://ustream.tv/embed/17074538"
                                title="NASA ISS Live Stream - Earth From Space"
                                frameborder="0"
                                allow="autoplay"
                                allowfullscreen
                                class="iss-live-video__iframe"
                            ></iframe>
                            <div class="iss-live-video__notice">
                                <span class="iss-live-video__notice-icon">&#128246;</span>
                                <span>ISSが地球の夜側にいる時は映像が暗くなります．通信途絶時はブルー画面や録画映像が表示されることがあります．OFF-AIRと表示される場合は配信が行われていません．以下のリンクからご確認ください．</span>
                            </div>
                            <div class="iss-live-video__links">
                                <a href="https://eol.jsc.nasa.gov/ESRS/HDEV/" target="_blank" rel="noopener" class="iss-live-video__link">
                                    NASA公式ページで視聴
                                </a>
                                <a href="https://www.nasa.gov/live/" target="_blank" rel="noopener" class="iss-live-video__link">
                                    NASA Live
                                </a>
                            </div>
                        </div>
                        <div class="iss-map" id="iss-map" style="display: none;">
                            <div class="iss-map__globe">
                                <div class="iss-icon" id="iss-icon"></div>
                            </div>
                        </div>
                    </div>
                    <div class="iss-data">
                        <div class="iss-data__row">
                            <span class="iss-data__label">緯度</span>
                            <span class="iss-data__value" id="iss-lat"><?= $issLocation ? h(number_format($issLocation['latitude'], 4)) : '--' ?>°</span>
                        </div>
                        <div class="iss-data__row">
                            <span class="iss-data__label">経度</span>
                            <span class="iss-data__value" id="iss-lon"><?= $issLocation ? h(number_format($issLocation['longitude'], 4)) : '--' ?>°</span>
                        </div>
                        <div class="iss-data__row">
                            <span class="iss-data__label">高度</span>
                            <span class="iss-data__value">約 408 km</span>
                        </div>
                        <div class="iss-data__row">
                            <span class="iss-data__label">速度</span>
                            <span class="iss-data__value">約 27,600 km/h</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 宇宙飛行士 -->
            <section class="panel panel--astronauts">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128105;&#8205;&#128640;</span>
                        ASTRONAUTS IN SPACE
                    </h2>
                    <div class="panel__count" id="astronaut-count">
                        <?= $astronauts ? h($astronauts['number']) : '--' ?> 人
                    </div>
                </div>
                <p class="panel__desc">現在，宇宙空間で活動している全ての宇宙飛行士と所属する宇宙船を表示します．</p>
                <div class="panel__content">
                    <ul class="astronaut-list" id="astronaut-list">
                        <?php if ($astronauts && !empty($astronauts['people'])): ?>
                            <?php foreach ($astronauts['people'] as $person): ?>
                                <li class="astronaut-list__item">
                                    <span class="astronaut-list__name"><?= h($person['name']) ?></span>
                                    <span class="astronaut-list__craft"><?= h($person['craft']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="astronaut-list__item">データを取得中...</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </section>

            <!-- 地球接近天体 -->
            <section class="panel panel--neo">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#9788;</span>
                        NEAR EARTH OBJECTS
                    </h2>
                    <div class="panel__badge">今週TOP5</div>
                </div>
                <p class="panel__desc">今週地球に最も接近する小惑星TOP5．赤い警告はちょっと危険な天体を示します．</p>
                <div class="panel__content">
                    <div class="neo-list" id="neo-list">
                        <?php if ($nearEarthObjects && !empty($nearEarthObjects)): ?>
                            <?php foreach ($nearEarthObjects as $index => $neo): ?>
                                <div class="neo-card <?= $neo['is_hazardous'] ? 'neo-card--hazardous' : '' ?>">
                                    <div class="neo-card__rank"><?= $index + 1 ?></div>
                                    <div class="neo-card__info">
                                        <div class="neo-card__name"><?= h($neo['name']) ?></div>
                                        <div class="neo-card__date">接近日: <?= h($neo['date']) ?></div>
                                    </div>
                                    <div class="neo-card__stats">
                                        <div class="neo-card__stat">
                                            <span class="neo-card__stat-label">距離</span>
                                            <span class="neo-card__stat-value"><?= h(number_format($neo['distance_km'])) ?> km</span>
                                        </div>
                                        <div class="neo-card__stat">
                                            <span class="neo-card__stat-label">直径</span>
                                            <span class="neo-card__stat-value"><?= h(round($neo['diameter_min'])) ?>-<?= h(round($neo['diameter_max'])) ?> m</span>
                                        </div>
                                        <div class="neo-card__stat">
                                            <span class="neo-card__stat-label">速度</span>
                                            <span class="neo-card__stat-value"><?= h(number_format($neo['velocity_kmh'])) ?> km/h</span>
                                        </div>
                                    </div>
                                    <?php if ($neo['is_hazardous']): ?>
                                        <div class="neo-card__warning">&#9888; POTENTIALLY HAZARDOUS</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="neo-card">
                                <div class="neo-card__info">データを取得中...</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- NASA APOD -->
            <section class="panel panel--apod">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128247;</span>
                        NASA PICTURE OF THE DAY
                    </h2>
                    <div class="panel__date" id="apod-date">
                        <?= $todayApod ? h($todayApod['date']) : '--' ?>
                    </div>
                </div>
                <p class="panel__desc">NASAが毎日公開する天文学的な写真や画像です．1995年より開始されたため，それ以前の者は取得できません．</p>
                <div class="panel__content">
                    <?php if ($todayApod): ?>
                        <div class="apod">
                            <?php if ($todayApod['media_type'] === 'image'): ?>
                                <img src="<?= h($todayApod['url']) ?>" alt="<?= h($todayApod['title']) ?>" class="apod__image" loading="lazy">
                            <?php else: ?>
                                <iframe src="<?= h($todayApod['url']) ?>" class="apod__video" allowfullscreen></iframe>
                            <?php endif; ?>
                            <div class="apod__info">
                                <h3 class="apod__title"><?= h($todayApod['title']) ?></h3>
                                <div class="apod__explanation-wrapper">
                                    <p class="apod__explanation" id="apod-explanation" data-original="<?= h($todayApod['explanation']) ?>"><?= h($todayApod['explanation']) ?></p>
                                    <button type="button" class="btn btn--translate" id="translate-apod-btn" data-target="apod-explanation">
                                        <span class="btn__icon">&#127760;</span>
                                        日本語に翻訳
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="apod">
                            <p class="apod__loading">画像を取得中...</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 宇宙統計情報 -->
            <section class="panel panel--stats">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128202;</span>
                        SPACE STATISTICS
                    </h2>
                    <div class="panel__badge">リアルタイム</div>
                </div>
                <p class="panel__desc">今の宇宙に関する統計データです．数値は概算値です．</p>
                <div class="panel__content">
                    <div class="space-stats">
                        <div class="space-stat-card">
                            <div class="space-stat-card__icon">&#128752;</div>
                            <div class="space-stat-card__value" id="iss-orbits"><?= h(number_format($spaceStats['iss_orbits'])) ?></div>
                            <div class="space-stat-card__label">ISS累計周回数</div>
                            <div class="space-stat-card__sub">1998年11月20日から</div>
                        </div>
                        <div class="space-stat-card">
                            <div class="space-stat-card__icon">&#128640;</div>
                            <div class="space-stat-card__value"><?= h(number_format($spaceStats['voyager_distance_au'])) ?> AU</div>
                            <div class="space-stat-card__label">ボイジャー1号の距離</div>
                            <div class="space-stat-card__sub">光で約<?= h($spaceStats['voyager_light_hours']) ?>時間</div>
                        </div>
                        <div class="space-stat-card">
                            <div class="space-stat-card__icon">&#127759;</div>
                            <div class="space-stat-card__value"><?= h(number_format($spaceStats['iss_days_in_space'])) ?></div>
                            <div class="space-stat-card__label">ISS運用日数</div>
                            <div class="space-stat-card__sub"><?= h(round($spaceStats['iss_days_in_space'] / 365.25, 1)) ?>年以上</div>
                        </div>
                        <div class="space-stat-card">
                            <div class="space-stat-card__icon">&#127775;</div>
                            <div class="space-stat-card__value" id="saved-count"><?= h($spaceStats['saved_birthdays']) ?></div>
                            <div class="space-stat-card__label">保存された宇宙の誕生日</div>
                            <div class="space-stat-card__sub">TIME MACHINEで追加</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- フッター
    <footer class="footer">
        <div class="footer__credits">
            <p>Data provided by NASA, Open Notify API, Sunrise-Sunset.org</p>
        </div>
        <div class="footer__info">
            <p>Space Control Center - 宇宙コックピット</p>
        </div>
    </footer>
    -->

    <!-- JavaScript -->
    <script>
        // PHPから渡す初期データ
        window.SPACE_DATA = <?= json_encode($jsData, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="script.js"></script>
</body>
</html>
