# 宇宙コックピット - Space Control Center
## 要件定義書

---

## 1. システム概要

### 1.1 システム名
**宇宙コックピット - Space Control Center**

### 1.2 システムの目的
リアルタイムの宇宙情報を統合し、まるで宇宙管制センターのようなインタラクティブな体験を提供するWebアプリケーション。NASAをはじめとする複数のAPIを組み合わせ、宇宙の「今」「過去」「今夜」を可視化する。

### 1.3 想定ユーザー
- 講義課題のシステムのため、評価者(学生・教員)を主なユーザと想定

### 1.4 使用技術
- **フロントエンド**: HTML5, CSS3, JavaScript（Vanilla JS、フレームワーク不使用）
- **バックエンド**: PHP（フレームワーク不使用、MVC設計思想は維持）
- **実行環境**: Docker（PHP with Apache、単一コンテナ構成）
- **データ保存**: JSON形式のファイル
- **使用API**: 
  - NASA APOD API
  - Open Notify API (ISS位置情報)
  - NASA NeoWs API (地球接近天体)
  - Geolocation API (ブラウザ標準)
  - Sunrise-Sunset.org API

---

## 2. 機能要件

### 2.1 画面構成
3つのメインページで構成:
1. **index.php - NOW (リアルタイム宇宙監視)**
2. **time-machine.php - TIME MACHINE (時空を超える)**
3. **tonight.php - TONIGHT (今夜の観測ガイド)**

**ページ間遷移**:
- 各ページ上部にナビゲーションメニュー（NOW / TIME MACHINE / TONIGHT）
- リンククリックでページ遷移
- 共通のヘッダー・フッター部分は各PHPファイルに直接記述（コピー）

### 2.2 NOW画面の機能

#### 2.2.1 ISSライブトラッカー
**表示項目**:
- ISS現在位置（緯度・経度）
- 地図上にISSアイコンを表示
- 現在速度（km/h）
- 高度（km）
- 次の日本上空通過時刻
- 「あと○分で○○の上空！」メッセージ
- 搭乗中の宇宙飛行士リスト（名前・国籍）

**データ更新**:
- 10秒ごとに自動更新
- 更新時にアニメーション表示

**使用API**:
- Open Notify API: `http://api.open-notify.org/iss-now.json`
- Open Notify API: `http://api.open-notify.org/astros.json`

#### 2.2.2 地球接近天体アラート
**表示項目**:
- 今週地球に接近する小惑星TOP5
- 各小惑星の情報:
  - 名前
  - 接近日時
  - 地球からの距離（km）
  - 推定直径（m）
  - 相対速度（km/h）
  - 危険度レベル（色分け表示）

**データ更新**:
- 1時間ごとに自動更新

**使用API**:
- NASA NeoWs API: `https://api.nasa.gov/neo/rest/v1/feed`

#### 2.2.3 今日のNASA宇宙写真
**表示項目**:
- 本日のAPOD（Astronomy Picture of the Day）
- 写真タイトル
- 撮影日
- 説明文（英語）
- 画像を大きく表示

**使用API**:
- NASA APOD API: `https://api.nasa.gov/planetary/apod`

#### 2.2.4 現在地の天気情報（オプション）
**表示項目**:
- 天気アイコン
- 気温
- 雲量

**使用API**:
- Geolocation API（位置取得）
- OpenWeather API（オプション、時間があれば実装）

---

### 2.3 TIME MACHINE画面の機能

#### 2.3.1 あなたの誕生日の宇宙
**入力項目**:
- 生年月日（日付選択フォーム）
- ニックネーム（オプション、保存する場合に使用）

**表示項目**:
- 入力された日付のNASA APOD写真
- 「あなたが生まれた日、NASAはこの写真を公開しました」メッセージ
- 写真タイトル
- 撮影日
- 説明文
- 「あなたの誕生から○○日経過しました」
- 「これは地球○○周分です」（地球1周=24時間として計算）
- 地球以外の天体の周回数表示(例：月、火星など計算可能なもの)
- 「これは△の〇〇周分です」
- 「この誕生日を保存」ボタン（ニックネーム入力後に有効化）

**ページ遷移フロー**:
```
time-machine.php (入力フォーム表示)
  ↓ フォーム送信（POST: date）
time-machine.php (結果表示 + 保存フォーム)
  ↓ 保存ボタン押下（POST: date, nickname, save_action）
time-machine.php (保存完了メッセージ + ギャラリー表示)
```

