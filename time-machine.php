<?php
/**
 * 宇宙コックピット - Space Control Center
 * TIME MACHINE画面（時空を超える）
 *
 * MVC構造:
 * - Model: データ取得・保存関数
 * - Controller: リクエスト処理
 * - View: HTML出力
 */

// ========== 設定読み込み ==========
require_once __DIR__ . '/config.php';

// ========== MODEL ==========

/**
 * 指定日付のAPODを取得（キャッシュチェック付き）
 */
function getApodByDate($date) {
    // まずキャッシュ（保存済み誕生日）をチェック
    $birthdays = loadBirthdays();
    foreach ($birthdays['birthdays'] as $birthday) {
        if ($birthday['date'] === $date && isset($birthday['apod_data'])) {
            return $birthday['apod_data'];
        }
    }

    // APIから取得
    $url = API_NASA_APOD . "?api_key=" . NASA_API_KEY . "&date=" . urlencode($date);
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
 * 誕生日を保存
 */
function saveBirthday($nickname, $date, $apodData) {
    $birthdays = loadBirthdays();

    $newEntry = [
        'id' => generateUniqueId(),
        'nickname' => $nickname,
        'date' => $date,
        'saved_at' => date('c'),
        'apod_data' => $apodData
    ];

    array_unshift($birthdays['birthdays'], $newEntry);

    // 最大件数を超えたら古いものを削除
    if (count($birthdays['birthdays']) > GALLERY_MAX_ITEMS * 2) {
        $birthdays['birthdays'] = array_slice($birthdays['birthdays'], 0, GALLERY_MAX_ITEMS * 2);
    }

    return saveBirthdays($birthdays) ? $newEntry['id'] : null;
}

/**
 * 特定の誕生日データを取得
 */
function getBirthdayById($id) {
    $birthdays = loadBirthdays();
    foreach ($birthdays['birthdays'] as $birthday) {
        if ($birthday['id'] === $id) {
            return $birthday;
        }
    }
    return null;
}

/**
 * ギャラリー用の誕生日リストを取得
 */
function getGalleryBirthdays($limit = GALLERY_MAX_ITEMS) {
    $birthdays = loadBirthdays();
    return array_slice($birthdays['birthdays'], 0, $limit);
}

// ========== CONTROLLER ==========

$mode = 'input'; // input, result, detail
$apodData = null;
$selectedDate = null;
$daysPassed = null;
$orbits = null;
$savedMessage = null;
$errorMessage = null;
$detailData = null;
$isBirthdayMode = true; // 誕生日モードか探索モードか

// 詳細表示モード
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $mode = 'detail';
    $detailData = getBirthdayById($_GET['view']);
    if (!$detailData) {
        $errorMessage = 'データが見つかりませんでした。';
        $mode = 'input';
    }
}
// 保存処理
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_action'])) {
    $nickname = trim($_POST['nickname'] ?? '');
    $date = $_POST['date'] ?? '';
    $apodDataJson = $_POST['apod_data'] ?? '';

    if (empty($nickname)) {
        $errorMessage = 'ニックネームを入力してください。';
    } elseif (empty($date) || !isValidApodDate($date)) {
        $errorMessage = '無効な日付です。';
    } else {
        $apodData = json_decode($apodDataJson, true);
        if ($apodData) {
            $savedId = saveBirthday($nickname, $date, $apodData);
            if ($savedId) {
                $savedMessage = '誕生日の宇宙を保存しました！';
            } else {
                $errorMessage = '保存に失敗しました。';
            }
        } else {
            $errorMessage = 'APODデータが無効です。';
        }
    }
    $mode = 'input';
}
// 日付検索処理
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
    $selectedDate = $_POST['date'] ?? '';
    $isBirthdayMode = isset($_POST['birthday_mode']) && $_POST['birthday_mode'] === '1';

    if (empty($selectedDate)) {
        $errorMessage = '日付を入力してください。';
        $mode = 'input';
    } elseif (!isValidApodDate($selectedDate)) {
        $errorMessage = 'NASA APODは1995年6月16日から利用可能です。有効な日付を入力してください。';
        $mode = 'input';
    } else {
        $apodData = getApodByDate($selectedDate);
        if ($apodData) {
            $mode = 'result';
            // 経過日数計算
            $birthDate = new DateTime($selectedDate);
            $today = new DateTime();
            $diff = $today->diff($birthDate);
            $daysPassed = $diff->days;
            $orbits = calculateOrbits($daysPassed);
        } else {
            $errorMessage = 'この日付のデータを取得できませんでした。';
            $mode = 'input';
        }
    }
}

// ギャラリーデータ取得
$galleryBirthdays = getGalleryBirthdays();

