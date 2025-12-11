/**
 * 宇宙コックピット - Space Control Center
 * メインJavaScript
 *
 * 構成:
 * 1. グローバル変数・定数
 * 2. ユーティリティ関数
 * 3. API呼び出し関数
 * 4. NOW画面用関数
 * 5. TIME MACHINE画面用関数
 * 6. TONIGHT画面用関数
 * 7. 共通UI関数
 * 8. 初期化処理
 */

// ========== 1. グローバル変数・定数 ==========

const API_ENDPOINT = 'api.php';
const ISS_UPDATE_INTERVAL = 10000; // 10秒

// 状態管理
let issUpdateTimer = null;
let clockUpdateTimer = null;
let countdownTimer = null;

// ========== 2. ユーティリティ関数 ==========

/**
 * APIリクエストを実行
 */
async function fetchAPI(action, params = {}) {
    const url = new URL(API_ENDPOINT, window.location.href);
    url.searchParams.append('action', action);

    for (const [key, value] of Object.entries(params)) {
        url.searchParams.append(key, value);
    }

    try {
        const response = await fetch(url);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * 数値をフォーマット（カンマ区切り）
 */
function formatNumber(num, decimals = 0) {
    return Number(num).toLocaleString('ja-JP', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

/**
 * 日付をフォーマット
 */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * 時刻をフォーマット
 */
function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('ja-JP', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

/**
 * HTMLエスケープ
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/**
 * ローディング表示
 */
function showLoading(element) {
    element.innerHTML = `
        <div class="loading">
            <div class="loading__spinner"></div>
        </div>
    `;
}

/**
 * アニメーション付きで値を更新
 */
function updateWithAnimation(element, newValue) {
    element.style.opacity = '0';
    element.style.transform = 'translateY(-5px)';

    setTimeout(() => {
        element.textContent = newValue;
        element.style.transition = 'all 0.3s ease';
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
    }, 150);
}

// ========== 3. API呼び出し関数 ==========

/**
 * ISS位置情報を取得
 */
async function getIssLocation() {
    return await fetchAPI('iss_location');
}

/**
 * 宇宙飛行士情報を取得
 */
async function getAstronauts() {
    return await fetchAPI('astronauts');
}

/**
 * 地球接近天体を取得
 */
async function getNearEarthObjects() {
    return await fetchAPI('neo');
}

/**
 * 日没・日の出情報を取得
 */
async function getSunriseSunset(lat, lon, date = null) {
    const params = { lat, lon };
    if (date) params.date = date;
    return await fetchAPI('sunrise_sunset', params);
}

// ========== 4. NOW画面用関数 ==========

/**
 * ISS位置を更新
 */
async function updateIssLocation() {
    const response = await getIssLocation();

    if (response.success && response.data) {
        const { latitude, longitude } = response.data;

        // 緯度・経度を更新
        const latElement = document.getElementById('iss-lat');
        const lonElement = document.getElementById('iss-lon');

        if (latElement) {
            updateWithAnimation(latElement, `${formatNumber(latitude, 4)}°`);
        }
        if (lonElement) {
            updateWithAnimation(lonElement, `${formatNumber(longitude, 4)}°`);
        }

        // ISSアイコンの位置を更新
        updateIssIcon(latitude, longitude);
    }
}

/**
 * ISSアイコンの位置を更新
 */
function updateIssIcon(lat, lon) {
    const issIcon = document.getElementById('iss-icon');
    if (!issIcon) return;

    // 緯度経度から円周上の位置を計算（簡易版）
    const angle = ((lon + 180) / 360) * 360;
    const radius = 80;

    issIcon.style.transform = `rotate(${angle}deg) translateX(${radius}px)`;
}

/**
 * ISS自動更新を開始
 */
function startIssUpdates() {
    if (issUpdateTimer) {
        clearInterval(issUpdateTimer);
    }

    // 初回更新
    updateIssLocation();

    // 定期更新
    issUpdateTimer = setInterval(updateIssLocation, ISS_UPDATE_INTERVAL);
}

/**
 * ISS自動更新を停止
 */
function stopIssUpdates() {
    if (issUpdateTimer) {
        clearInterval(issUpdateTimer);
        issUpdateTimer = null;
    }
}

// ========== 5. TIME MACHINE画面用関数 ==========

/**
 * タイムトラベルアニメーション
 */
function playTimeTravelAnimation() {
    const body = document.body;
    body.classList.add('time-traveling');

    setTimeout(() => {
        body.classList.remove('time-traveling');
    }, 1500);
}

/**
 * フォーム送信時のハンドラー（タイムトラベル演出）
 */
function handleTimeMachineSubmit(event) {
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="loading__spinner" style="width: 20px; height: 20px; border-width: 2px;"></span>
            タイムトラベル中...
        `;
    }

    playTimeTravelAnimation();
}

// ========== 6. TONIGHT画面用関数 ==========

/**
 * 位置情報を取得
 */
function getGeolocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('お使いのブラウザは位置情報に対応していません'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                resolve({
                    lat: position.coords.latitude,
                    lon: position.coords.longitude
                });
            },
            (error) => {
                reject(error);
            },
            {
                enableHighAccuracy: false,
                timeout: 10000,
                maximumAge: 300000 // 5分キャッシュ
            }
        );
    });
}

/**
 * 位置情報取得ボタンのハンドラー
 */
async function handleGetLocation() {
    const statusText = document.getElementById('location-text');
    const btn = document.getElementById('get-location-btn');

    if (btn) btn.disabled = true;
    if (statusText) statusText.textContent = '位置情報を取得中...';

    try {
        const location = await getGeolocation();

        if (statusText) {
            statusText.textContent = `緯度: ${location.lat.toFixed(4)}, 経度: ${location.lon.toFixed(4)}`;
        }

        // データを再取得
        await updateTonightData(location.lat, location.lon);

    } catch (error) {
        console.error('位置情報取得エラー:', error);
        if (statusText) {
            statusText.textContent = '位置情報を取得できませんでした（東京の情報を表示中）';
        }
    } finally {
        if (btn) btn.disabled = false;
    }
}

/**
 * TONIGHT画面のデータを更新
 */
async function updateTonightData(lat, lon) {
    // 日没・日の出情報を取得
    const sunResponse = await getSunriseSunset(lat, lon);

    if (sunResponse.success && sunResponse.data) {
        const sunsetTime = document.getElementById('sunset-time');
        const sunriseTime = document.getElementById('sunrise-time');

        if (sunResponse.data.sunset && sunsetTime) {
            sunsetTime.textContent = formatTime(sunResponse.data.sunset);
        }

        // 明日の日の出
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];

        const tomorrowSunResponse = await getSunriseSunset(lat, lon, tomorrowStr);
        if (tomorrowSunResponse.success && tomorrowSunResponse.data && sunriseTime) {
            sunriseTime.textContent = formatTime(tomorrowSunResponse.data.sunrise);
        }

        // カウントダウン更新
        updateDarknessCountdown(sunResponse.data);
    }
}

/**
 * 暗闘までのカウントダウンを更新
 */
function updateDarknessCountdown(sunData) {
    const countdownElement = document.getElementById('darkness-countdown');
    if (!countdownElement || !sunData.astronomical_twilight_end) return;

    const twilightEnd = new Date(sunData.astronomical_twilight_end);
    const now = new Date();

    if (twilightEnd > now) {
        const diff = twilightEnd - now;
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        countdownElement.textContent = `あと ${hours}時間 ${minutes}分`;
    } else {
        countdownElement.textContent = '現在は完全な暗闘です';
        countdownElement.style.color = 'var(--color-success)';
    }
}

/**
 * カウントダウンタイマーを開始
 */
function startCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }

    countdownTimer = setInterval(() => {
        if (window.TONIGHT_DATA && window.TONIGHT_DATA.sunData) {
            updateDarknessCountdown(window.TONIGHT_DATA.sunData);
        }
    }, 60000); // 1分ごと
}

// ========== 7. 共通UI関数 ==========

/**
 * テキストを日本語に翻訳
 */
async function translateText(text) {
    try {
        const response = await fetch(`${API_ENDPOINT}?action=translate&text=${encodeURIComponent(text)}`);
        const data = await response.json();
        if (data.success && data.data) {
            return data.data.translated;
        }
        return null;
    } catch (error) {
        console.error('Translation error:', error);
        return null;
    }
}

/**
 * 翻訳ボタンのハンドラー
 */
async function handleTranslateClick(event) {
    const btn = event.currentTarget;
    const targetId = btn.dataset.target;
    const targetElement = document.getElementById(targetId);

    if (!targetElement) return;

    // 既に翻訳済みの場合は原文に戻す
    if (btn.classList.contains('translated')) {
        const originalText = targetElement.dataset.original;
        if (originalText) {
            targetElement.textContent = originalText;
            targetElement.classList.remove('translated');
            btn.classList.remove('translated');
            btn.innerHTML = '<span class="btn__icon">&#127760;</span> 日本語に翻訳';
        }
        return;
    }

    // 翻訳中の状態にする
    btn.classList.add('translating');
    btn.disabled = true;
    btn.innerHTML = '<span class="btn__icon">&#8987;</span> 翻訳中...';

    const originalText = targetElement.dataset.original || targetElement.textContent;
    targetElement.dataset.original = originalText;

    const translatedText = await translateText(originalText);

    if (translatedText) {
        targetElement.textContent = translatedText;
        targetElement.classList.add('translated');
        btn.classList.remove('translating');
        btn.classList.add('translated');
        btn.innerHTML = '<span class="btn__icon">&#127760;</span> 原文を表示';
    } else {
        btn.innerHTML = '<span class="btn__icon">&#10060;</span> 翻訳失敗';
        setTimeout(() => {
            btn.innerHTML = '<span class="btn__icon">&#127760;</span> 日本語に翻訳';
        }, 2000);
    }

    btn.disabled = false;
    btn.classList.remove('translating');
}

/**
 * 翻訳ボタンのイベントリスナーを設定
 */
function setupTranslateButtons() {
    const translateBtns = document.querySelectorAll('.btn--translate');
    translateBtns.forEach(btn => {
        btn.addEventListener('click', handleTranslateClick);
    });
}

/**
 * 時計を更新
 */
function updateClock() {
    const now = new Date();

    // UTC時刻
    const utcElement = document.getElementById('utc-time');
    if (utcElement) {
        const utcTime = now.toUTCString().split(' ')[4];
        utcElement.textContent = utcTime;
    }

    // ローカル時刻
    const localElement = document.getElementById('local-time');
    if (localElement) {
        localElement.textContent = now.toLocaleTimeString('ja-JP');
    }
}

/**
 * 時計の自動更新を開始
 */
function startClock() {
    updateClock();
    clockUpdateTimer = setInterval(updateClock, 1000);
}

/**
 * ページ離脱時のクリーンアップ
 */
function cleanup() {
    stopIssUpdates();

    if (clockUpdateTimer) {
        clearInterval(clockUpdateTimer);
    }

    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
}

/**
 * スムーズスクロール
 */
function smoothScrollTo(element) {
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// ========== 8. 初期化処理 ==========

document.addEventListener('DOMContentLoaded', () => {
    // 時計を開始
    startClock();

    // 翻訳ボタンのセットアップ
    setupTranslateButtons();

    // ページ離脱時のクリーンアップ
    window.addEventListener('beforeunload', cleanup);

    // ページに応じた初期化
    const currentPage = window.location.pathname;

    // NOW画面（index.php）
    if (currentPage.includes('index.php') || currentPage.endsWith('/')) {
        // 初期データがある場合はそれを使用
        if (window.SPACE_DATA) {
            // ISS自動更新を開始
            startIssUpdates();
        }
    }

    // TIME MACHINE画面
    if (currentPage.includes('time-machine.php')) {
        // フォーム送信時のアニメーション
        const birthdayForm = document.getElementById('birthday-form');
        const exploreForm = document.getElementById('explore-form');

        if (birthdayForm) {
            birthdayForm.addEventListener('submit', handleTimeMachineSubmit);
        }
        if (exploreForm) {
            exploreForm.addEventListener('submit', handleTimeMachineSubmit);
        }
    }

    // TONIGHT画面
    if (currentPage.includes('tonight.php')) {
        // 位置情報ボタン
        const getLocationBtn = document.getElementById('get-location-btn');
        if (getLocationBtn) {
            getLocationBtn.addEventListener('click', handleGetLocation);
        }

        // カウントダウン開始
        if (window.TONIGHT_DATA) {
            startCountdown();
        }
    }

    // 画像の遅延読み込み（Intersection Observer使用）
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    }

    // カードのホバーエフェクト強化
    const cards = document.querySelectorAll('.neo-card, .gallery__card, .stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
});

// タブ切り替え時のVisibility API対応
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // タブが非表示になったら更新を停止
        stopIssUpdates();
    } else {
        // タブが表示されたら更新を再開（NOW画面の場合）
        const currentPage = window.location.pathname;
        if (currentPage.includes('index.php') || currentPage.endsWith('/')) {
            startIssUpdates();
        }
    }
});