**保存機能**:
- ニックネームと誕生日をJSON形式で保存（`data/birthdays.json`）
- 保存データ構造:
```json
{
  "birthdays": [
    {
      "id": "unique_id_123",
      "nickname": "太郎",
      "date": "1995-06-16",
      "saved_at": "2024-12-11T10:30:00Z",
      "apod_data": {
        "title": "...",
        "url": "...",
        "explanation": "..."
      }
    }
  ]
}
```

**データ取得**:
- フォーム送信後、「タイムトラベル中...」ローディング表示
- まずJSON内に同じ日付のデータがあるかチェック
- JSON内にあればそれを使用（API呼び出しなし）
- なければNASA APOD APIで取得

**使用API**:
- NASA APOD API: `https://api.nasa.gov/planetary/apod?date=YYYY-MM-DD`

**注意事項**:
- NASA APODは1995年6月16日から開始
- それ以前の日付は「データがありません」と表示
- 写真が存在しない日付の場合のエラーハンドリング

#### 2.3.2 みんなの誕生日ギャラリー
**機能概要**:
- 他のユーザーが保存した誕生日の宇宙を閲覧できる
- time-machine.php内でギャラリーセクションとして表示

**表示項目**:
- 保存された誕生日のリスト（カード形式）
- 各カードに表示:
  - ニックネーム
  - 誕生日（年は非表示、月日のみ「6月16日生まれ」）
  - NASA APOD写真のサムネイル
  - 「詳細を見る」リンク

**ページ遷移フロー**:
```
time-machine.php (ギャラリー表示)
  ↓ カードクリック
time-machine.php?view=ID (詳細表示)
  ↓ 「戻る」リンク
time-machine.php (ギャラリーに戻る)
```

**詳細表示**:
- GETパラメータ`view`で特定の誕生日IDを指定
- 完全なAPOD情報を表示（タイトル、説明、大きな画像）
- API呼び出しなし（JSON内のキャッシュデータを使用）
- 「誕生日入力フォームに戻る」リンク

**データソース**:
- `data/birthdays.json` から読み込み
- 新しい順にソート（saved_at順）

**表示制限**:
- 最新30件まで表示（パフォーマンス考慮）

#### 2.3.3 過去の日付で宇宙探索
**入力項目**:
- 任意の日付（日付選択フォーム）

**表示項目**:
- TIME MACHINE 2.3.1と同様の情報
- 「○○年○月○日の宇宙」というタイトル
- 保存機能は提供しない（誕生日専用）

**ページ遷移**:
- 同じtime-machine.php内で処理（POSTパラメータで区別）

---

### 2.4 TONIGHT画面の機能

#### 2.4.1 位置情報の取得
**機能**:
- ページ読み込み時に位置情報の許可を要求
- 許可された場合: 緯度経度を取得して以下の計算に使用
- 拒否された場合: デフォルト位置（東京）を使用、またはユーザーに都市名入力を促す

**使用API**:
- Geolocation API（ブラウザ標準）

#### 2.4.2 日没・日の出情報
**表示項目**:
- 本日の日没時刻
- 翌日の日の出時刻
- 「完全な暗闇まであと○分」カウントダウン
- 天体観測ベストタイム（日没後1時間〜日の出前1時間）

**使用API**:
- Sunrise-Sunset.org API: `https://api.sunrise-sunset.org/json?lat=LATITUDE&lng=LONGITUDE`

#### 2.4.3 ISS観測チャンス
**表示項目**:
- 今夜ISSが観測可能な時刻（複数回ある場合は全て表示）
- 各観測チャンスの詳細:
  - 観測可能時刻（開始〜終了）
  - 観測時間（○分間）
  - 出現方角
  - 最大高度（度）
  - 明るさレベル（★で表示）
- 「観測まであと○時間○分」カウントダウン

**計算方法**:
- ISS軌道データと現在位置から、地平線より上にISSが来る時刻を計算
- 太陽の位置を考慮（日没後・日の出前で、ISSは太陽光を反射している状態）

**使用API**:
- Open Notify API: `http://api.open-notify.org/iss-pass.json?lat=LATITUDE&lon=LONGITUDE`

#### 2.4.4 観測条件スコア
**表示項目**:
- 総合観測スコア（★5段階）
- 評価要素:
  - 天気（晴れ/曇り/雨）
  - ISSの明るさ
  - 月齢（満月に近いと星が見えにくい）
- 観測アドバイス（「防寒必須」など）

**計算方法**:
- 各要素を数値化して合計スコアを算出

---

## 3. 非機能要件