// ========== VIEW ==========
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIME MACHINE - Space Control Center</title>
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
        <a href="time-machine.php" class="nav__link nav__link--active">
            <span class="nav__icon">&#8987;</span>
            TIME MACHINE
        </a>
        <a href="tonight.php" class="nav__link">
            <span class="nav__icon">&#9734;</span>
            TONIGHT
        </a>
    </nav>

    <!-- メインコンテンツ -->
    <main class="main">
        <!-- 画面説明 -->
        <div class="page-intro">
            <h2 class="page-intro__title">宇宙タイムマシン</h2>
            <p class="page-intro__desc">
                過去の日付を入力して、その日にNASAが公開した天文写真を見ることができます。
                あなたの誕生日にはどんな宇宙の姿が映し出されていたのでしょうか？
                1995年6月16日から今日までの任意の日付を探索できます。
            </p>
        </div>

        <div class="time-machine">
            <?php if ($mode === 'detail' && $detailData): ?>
                <!-- 詳細表示モード -->
                <section class="panel panel--full">
                    <div class="panel__header">
                        <h2 class="panel__title">
                            <span class="panel__icon">&#128302;</span>
                            <?= h($detailData['nickname']) ?>さんの誕生日の宇宙
                        </h2>
                        <a href="time-machine.php" class="btn btn--secondary">ギャラリーに戻る</a>
                    </div>
                    <div class="panel__content">
                        <div class="result">
                            <?php
                            $birthMonth = date('n月j日', strtotime($detailData['date']));
                            ?>
                            <div class="result__date-badge"><?= h($birthMonth) ?>生まれ</div>

                            <?php if (isset($detailData['apod_data'])): ?>
                                <div class="result__apod">
                                    <?php if ($detailData['apod_data']['media_type'] === 'image'): ?>
                                        <img src="<?= h($detailData['apod_data']['url']) ?>" alt="<?= h($detailData['apod_data']['title']) ?>" class="result__image">
                                    <?php else: ?>
                                        <iframe src="<?= h($detailData['apod_data']['url']) ?>" class="result__video" allowfullscreen></iframe>
                                    <?php endif; ?>
                                    <div class="result__info">
                                        <h3 class="result__title"><?= h($detailData['apod_data']['title']) ?></h3>
                                        <div class="result__explanation-wrapper">
                                            <p class="result__explanation" id="detail-explanation" data-original="<?= h($detailData['apod_data']['explanation']) ?>"><?= h($detailData['apod_data']['explanation']) ?></p>
                                            <button type="button" class="btn btn--translate" data-target="detail-explanation">
                                                <span class="btn__icon">&#127760;</span>
                                                日本語に翻訳
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

            <?php else: ?>
                <!-- 入力・結果表示モード -->
                <div class="time-machine__grid">
                    <!-- 入力フォームセクション -->
                    <section class="panel panel--input">
                        <div class="panel__header">
                            <h2 class="panel__title">
                                <span class="panel__icon">&#128302;</span>
                                TIME TRAVEL
                            </h2>
                        </div>
                        <div class="panel__content">
                            <?php if ($savedMessage): ?>
                                <div class="message message--success"><?= h($savedMessage) ?></div>
                            <?php endif; ?>
                            <?php if ($errorMessage): ?>
                                <div class="message message--error"><?= h($errorMessage) ?></div>
                            <?php endif; ?>

                            <!-- 誕生日検索フォーム -->
                            <form method="POST" class="search-form" id="birthday-form">
                                <h3 class="search-form__title">あなたの誕生日の宇宙</h3>
                                <p class="search-form__desc">生年月日を入力して、あなたが生まれた日にNASAが公開した宇宙の写真を見てみましょう。</p>
                                <div class="search-form__field">
                                    <label for="birthday-date" class="search-form__label">生年月日</label>
                                    <input type="date" id="birthday-date" name="date" class="search-form__input"
                                           min="1995-06-16" max="<?= date('Y-m-d') ?>" required>
                                    <input type="hidden" name="birthday_mode" value="1">
                                </div>
                                <button type="submit" class="btn btn--primary btn--full">
                                    <span class="btn__icon">&#128640;</span>
                                    タイムトラベル開始
                                </button>
                            </form>

                            <div class="divider">
                                <span>または</span>
                            </div>

                            <!-- 過去の日付探索フォーム -->
                            <form method="POST" class="search-form" id="explore-form">
                                <h3 class="search-form__title">過去の日付で宇宙探索</h3>
                                <p class="search-form__desc">任意の日付の宇宙を探索できます。</p>
                                <div class="search-form__field">
                                    <label for="explore-date" class="search-form__label">日付</label>
                                    <input type="date" id="explore-date" name="date" class="search-form__input"
                                           min="1995-06-16" max="<?= date('Y-m-d') ?>" required>
                                    <input type="hidden" name="birthday_mode" value="0">
                                </div>
                                <button type="submit" class="btn btn--secondary btn--full">
                                    <span class="btn__icon">&#128269;</span>
                                    探索する
                                </button>
                            </form>
                        </div>
                    </section>

                    <?php if ($mode === 'result' && $apodData): ?>
                        <!-- 結果表示セクション -->
                        <section class="panel panel--result">
                            <div class="panel__header">
                                <h2 class="panel__title">
                                    <span class="panel__icon">&#127775;</span>
                                    <?= $isBirthdayMode ? 'あなたが生まれた日の宇宙' : h($selectedDate) . 'の宇宙' ?>
                                </h2>
                            </div>
                            <div class="panel__content">
                                <div class="result">
                                    <?php if ($isBirthdayMode): ?>
                                        <div class="result__message">
                                            あなたが生まれた日、NASAはこの写真を公開しました
                                        </div>
                                    <?php endif; ?>

                                    <div class="result__apod">
                                        <?php if ($apodData['media_type'] === 'image'): ?>
                                            <img src="<?= h($apodData['url']) ?>" alt="<?= h($apodData['title']) ?>" class="result__image" loading="lazy">
                                        <?php else: ?>
                                            <iframe src="<?= h($apodData['url']) ?>" class="result__video" allowfullscreen></iframe>
                                        <?php endif; ?>
                                        <div class="result__info">
                                            <h3 class="result__title"><?= h($apodData['title']) ?></h3>
                                            <p class="result__date"><?= h($apodData['date']) ?></p>
                                            <div class="result__explanation-wrapper">
                                                <p class="result__explanation" id="result-explanation" data-original="<?= h($apodData['explanation']) ?>"><?= h($apodData['explanation']) ?></p>
                                                <button type="button" class="btn btn--translate" data-target="result-explanation">
                                                    <span class="btn__icon">&#127760;</span>
                                                    日本語に翻訳
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($daysPassed !== null): ?>
                                        <div class="result__stats">
                                            <h4 class="result__stats-title">
                                                <?= $isBirthdayMode ? 'あなたの誕生からの記録' : 'この日からの記録' ?>
                                            </h4>
                                            <div class="stats-grid">
                                                <div class="stat-card">
                                                    <div class="stat-card__value"><?= h(number_format($daysPassed)) ?></div>
                                                    <div class="stat-card__label">日経過</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-card__value"><?= h(number_format($orbits['earth'])) ?></div>
                                                    <div class="stat-card__label">地球の自転</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-card__value"><?= h(number_format($orbits['moon'], 1)) ?></div>
                                                    <div class="stat-card__label">月の公転</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-card__value"><?= h(number_format($orbits['mars'], 1)) ?></div>
                                                    <div class="stat-card__label">火星の公転</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-card__value"><?= h(number_format($orbits['jupiter'], 2)) ?></div>
                                                    <div class="stat-card__label">木星の公転</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($isBirthdayMode): ?>
                                        <!-- 保存フォーム -->
                                        <div class="result__save">
                                            <h4 class="result__save-title">この誕生日を保存しますか？</h4>
                                            <form method="POST" class="save-form">
                                                <input type="hidden" name="date" value="<?= h($selectedDate) ?>">
                                                <input type="hidden" name="apod_data" value="<?= h(json_encode($apodData)) ?>">
                                                <input type="hidden" name="save_action" value="1">
                                                <div class="save-form__field">
                                                    <label for="nickname" class="save-form__label">ニックネーム</label>
                                                    <input type="text" id="nickname" name="nickname" class="save-form__input"
                                                           placeholder="例: 太郎" required maxlength="20">
                                                </div>
                                                <button type="submit" class="btn btn--accent btn--full">
                                                    <span class="btn__icon">&#128190;</span>
                                                    ギャラリーに保存する
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <!-- ギャラリーセクション -->
                <?php if (!empty($galleryBirthdays)): ?>
                    <section class="panel panel--gallery">
                        <div class="panel__header">
                            <h2 class="panel__title">
                                <span class="panel__icon">&#127912;</span>
                                みんなの誕生日ギャラリー
                            </h2>
                            <div class="panel__count"><?= count($galleryBirthdays) ?>件</div>
                        </div>
                        <p class="panel__desc">みんなが保存した誕生日の宇宙写真コレクション。カードをクリックすると詳細が見れます。</p>
                        <div class="panel__content">
                            <div class="gallery">
                                <?php foreach ($galleryBirthdays as $birthday): ?>
                                    <a href="time-machine.php?view=<?= h($birthday['id']) ?>" class="gallery__card">
                                        <?php if (isset($birthday['apod_data']['url'])): ?>
                                            <div class="gallery__card-image">
                                                <img src="<?= h($birthday['apod_data']['url']) ?>" alt="<?= h($birthday['apod_data']['title'] ?? '') ?>" loading="lazy">
                                            </div>
                                        <?php endif; ?>
                                        <div class="gallery__card-info">
                                            <div class="gallery__card-nickname"><?= h($birthday['nickname']) ?></div>
                                            <div class="gallery__card-date">
                                                <?= h(date('n月j日', strtotime($birthday['date']))) ?>生まれ
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
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
    <script src="script.js"></script>
</body>
</html>
