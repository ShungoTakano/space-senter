<?php

// 設定読み込み
require_once __DIR__ . '/config.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HELP - Space Control Center</title>
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
        <a href="index.php" class="nav__link">
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
        <a href="help.php" class="nav__link nav__link--active">
            <span class="nav__icon">&#10067;</span>
            HELP
        </a>
    </nav>

    <!-- メインコンテンツ -->
    <main class="main">
        <!-- 画面説明 -->
        <div class="page-intro">
            <h2 class="page-intro__title">ヘルプ・使い方ガイド</h2>
            <p class="page-intro__desc">
                宇宙コックピットの各機能の説明と、使用している宇宙APIについての解説です。
                このアプリケーションは複数の無料APIを活用して、リアルタイムの宇宙情報を提供しています。
            </p>
        </div>

        <div class="help-content">
            <!-- 操作方法セクション -->
            <section class="panel panel--help">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128214;</span>
                        各画面の操作方法
                    </h2>
                </div>
                <div class="panel__content">
                    <div class="help-sections">
                        <!-- NOW画面 -->
                        <div class="help-section">
                            <div class="help-section__header">
                                <span class="help-section__icon">&#9679;</span>
                                <h3 class="help-section__title">NOW画面</h3>
                            </div>
                            <div class="help-section__content">
                                <p class="help-section__desc">リアルタイムの宇宙情報を監視する画面です。</p>
                                <ul class="help-list">
                                    <li>
                                        <strong>ISS LIVE TRACKER:</strong>
                                        国際宇宙ステーション（ISS）のリアルタイム位置とライブ映像を表示します。
                                        「ライブ映像」と「位置表示」ボタンで表示を切り替えられます。
                                        ISSが地球の夜側にいる時は映像が暗くなることがあります。
                                    </li>
                                    <li>
                                        <strong>ASTRONAUTS IN SPACE:</strong>
                                        現在宇宙に滞在している宇宙飛行士の名前と所属する宇宙船を表示します。
                                    </li>
                                    <li>
                                        <strong>NEAR EARTH OBJECTS:</strong>
                                        今週地球に接近する小惑星TOP5を表示。赤い警告は潜在的に危険な天体を示します。
                                    </li>
                                    <li>
                                        <strong>NASA PICTURE OF THE DAY:</strong>
                                        NASAが毎日公開する天文写真。「日本語に翻訳」ボタンで説明文を翻訳できます。
                                    </li>
                                    <li>
                                        <strong>SPACE STATISTICS:</strong>
                                        ISSの累計周回数やボイジャー1号の距離など、宇宙に関する統計情報です。
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- TIME MACHINE画面 -->
                        <div class="help-section">
                            <div class="help-section__header">
                                <span class="help-section__icon">&#8987;</span>
                                <h3 class="help-section__title">TIME MACHINE画面</h3>
                            </div>
                            <div class="help-section__content">
                                <p class="help-section__desc">過去の日付の宇宙写真を探索する画面です。</p>
                                <ul class="help-list">
                                    <li>
                                        <strong>あなたの誕生日の宇宙:</strong>
                                        生年月日を入力すると、その日にNASAが公開した天文写真を見ることができます。
                                        1995年6月16日以降の日付が有効です。
                                    </li>
                                    <li>
                                        <strong>過去の日付で宇宙探索:</strong>
                                        誕生日以外の任意の日付でも宇宙写真を探索できます。
                                    </li>
                                    <li>
                                        <strong>ギャラリーに保存:</strong>
                                        誕生日の宇宙写真は、ニックネームを付けてギャラリーに保存できます。
                                        保存したデータはサーバー上のJSONファイルに保管されます。
                                    </li>
                                    <li>
                                        <strong>みんなの誕生日ギャラリー:</strong>
                                        保存された誕生日の宇宙写真を一覧で閲覧できます。
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- TONIGHT画面 -->
                        <div class="help-section">
                            <div class="help-section__header">
                                <span class="help-section__icon">&#9734;</span>
                                <h3 class="help-section__title">TONIGHT画面</h3>
                            </div>
                            <div class="help-section__content">
                                <p class="help-section__desc">今夜の天体観測に役立つ情報を提供する画面です。</p>
                                <ul class="help-list">
                                    <li>
                                        <strong>位置情報:</strong>
                                        「位置情報を取得」ボタンを押すと、あなたの現在地に合わせた情報が表示されます。
                                        許可しない場合は東京の情報が表示されます。
                                    </li>
                                    <li>
                                        <strong>日没・日の出:</strong>
                                        今日の日没時刻と明日の日の出時刻、天体観測に最適な時間帯を表示します。
                                    </li>
                                    <li>
                                        <strong>今夜の月:</strong>
                                        現在の月齢と月相を表示。月明かりは天体観測に影響します。
                                    </li>
                                    <li>
                                        <strong>ISS観測チャンス:</strong>
                                        ISSがあなたの上空を通過する時刻を表示します（API利用可能時）。
                                    </li>
                                    <li>
                                        <strong>今夜見える惑星・星座:</strong>
                                        季節に応じた観測可能な惑星と星座の情報を提供します。
                                    </li>
                                    <li>
                                        <strong>今後の天文イベント:</strong>
                                        30日以内の流星群などの天文イベントを表示します。
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 使用API説明セクション -->
            <section class="panel panel--api">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128279;</span>
                        使用しているAPI
                    </h2>
                </div>
                <div class="panel__content">
                    <div class="api-cards">
                        <!-- NASA API -->
                        <div class="api-card">
                            <div class="api-card__header">
                                <div class="api-card__logo">NASA</div>
                                <div class="api-card__badge">公式</div>
                            </div>
                            <h3 class="api-card__title">NASA Open APIs</h3>
                            <p class="api-card__url">
                                <a href="https://api.nasa.gov/" target="_blank" rel="noopener">https://api.nasa.gov/</a>
                            </p>
                            <div class="api-card__endpoints">
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">APOD (Astronomy Picture of the Day)</div>
                                    <div class="api-endpoint__url">planetary/apod</div>
                                    <div class="api-endpoint__desc">
                                        毎日更新される天文写真を取得します。1995年6月16日からのアーカイブにアクセス可能。
                                        タイトル、説明文、画像/動画URL、撮影日などの情報を提供します。
                                    </div>
                                </div>
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">NeoWs (Near Earth Object Web Service)</div>
                                    <div class="api-endpoint__url">neo/rest/v1/feed</div>
                                    <div class="api-endpoint__desc">
                                        地球に接近する小惑星（Near Earth Objects）の情報を取得します。
                                        接近日時、距離、速度、直径、危険度などのデータを提供します。
                                    </div>
                                </div>
                            </div>
                            <div class="api-card__note">
                                <strong>APIキー:</strong> 無料で取得可能。DEMO_KEYは1時間あたり30リクエストまで制限あり。
                            </div>
                        </div>

                        <!-- Open Notify API -->
                        <div class="api-card">
                            <div class="api-card__header">
                                <div class="api-card__logo">Open Notify</div>
                                <div class="api-card__badge">無料</div>
                            </div>
                            <h3 class="api-card__title">Open Notify API</h3>
                            <p class="api-card__url">
                                <a href="http://open-notify.org/" target="_blank" rel="noopener">http://open-notify.org/</a>
                            </p>
                            <div class="api-card__endpoints">
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">ISS Location Now</div>
                                    <div class="api-endpoint__url">iss-now.json</div>
                                    <div class="api-endpoint__desc">
                                        ISSの現在位置（緯度・経度）をリアルタイムで取得します。
                                        このアプリでは10秒ごとに自動更新しています。
                                    </div>
                                </div>
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">People in Space</div>
                                    <div class="api-endpoint__url">astros.json</div>
                                    <div class="api-endpoint__desc">
                                        現在宇宙に滞在している宇宙飛行士の人数と名前、所属する宇宙船を取得します。
                                    </div>
                                </div>
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">ISS Pass Times</div>
                                    <div class="api-endpoint__url">iss-pass.json</div>
                                    <div class="api-endpoint__desc">
                                        指定した緯度経度でのISS通過予測時刻を取得します。
                                        ※このエンドポイントは現在利用不可の場合があります。
                                    </div>
                                </div>
                            </div>
                            <div class="api-card__note">
                                <strong>APIキー:</strong> 不要。無料で利用可能。
                            </div>
                        </div>

                        <!-- Sunrise-Sunset API -->
                        <div class="api-card">
                            <div class="api-card__header">
                                <div class="api-card__logo">Sunrise-Sunset</div>
                                <div class="api-card__badge">無料</div>
                            </div>
                            <h3 class="api-card__title">Sunrise-Sunset API</h3>
                            <p class="api-card__url">
                                <a href="https://sunrise-sunset.org/api" target="_blank" rel="noopener">https://sunrise-sunset.org/api</a>
                            </p>
                            <div class="api-card__endpoints">
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">日の出・日没時刻</div>
                                    <div class="api-endpoint__url">json</div>
                                    <div class="api-endpoint__desc">
                                        指定した緯度経度・日付の日の出・日没時刻を取得します。
                                        市民薄明、航海薄明、天文薄明の開始・終了時刻も含まれます。
                                        天体観測に最適な時間帯の計算に使用しています。
                                    </div>
                                </div>
                            </div>
                            <div class="api-card__note">
                                <strong>APIキー:</strong> 不要。無料で利用可能。
                            </div>
                        </div>

                        <!-- MyMemory Translation API -->
                        <div class="api-card">
                            <div class="api-card__header">
                                <div class="api-card__logo">MyMemory</div>
                                <div class="api-card__badge">無料</div>
                            </div>
                            <h3 class="api-card__title">MyMemory Translation API</h3>
                            <p class="api-card__url">
                                <a href="https://mymemory.translated.net/doc/spec.php" target="_blank" rel="noopener">https://mymemory.translated.net/doc/spec.php</a>
                            </p>
                            <div class="api-card__endpoints">
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">テキスト翻訳</div>
                                    <div class="api-endpoint__url">get</div>
                                    <div class="api-endpoint__desc">
                                        英語テキストを日本語に翻訳します。
                                        NASA APODの説明文など、英語コンテンツの翻訳に使用しています。
                                        1回のリクエストで500文字まで翻訳可能です。
                                    </div>
                                </div>
                            </div>
                            <div class="api-card__note">
                                <strong>APIキー:</strong> 不要（匿名利用は1日1000語まで）。メール登録で制限緩和。
                            </div>
                        </div>

                        <!-- NASA Live Stream -->
                        <div class="api-card">
                            <div class="api-card__header">
                                <div class="api-card__logo">NASA Live</div>
                                <div class="api-card__badge">UStream/NASA+</div>
                            </div>
                            <h3 class="api-card__title">NASA ISS Live Stream</h3>
                            <p class="api-card__url">
                                <a href="https://eol.jsc.nasa.gov/ESRS/HDEV/" target="_blank" rel="noopener">NASA公式 ISS映像ページ</a>
                            </p>
                            <div class="api-card__endpoints">
                                <div class="api-endpoint">
                                    <div class="api-endpoint__name">ISS HD Earth Viewing</div>
                                    <div class="api-endpoint__url">UStream埋め込み / NASA+</div>
                                    <div class="api-endpoint__desc">
                                        ISSに搭載されたカメラからの地球のライブ映像です。
                                        ISSが地球の夜側を通過中は映像が暗くなります。
                                        通信途絶時はブルー画面や録画映像が表示されます。
                                        2024年8月にNASA TVは終了し、NASA+に移行しました。
                                    </div>
                                </div>
                            </div>
                            <div class="api-card__note">
                                <strong>注意:</strong> ライブ配信は常時行われているわけではありません。配信されていない場合は、NASA公式ページやNASA+でご覧ください。
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 技術仕様セクション -->
            <section class="panel panel--tech">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128736;</span>
                        技術仕様
                    </h2>
                </div>
                <div class="panel__content">
                    <div class="tech-specs">
                        <div class="tech-spec">
                            <div class="tech-spec__label">フロントエンド</div>
                            <div class="tech-spec__value">HTML5, CSS3, JavaScript (Vanilla)</div>
                        </div>
                        <div class="tech-spec">
                            <div class="tech-spec__label">バックエンド</div>
                            <div class="tech-spec__value">PHP 7.4+</div>
                        </div>
                        <div class="tech-spec">
                            <div class="tech-spec__label">データ保存</div>
                            <div class="tech-spec__value">JSONファイル（サーバーローカル）</div>
                        </div>
                        <div class="tech-spec">
                            <div class="tech-spec__label">API通信</div>
                            <div class="tech-spec__value">cURL（リトライ機能付き）</div>
                        </div>
                        <div class="tech-spec">
                            <div class="tech-spec__label">更新間隔</div>
                            <div class="tech-spec__value">ISS位置: 10秒 / 時計: 1秒</div>
                        </div>
                        <div class="tech-spec">
                            <div class="tech-spec__label">対応ブラウザ</div>
                            <div class="tech-spec__value">Chrome, Firefox, Safari, Edge（最新版）</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- クレジット -->
            <section class="panel panel--credits">
                <div class="panel__header">
                    <h2 class="panel__title">
                        <span class="panel__icon">&#128151;</span>
                        クレジット
                    </h2>
                </div>
                <div class="panel__content">
                    <div class="credits-content">
                        <p>
                            このアプリケーションは、NASA、Open Notify、Sunrise-Sunset.org、MyMemory Translation
                            が提供する無料APIを使用しています。各APIの提供者に感謝いたします。
                        </p>
                        <p>
                            宇宙の美しさと不思議さを多くの人に届けることを目的に開発されました。
                        </p>
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
    <script src="script.js"></script>
</body>
</html>