### 3.1 UI/UXデザイン

#### 3.1.1 デザインコンセプト
- **テーマ**: SF映画の宇宙管制センター風
- **カラースキーム**:
  - 背景: 深い宇宙ブルー (#0a1128, #001233)
  - アクセント: オレンジ (#ff6b35) - 警告・重要情報
  - テキスト: 白 (#ffffff)
  - サブテキスト: グレー (#a0a0a0)
  - 成功: グリーン (#00ff88)
  - 警告: イエロー (#ffd23f)
  - 危険: レッド (#ee4266)

#### 3.1.2 フォント
- **見出し**: 'Orbitron', sans-serif（SF感のあるフォント）
- **本文**: 'Roboto', sans-serif（可読性重視）
- **数値**: 'Courier New', monospace（管制室風の等幅フォント）

#### 3.1.3 レイアウト
- **ヘッダー**: 
  - システム名「SPACE CONTROL CENTER」
  - 現在時刻（UTC + ローカル時刻）
  - タブナビゲーション（NOW / TIME MACHINE / TONIGHT）

- **メインエリア**:
  - タブコンテンツ表示エリア
  - グリッドレイアウトで情報を整理

- **フッター**:
  - APIクレジット表示
  - 作成者情報

#### 3.1.4 レスポンシブデザイン
- デスクトップ（1024px以上）: 2カラムレイアウト
- タブレット（768px-1023px）: 1カラムレイアウト
- スマートフォン（767px以下）: 1カラム、縦スクロール

### 3.2 アニメーション要件

#### 3.2.1 NOW画面のアニメーション
1. **ISS軌道アニメーション**
   - CSS animationで地球の周りを周回するアイコン
   - 回転速度: 90分で1周（実際の周回時間）を早送り表示
   - 現在位置を光のパルスで強調（pulse animation）

2. **小惑星接近カウンター**
   - 距離に応じた色変化（CSS transition）
   - 危険度「高」の場合、画面が微振動（shake animation）

3. **データ更新時のフィードバック**
   - 更新ボタンクリック時にローディングスピナー
   - データ取得完了時にフェードイン

#### 3.2.2 TIME MACHINE画面のアニメーション
1. **タイムトラベル演出**
   - 日付入力後、画面全体が歪むエフェクト（CSS transform）
   - 星が流れるアニメーション（particles.jsライブラリ使用を検討）
   - 「タイムトラベル中...」のローディング表示

2. **写真表示アニメーション**
   - 写真がフェードインで表示
   - 説明文が下からスライドイン

#### 3.2.3 TONIGHT画面のアニメーション
1. **カウントダウンタイマー**
   - 秒数が変わるごとに数字が弾むアニメーション
   - 残り時間が少なくなると色が変化（緑→黄→赤）

2. **星のきらめきエフェクト**
   - 背景に小さな星がランダムに輝く（CSS animation）
   - opacity を 0-1 で繰り返し

3. **ISS軌跡描画**
   - Canvas APIを使用して空のマップ上に軌跡を描画
   - アニメーションで徐々に線が描かれる

#### 3.2.4 共通アニメーション
1. **ページ遷移**
   - タブ切り替え時にスムーズなフェード
   - 300msのtransition

2. **ホバーエフェクト**
   - ボタンやカードにマウスオーバー時に浮き上がる（box-shadow変化）
   - 色が明るくなる

3. **ローディング表示**
   - 各API呼び出し時にローディングスピナー
   - 宇宙船や衛星のアイコンを回転させる

### 3.3 パフォーマンス要件

#### 3.3.1 API呼び出し最適化
- **キャッシング**: 
  - NASA APOD: 同じ日付のデータは再取得しない（sessionStorageに保存）
  - ISS位置: 10秒間隔で更新
  - 小惑星データ: 1時間ごとに更新

- **エラーハンドリング**:
  - API呼び出し失敗時に再試行（最大3回）
  - タイムアウト設定（10秒）
  - エラー時にユーザーフレンドリーなメッセージ表示

#### 3.3.2 画像の最適化
- NASA APODの画像が大きい場合、読み込み中はローディング表示
- 画像のlazy loading（画面に表示されるまで読み込まない）

#### 3.3.3 初回読み込み
- 初回アクセス時はNOW画面をデフォルト表示
- 必要なデータのみ先に読み込み、他は遅延読み込み

### 3.4 セキュリティ要件

#### 3.4.1 APIキーの管理
- NASA APIキー: PHPファイルに記載（環境変数推奨だが、学習目的のため簡易的でOK）
- フロントエンドに直接APIキーを露出させない

#### 3.4.2 入力検証
- 日付入力: 1995年6月16日〜現在日までの範囲チェック
- XSS対策: ユーザー入力をサニタイズ

### 3.5 ブラウザ対応
- **対応ブラウザ**:
  - Google Chrome (最新版)
  - Firefox (最新版)
  - Safari (最新版)
  - Edge (最新版)

- **非対応**: Internet Explorer

---

## 4. データ仕様

### 4.1 NASA APOD API

**エンドポイント**:
```
https://api.nasa.gov/planetary/apod?api_key=YOUR_API_KEY&date=YYYY-MM-DD
```

**レスポンス例**:
```json
{
  "date": "2024-12-11",
  "explanation": "説明文...",
  "hdurl": "https://...",
  "media_type": "image",
  "title": "タイトル",
  "url": "https://..."
}
```

**使用項目**:
- `date`: 日付
- `title`: タイトル
- `explanation`: 説明文
- `url`: 画像URL
- `media_type`: メディアタイプ（image/video）

### 4.2 Open Notify ISS Location API

**エンドポイント**:
```
http://api.open-notify.org/iss-now.json
```

**レスポンス例**:
```json
{
  "iss_position": {
    "latitude": "45.1234",
    "longitude": "-122.5678"
  },
  "timestamp": 1234567890
}
```

**使用項目**:
- `iss_position.latitude`: 緯度
- `iss_position.longitude`: 経度
- `timestamp`: タイムスタンプ

### 4.3 Open Notify Astronauts API

**エンドポイント**:
```
http://api.open-notify.org/astros.json
```

**レスポンス例**:
```json
{
  "number": 7,
  "people": [
    {"name": "Name", "craft": "ISS"}
  ]
}
```

**使用項目**:
- `number`: 宇宙飛行士の総数
- `people`: 宇宙飛行士リスト
  - `name`: 名前
  - `craft`: 搭乗している宇宙船

### 4.4 Open Notify ISS Pass Times API

**エンドポイント**:
```
http://api.open-notify.org/iss-pass.json?lat=LATITUDE&lon=LONGITUDE&n=5
```

**パラメータ**:
- `lat`: 緯度
- `lon`: 経度
- `n`: 取得する通過回数（最大100）

**レスポンス例**:
```json
{
  "response": [
    {
      "duration": 300,
      "risetime": 1234567890
    }
  ]
}
```

**使用項目**:
- `response[].duration`: 観測可能時間（秒）
- `response[].risetime`: 出現時刻（UNIXタイムスタンプ）

### 4.5 NASA NeoWs API

**エンドポイント**:
```
https://api.nasa.gov/neo/rest/v1/feed?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD&api_key=YOUR_API_KEY
```

**レスポンス例**:
```json
{
  "near_earth_objects": {
    "2024-12-11": [
      {
        "name": "小惑星名",
        "close_approach_data": [
          {
            "close_approach_date": "2024-12-11",
            "relative_velocity": {
              "kilometers_per_hour": "12345"
            },
            "miss_distance": {
              "kilometers": "1234567"
            }
          }
        ],
        "estimated_diameter": {
          "meters": {
            "estimated_diameter_min": 100,
            "estimated_diameter_max": 200
          }
        },
        "is_potentially_hazardous_asteroid": false
      }
    ]
  }
}
```

**使用項目**:
- `name`: 小惑星名
- `close_approach_data[0].close_approach_date`: 接近日
- `close_approach_data[0].relative_velocity.kilometers_per_hour`: 速度
- `close_approach_data[0].miss_distance.kilometers`: 距離
- `estimated_diameter.meters`: 推定直径
- `is_potentially_hazardous_asteroid`: 危険フラグ

### 4.6 Sunrise-Sunset API

**エンドポイント**:
```
https://api.sunrise-sunset.org/json?lat=LATITUDE&lng=LONGITUDE&formatted=0
```

**パラメータ**:
- `lat`: 緯度
- `lng`: 経度
- `formatted`: 0（ISO8601形式で取得）

**レスポンス例**:
```json
{
  "results": {
    "sunrise": "2024-12-11T21:45:00+00:00",
    "sunset": "2024-12-11T07:42:00+00:00"
  }
}
```

**使用項目**:
- `results.sunrise`: 日の出時刻（UTC）
- `results.sunset`: 日没時刻（UTC）

---

## 5. ファイル構成

### 5.1 推奨ディレクトリ構造（最小構成）
```
/space-control-center/
├── Dockerfile                # Docker設定ファイル
├── index.php                 # メインファイル（全画面統合、MVC思想でコード整理）
├── api.php                   # APIエンドポイント（全API処理を統合）
├── style.css                 # 全スタイル統合（メイン + アニメーション）
├── script.js                 # 全JavaScript統合（共通 + 各画面用）
├── /data/
│   ├── birthdays.json        # 誕生日データ保存用（初期は空配列）
│   └── .htaccess             # 直接アクセス禁止設定
└── /assets/
    ├── iss-icon.png          # ISSアイコン
    ├── earth.png             # 地球画像
    └── logo.png              # ロゴ
```

### 5.2 各ファイルの役割

#### 5.2.1 Dockerfile
- PHP 8.x with Apache イメージを使用
- 必要な拡張機能のインストール（curl, json）
- ポート80を公開
- `/data`ディレクトリへの書き込み権限設定

**実行コマンド例**:
```bash
# ビルド
docker build -t space-control-center .

# 実行
docker run -d -p 8080:80 -v $(pwd):/var/www/html space-control-center

# アクセス
http://localhost:8080
```

#### 5.2.2 index.php
**構成**: MVC思想に基づく3セクション構造

1. **Model部分（上部）**
   - 設定値（APIキー、定数）
   - データ取得関数
   - ビジネスロジック

2. **Controller部分（中部）**
   - 初期データの準備
   - 条件分岐処理

3. **View部分（下部）**
   - HTML出力
   - CSS/JSの読み込み
   - 3つのタブ画面のHTML構造

**役割**:
- 全画面のHTMLを出力
- 初期表示データの準備
- JavaScriptへのデータ渡し

#### 5.2.3 api.php
**機能**: 全APIリクエストを処理する単一エンドポイント

**リクエスト形式**:
```
GET /api.php?action=ACTION_NAME&params...
```

**対応アクション**:
- `iss_location`: ISS現在位置取得
- `astronauts`: 宇宙飛行士情報取得
- `iss_pass`: ISS通過時刻取得
- `apod`: NASA APOD取得
- `neo`: 地球接近天体取得
- `sunrise_sunset`: 日没日の出取得
- `save_birthday`: 誕生日データ保存
- `get_birthdays`: 誕生日ギャラリーデータ取得
- `get_birthday_detail`: 特定の誕生日詳細取得

**構成**: MVC思想に基づく構造
1. **Model部分**: 外部API呼び出し関数、JSONファイル操作関数
2. **Controller部分**: アクション分岐、データ検証、エラーハンドリング
3. **View部分**: JSON形式でレスポンス出力

**レスポンス形式**:
```json
{
  "success": true,
  "data": {...},
  "error": null
}
```

**エラーハンドリング**:
- try-catchで例外をキャッチ
- タイムアウト設定（10秒）
- 最大3回の再試行

#### 5.2.4 style.css
**構成**: 
1. 共通スタイル（リセット、変数定義）
2. レイアウト（ヘッダー、タブ、フッター）
3. NOW画面専用スタイル
4. TIME MACHINE画面専用スタイル
5. TONIGHT画面専用スタイル
6. アニメーション定義（@keyframes）
7. レスポンシブデザイン（@media）

**CSS設計思想**:
- BEM記法を意識したクラス命名
- CSS変数でカラースキーム管理
- コメントでセクション区切り

#### 5.2.5 script.js
**構成**: 
1. グローバル変数・定数定義
2. ユーティリティ関数
3. API呼び出し関数
4. NOW画面用関数
5. TIME MACHINE画面用関数
6. TONIGHT画面用関数
7. 共通UI関数（タブ切り替え、モーダル等）
8. 初期化処理（DOMContentLoaded）

**設計思想**:
- 即時実行関数（IIFE）でスコープ管理
- async/awaitでAPI呼び出し
- コメントでセクション区切り

#### 5.2.6 data/birthdays.json
**初期内容**:
```json
{
  "birthdays": []
}
```

**アクセス制御**:
- PHPからの読み書きのみ許可
- 直接HTTPアクセスは`.htaccess`で禁止

**.htaccess内容**:
```apache
Order allow,deny
Deny from all
```

---

## 5.3 画面遷移図

### 5.3.1 全体遷移図

```
┌─────────────────┐
│  index.php      │ ← 初回アクセス（デフォルト）
│  (NOW画面)      │
└─────────────────┘
        ↕ ナビゲーションリンク
┌─────────────────┐
│ time-machine.php│
│ (入力フォーム)  │
└─────────────────┘
        ↓ POST: date (日付送信)
┌─────────────────┐
│ time-machine.php│
│ (APOD結果表示)  │
│ + 保存フォーム  │
└─────────────────┘
        ↓ POST: nickname, date, save (保存)
┌─────────────────┐
│ time-machine.php│
│ (保存完了)      │
│ + ギャラリー    │
└─────────────────┘
        ↓ GET: ?view=ID (詳細表示)
┌─────────────────┐
│ time-machine.php│
│ (詳細表示)      │
└─────────────────┘
        ↕ ナビゲーションリンク
┌─────────────────┐
│  tonight.php    │
│  (TONIGHT画面)  │
└─────────────────┘
```

### 5.3.2 NOW画面の遷移（index.php）

```
[初回アクセス]
    ↓
index.php
    ├─ ISS位置情報表示（初期データ）
    ├─ 小惑星情報表示（初期データ）
    └─ NASA APOD表示（初期データ）
    
[自動更新]
    ↓ JavaScript 10秒ごと
api.php?action=iss_location
    ↓ JSONレスポンス
index.php（ISS位置更新）

[ナビゲーション]
    ↓ リンククリック
time-machine.php または tonight.php
```

### 5.3.3 TIME MACHINE画面の詳細遷移

```
[ステップ1: 入力]
time-machine.php
    ├─ 誕生日入力フォーム
    └─ みんなの誕生日ギャラリー（カード一覧）
    
    ↓ フォーム送信（POST: date）
    
[ステップ2: 結果表示]
time-machine.php
    ├─ APOD写真・タイトル・説明
    ├─ 経過日数計算結果
    ├─ ニックネーム入力フォーム（保存用）
    └─ ギャラリー表示（継続）
    
    ↓ 保存ボタン（POST: nickname, date, save_action）
    
[ステップ3: 保存完了]
time-machine.php
    ├─ 「保存しました！」メッセージ
    ├─ 新しい入力フォーム（再検索可能）
    └─ ギャラリー表示（更新済み）
    
[ギャラリー閲覧]
time-machine.php
    ↓ カードクリック（GET: ?view=ID）
time-machine.php?view=ID
    ├─ 選択された誕生日の詳細表示
    ├─ APOD画像（大）
    └─ 「ギャラリーに戻る」リンク
    
    ↓ リンククリック
time-machine.php（ギャラリーに戻る）
```

### 5.3.4 TONIGHT画面の遷移（tonight.php）

```
[初回アクセス]
    ↓
tonight.php
    ↓ JavaScript: 位置情報取得
Geolocation API
    ↓ 緯度経度取得
tonight.php（位置情報を使用）
    ├─ 日没・日の出時刻表示
    ├─ ISS通過時刻表示
    └─ カウントダウン開始（JavaScript）
    
[ナビゲーション]
    ↓ リンククリック
index.php または time-machine.php
```

### 5.3.5 データの流れ

```
[APOD取得フロー]
time-machine.php (POST受信)
    ↓ 日付を受け取る
Model: getApodFromCache(date)
    ↓ data/birthdays.json 検索
    ├─ キャッシュHIT → JSONデータ返却
    └─ キャッシュMISS
        ↓
    Model: getApodFromAPI(date)
        ↓ NASA APOD API呼び出し
    APIレスポンス
        ↓
    time-machine.php（結果表示）

[誕生日保存フロー]
time-machine.php (POST: save_action)
    ↓
Model: saveBirthday(nickname, date, apodData)
    ↓
data/birthdays.json 読み込み
    ↓ 新規データ追加
data/birthdays.json 書き込み
    ↓ flock()でファイルロック
保存完了
    ↓
time-machine.php（成功メッセージ表示）
```

---

## 6. Docker環境構築

### 6.1 Dockerfile内容

```dockerfile
FROM php:8.2-apache

# 必要な拡張機能のインストール
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Apacheのmod_rewriteを有効化
RUN a2enmod rewrite

# 作業ディレクトリの設定
WORKDIR /var/www/html

# dataディレクトリの権限設定
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html/data && \
    chmod -R 755 /var/www/html/data

# ポート80を公開
EXPOSE 80

# Apacheをフォアグラウンドで実行
CMD ["apache2-foreground"]
```

### 6.2 ビルドと実行手順

```bash
# 1. プロジェクトディレクトリに移動
cd /path/to/space-control-center

# 2. Dockerイメージをビルド
docker build -t space-control-center .

# 3. コンテナを起動
docker run -d \
  --name space-control \
  -p 8080:80 \
  -v $(pwd):/var/www/html \
  space-control-center

# 4. ブラウザでアクセス
# http://localhost:8080        (index.phpにアクセス)
# または
# http://localhost:8080/index.php

# 5. コンテナの停止
docker stop space-control

# 6. コンテナの削除
docker rm space-control
```

### 6.3 アクセスURL

**仕様書に記載するURL形式**:
```
メイン画面（NOW）: http://localhost:8080/index.php
TIME MACHINE画面: http://localhost:8080/time-machine.php
TONIGHT画面: http://localhost:8080/tonight.php
```

### 6.4 開発時の注意事項

- **ボリュームマウント**: `-v $(pwd):/var/www/html` でローカルのファイル変更が即座に反映
- **ポート変更**: `-p 8080:80` の`8080`を変更すれば任意のポートで起動可能
- **ログ確認**: `docker logs space-control` でエラーログ確認
- **データ永続化**: `data/birthdays.json`はボリュームマウントされているためコンテナ削除後も保持される

---

## 7. 実装の優先順位

### Phase 1: 基本機能実装（必須）
1. **Docker環境構築**
   - Dockerfileの作成
   - data/birthdays.jsonの初期化
   - .htaccessの設定

2. **config.php: 共通設定**
   - APIキー定義
   - 定数定義
   - エラーハンドリング設定

3. **index.php: NOW画面**
   - MVC構造の実装
   - ナビゲーション（共通部分）
   - ISS位置情報表示
   - 宇宙飛行士リスト表示
   - 地球接近天体TOP5表示
   - NASA APOD表示
   - フッター（共通部分）

4. **time-machine.php: TIME MACHINE画面**
   - MVC構造の実装
   - ナビゲーション（index.phpからコピー）
   - 日付入力フォーム
   - POST処理（日付受信・APOD取得）
   - 結果表示
   - 誕生日保存機能
   - みんなの誕生日ギャラリー表示
   - GET処理（詳細表示: ?view=ID）
   - フッター（index.phpからコピー）

5. **tonight.php: TONIGHT画面**
   - MVC構造の実装
   - ナビゲーション（index.phpからコピー）
   - 位置情報取得
   - 日没・日の出時刻表示
   - ISS通過時刻表示
   - フッター（index.phpからコピー）

6. **api.php: AJAX用API**
   - ISS位置情報取得（NOW画面の自動更新用）
   - 地球接近天体取得
   - エラーハンドリング

7. **style.css: スタイル**
   - 共通スタイル（ナビゲーション、フッター）
   - 各画面のレイアウト
   - 基本的なカラースキーム

8. **script.js: JavaScript**
   - NOW画面の自動更新（10秒ごと）
   - TONIGHT画面のカウントダウン
   - 基本的なDOM操作

### Phase 2: アニメーション実装（高優先）
1. NOW画面のISS軌道アニメーション
2. TIME MACHINE画面のタイムトラベル演出
3. 誕生日ギャラリーのカードアニメーション
4. TONIGHT画面のカウントダウンタイマー
5. 共通のホバーエフェクト・ページ遷移アニメーション
6. ローディングスピナー

### Phase 3: 追加機能（時間があれば）
1. 観測条件スコアの計算
2. 天気情報の統合
3. より詳細なデータ表示
4. モバイル対応の最適化
5. 誕生日ギャラリーの検索・フィルター機能

---

## 8. テスト要件

### 8.1 機能テスト
- [ ] Docker環境が正常に起動するか
- [ ] 各API呼び出しが正常に動作するか
- [ ] エラー時に適切なメッセージが表示されるか
- [ ] 日付入力の範囲外チェックが機能するか
- [ ] 位置情報の拒否時にデフォルト値が使用されるか
- [ ] タブ切り替えが正常に動作するか
- [ ] 誕生日の保存が正常に動作するか
- [ ] JSONキャッシュから正しくデータが取得されるか
- [ ] みんなの誕生日ギャラリーが正常に表示されるか
- [ ] data/birthdays.jsonへの直接アクセスがブロックされるか

### 8.2 UI/UXテスト
- [ ] 各ブラウザで表示が崩れないか
- [ ] レスポンシブデザインが機能するか
- [ ] アニメーションがスムーズに動作するか
- [ ] 読み込み時間が許容範囲内か（3秒以内）
- [ ] 誕生日ギャラリーのカード表示が美しいか

### 8.3 パフォーマンステスト
- [ ] API呼び出しの回数が適切か（JSONキャッシュ活用）
- [ ] JSONファイルの読み書きが高速か
- [ ] メモリリークが発生していないか
- [ ] 大量の誕生日データでもパフォーマンスが劣化しないか

### 8.4 セキュリティテスト
- [ ] data/birthdays.jsonへの直接HTTPアクセスが拒否されるか
- [ ] XSS対策が適切に実装されているか
- [ ] JSONインジェクション対策がされているか
- [ ] APIキーがフロントエンドに露出していないか

---

## 9. 納品物

1. **ソースコード一式**
   - Dockerfile
   - config.php（共通設定、コメント付き）
   - index.php（NOW画面、MVC構造でコメント付き）
   - time-machine.php（TIME MACHINE画面、MVC構造でコメント付き）
   - tonight.php（TONIGHT画面、MVC構造でコメント付き）
   - api.php（AJAX用API、MVC構造でコメント付き）
   - style.css（セクション別コメント付き）
   - script.js（セクション別コメント付き）
   - data/birthdays.json（初期状態）
   - data/.htaccess
   - assets/（画像ファイル）

---

## 10. 参考情報

### 10.1 NASA APIキーの取得方法
1. https://api.nasa.gov/ にアクセス
2. メールアドレスを入力して「Signup」
3. 即座にAPIキーが発行される（デモキー: `DEMO_KEY` も使用可能だが制限あり）

### 10.2 開発時の注意事項
- DEMO_KEYは1時間あたり30リクエストまで
- 個人用APIキーは1時間あたり1000リクエストまで
- Open Notify APIはAPIキー不要
- Sunrise-Sunset APIはAPIキー不要
- JSONファイルへの同時書き込みに注意（ファイルロック推奨）

### 10.3 デバッグ用のヒント
- Chrome DevToolsのNetworkタブでAPI呼び出しを確認
- Console.logでデータ構造を確認
- PHPのerror_reportingを有効にして開発
- Dockerログ: `docker logs space-control`

### 10.4 MVC設計の実装方針
**フレームワークを使わないが、MVC思想は維持**:
- **Model**: データ取得・保存ロジック（関数として実装）
- **View**: HTML出力（PHPで生成またはJavaScriptでDOM操作）
- **Controller**: リクエスト処理・データ検証（if/switch文で実装）

**コード構造例（api.php）**:
```php
<?php
// ========== MODEL ==========
function fetchNasaApod($date, $apiKey) {
    // API呼び出しロジック
}

function saveBirthdayToJson($data) {
    // JSON保存ロジック
}

// ========== CONTROLLER ==========
$action = $_GET['action'] ?? '';

switch($action) {
    case 'apod':
        // データ検証
        // Model関数呼び出し
        // View（JSON出力）
        break;
    // ...
}

// ========== VIEW ==========
header('Content-Type: application/json');
echo json_encode($response);
?>
```

---

## 11. 追加アイデア（時間に余裕がある場合）

1. **音響効果**
   - ISS通過時にビープ音
   - ページ遷移時のSF効果音
   - ON/OFFトグル実装

2. **ダークモード切替**
   - ライトモード/ダークモード切替ボタン

3. **多言語対応**
   - 日本語/英語切替

4. **誕生日ギャラリーの拡張機能**
   - 月別フィルター（「6月生まれを表示」）
   - 人気順ソート（保存された回数）
   - 「いいね」機能

5. **共有機能**
   - 自分の誕生日の宇宙をSNSシェア
   - Twitter/Facebook シェアボタン

6. **統計情報表示**
   - 「現在○○人の誕生日が登録されています」
   - 「最も多い誕生月は○月です」
   - 月別の登録数グラフ

---

## 12. セキュリティとプライバシー考慮事項

### 12.1 プライバシー保護
- 誕生日の「年」は保存するが、ギャラリーでは非表示（月日のみ表示）
- ニックネームは任意入力（匿名性を保証）
- IPアドレスや個人識別情報は保存しない

### 12.2 データ保護
- data/birthdays.jsonへの直接アクセスを.htaccessでブロック
- XSS対策: ユーザー入力をhtmlspecialchars()でエスケープ
- JSONインジェクション対策: json_decode()のエラーハンドリング
- ファイルロック: flock()で同時書き込みを防止

### 12.3 API制限への対応
- JSONキャッシュ活用でAPI呼び出し回数を最小化
- 同じ日付のデータは再取得しない
- エラー時の適切なフォールバック処理

