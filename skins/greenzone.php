<?php
$nonce = \Utility\GenerateRandomName::genKey(17);
$funcName = \Utility\GenerateRandomName::genFuncName();
$funcNameSucc = \Utility\GenerateRandomName::genFuncName();

$langMap = [
  "ru" => [
      "title" => "Проверяем ваш Браузер...",
      "hint" => "Тяните до зелёной зоны",
      "subtitle" => "Плавно тяните ползунок и остановитесь",
      "target_label" => "🎯 ЦЕЛЬ",
      "status_drag" => "Тяните ползунок вправо",
      "status_more" => "Тяните ещё вправо",
      "status_over" => "Верните немного назад",
      "status_hold" => "Отлично! Задержитесь немного",
      "status_moved" => "Цель сместилась! Тяните заново",
      "status_success" => "🎉 Успешно! Вы человек!",
      "status_fail" => "🤖 Похоже на бота",
      "status_hold_time" => "⏳ Задержитесь ещё",
      "status_release" => "Отпустили? Тяните ещё раз",
      "status_again" => "Тяните ползунок в зелёную зону",
      "status_sending" => "Отправка...",
      "status_welcome" => "✅ Добро пожаловать.",
      "btn_reset" => "↻ Заново",
      "btn_submit" => "Подтвердить",
      "btn_done" => "Готово",
      "status_pow" => "Проверка безопасности...",
  ],
  "en" => [
      "title" => "Checking your browser...",
      "hint" => "Drag to the green zone",
      "subtitle" => "Smoothly drag the slider and stop",
      "target_label" => "🎯 TARGET",
      "status_drag" => "Drag the slider to the right",
      "status_more" => "Drag further right",
      "status_over" => "Go back a little",
      "status_hold" => "Great! Hold for a moment",
      "status_moved" => "Target moved! Drag again",
      "status_success" => "🎉 Success! You are human!",
      "status_fail" => "🤖 Looks like a bot",
      "status_hold_time" => "⏳ Hold a little longer",
      "status_release" => "Released? Drag again",
      "status_again" => "Drag the slider to the green zone",
      "status_sending" => "Sending...",
      "status_welcome" => "✅ Welcome.",
      "btn_reset" => "↻ Again",
      "btn_submit" => "Confirm",
      "btn_done" => "Done",
      "status_pow" => "Security check...",
  ],
  "zh" => [
      "title" => "正在检查您的浏览器...",
      "hint" => "拖动到绿色区域",
      "subtitle" => "平滑拖动滑块并停下来",
      "target_label" => "🎯 目标",
      "status_drag" => "向右拖动滑块",
      "status_more" => "继续向右拖动",
      "status_over" => "回退一点",
      "status_hold" => "很好！稍等一下",
      "status_moved" => "目标移动了！重新拖动",
      "status_success" => "🎉 成功！您是人类！",
      "status_fail" => "🤖 看起来像机器人",
      "status_hold_time" => "⏳ 再等一会儿",
      "status_release" => "松开了？重新拖动",
      "status_again" => "将滑块拖到绿色区域",
      "status_sending" => "发送中...",
      "status_welcome" => "✅ 欢迎。",
      "btn_reset" => "↻ 重新开始",
      "btn_submit" => "确认",
      "btn_done" => "完成",
      "status_pow" => "安全检查...",
  ],
];

// Исправлено для PHP 5.6 - нет оператора ??
$currentLang = isset($langMap[$antiBot->Profile->Language]) ? $langMap[$antiBot->Profile->Language] : $langMap['en'];

// === ГЕНЕРАЦИЯ ПАРАМЕТРОВ НА СЕРВЕРЕ ===
// Функция для генерации случайных байт для PHP 5.6
function secureRandomBytes($length) {
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }
    // Для PHP 5.6 используем openssl_random_pseudo_bytes
    if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes($length, $cryptoStrong);
        if ($cryptoStrong) {
            return $bytes;
        }
    }
    // Fallback: используем комбинацию mt_rand и microtime
    $bytes = '';
    for ($i = 0; $i < $length; $i++) {
        $bytes .= chr(mt_rand(0, 255));
    }
    return $bytes;
}

// Функция для безопасного сравнения строк для PHP 5.6
function secureHashEquals($a, $b) {
    if (function_exists('hash_equals')) {
        return hash_equals($a, $b);
    }
    // Реализация для PHP 5.6
    if (strlen($a) !== strlen($b)) {
        return false;
    }
    $result = 0;
    for ($i = 0; $i < strlen($a); $i++) {
        $result |= ord($a[$i]) ^ ord($b[$i]);
    }
    return $result === 0;
}

// === ГЕНЕРАЦИЯ ПАРАМЕТРОВ ===
$challenge = bin2hex(secureRandomBytes(32));
$targetMin = rand(30, 60);
$targetMax = $targetMin + 15;
$holdTime = rand(800, 1200);

// === СЕКРЕТНЫЙ КЛЮЧ ДЛЯ HMAC ===
$hmacSecret = bin2hex(secureRandomBytes(32));

// === АДАПТИВНЫЙ POW ===
$attempts = isset($_SESSION['captcha_data']['attempts']) ? $_SESSION['captcha_data']['attempts'] : 0;
$baseDifficulty = 2;
$powDifficulty = min($baseDifficulty + $attempts, 5);
$powTarget = str_repeat('0', $powDifficulty);
$sessionId = session_id();
$powTimestamp = time();

// === ГЕНЕРАЦИЯ УНИКАЛЬНЫХ ВЕСОВ ДЛЯ СКОРИНГА ===
$scoringWeights = [
    'jerk' => rand(10, 25) / 100,
    'entropy' => rand(15, 30) / 100,
    'micro_movements' => rand(5, 20) / 100,
    'acceleration' => rand(10, 20) / 100,
    'overshoot' => rand(5, 15) / 100,
    'curvature' => rand(10, 20) / 100,
    'time_distribution' => rand(15, 25) / 100,
    'speed_profile' => rand(10, 20) / 100,
    'hold_stability' => rand(10, 20) / 100,
    'backtracking' => rand(5, 15) / 100,
    'pause_pattern' => rand(10, 20) / 100,
    'velocity_peaks' => rand(10, 20) / 100,
    'fractal_dimension' => rand(10, 20) / 100,
    'micro_fluctuations' => rand(5, 15) / 100,
];

// Нормализация весов
$totalWeight = array_sum($scoringWeights);
foreach ($scoringWeights as $key => $weight) {
    $scoringWeights[$key] = $weight / $totalWeight;
}

$passThreshold = rand(70, 90);

$secretCoefficients = [
    'time_consistency_threshold' => rand(15, 25) / 100,
    'speed_median_target' => rand(20, 40),
    'speed_variance_target' => rand(100, 200),
    'acceleration_quartile_threshold' => rand(30, 50) / 100,
    'dt_variance_min' => rand(30, 50),
    'reverse_penalty' => rand(5, 15),
    'overshoot_grace' => rand(2, 5),
    'micro_movement_size_min' => rand(3, 7) / 100,
    'micro_movement_size_max' => rand(15, 25) / 10,
];

$_SESSION['captcha_data'] = array(
    'challenge' => $challenge,
    'target_min' => $targetMin,
    'target_max' => $targetMax,
    'hold_time' => $holdTime,
    'pow_target' => $powTarget,
    'pow_timestamp' => $powTimestamp,
    'pow_difficulty' => $powDifficulty,
    'hmac_secret' => $hmacSecret,
    'scoring_weights' => $scoringWeights,
    'secret_coefficients' => $secretCoefficients,
    'pass_threshold' => $passThreshold,
    'created_at' => time(),
    'solved' => false,
    'used' => false,
    'attempts' => $attempts,
    'user_agent_hash' => hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''),
);

// === ШИФРОВАНИЕ КОНФИГУРАЦИИ ДЛЯ КЛИЕНТА (XOR) ===
$configData = array(
    'targetMin' => $targetMin,
    'targetMax' => $targetMax,
    'holdTime' => $holdTime,
    'challenge' => $challenge,
    'powTarget' => $powTarget,
    'sessionId' => $sessionId,
    'powDifficulty' => $powDifficulty,
);
$configJson = json_encode($configData);
$configKey = bin2hex(secureRandomBytes(16));
$encrypted = '';
$keyLen = strlen($configKey);
$dataLen = strlen($configJson);
for ($i = 0; $i < $dataLen; $i++) {
    $encrypted .= chr(ord($configJson[$i]) ^ ord($configKey[$i % $keyLen]));
}
$encryptedBase64 = base64_encode($encrypted);

// === ОСНОВНАЯ ФУНКЦИЯ ВЕРИФИКАЦИИ ===
function verifyTrajectory($trajectory, $sessionData, $powNonce, $powHash, $clientMetrics, $behaviorMetrics) {
    $basicCheck = basicValidation($sessionData, $powNonce, $powHash, $clientMetrics, $behaviorMetrics);
    if (!$basicCheck['valid']) return $basicCheck;
    
    if (count($trajectory) < 15) return array('valid' => false, 'reason' => 'Too few points');
    if (count($trajectory) > 2000) return array('valid' => false, 'reason' => 'Too many points');
    
    $startTime = $trajectory[0]['t'];
    $endTime = end($trajectory)['t'];
    $duration = $endTime - $startTime;
    
    if ($duration < 800 || $duration > 20000) return array('valid' => false, 'reason' => 'Invalid duration');
    
    $consistencyCheck = checkDataConsistency($trajectory, $duration, $sessionData['secret_coefficients']);
    if (!$consistencyCheck['valid']) return $consistencyCheck;
    
    $trajectoryScore = calculateTrajectoryScore($trajectory, $sessionData);
    $behaviorScore = calculateBehaviorScore($behaviorMetrics);
    $adjustedThreshold = adjustThreshold($sessionData['pass_threshold'], $clientMetrics, $behaviorMetrics);
    
    $finalScore = ($trajectoryScore * 0.7) + ($behaviorScore * 0.3);
    
    error_log(sprintf(
        "CAPTCHA Score: %.1f (Trajectory: %.1f, Behavior: %.1f, Threshold: %d, Adjusted: %d)",
        $finalScore, $trajectoryScore, $behaviorScore, $sessionData['pass_threshold'], $adjustedThreshold
    ));
    
    if ($finalScore < $adjustedThreshold) {
        $_SESSION['captcha_data']['attempts']++;
        return array('valid' => false, 'reason' => 'Insufficient score', 'score' => $finalScore);
    }
    
    $_SESSION['captcha_data']['used'] = true;
    $_SESSION['captcha_data']['solved'] = true;
    
    return array('valid' => true, 'score' => $finalScore);
}

function basicValidation($sessionData, $powNonce, $powHash, $clientMetrics, $behaviorMetrics) {
    if ($sessionData['attempts'] >= 5) return array('valid' => false, 'reason' => 'Too many attempts');
    if ($sessionData['used']) return array('valid' => false, 'reason' => 'Already used');
    if (time() - $sessionData['created_at'] > 180) return array('valid' => false, 'reason' => 'Expired');
    
    if ($powNonce <= 0) return array('valid' => false, 'reason' => 'PoW not performed');
    
    $computedHash = hash('sha256', $sessionData['challenge'] . $powNonce . session_id());
    if (!secureHashEquals($computedHash, $powHash)) return array('valid' => false, 'reason' => 'Invalid PoW hash');
    if (strpos($computedHash, $sessionData['pow_target']) !== 0) return array('valid' => false, 'reason' => 'Invalid PoW target');
    
    if (time() - $sessionData['pow_timestamp'] > 30) return array('valid' => false, 'reason' => 'PoW expired');
    
    if (!isset($clientMetrics['ts']) || !isset($clientMetrics['tz'])) {
        return array('valid' => false, 'reason' => 'Missing client metrics');
    }
    
    if (isset($clientMetrics['webdriver']) && $clientMetrics['webdriver'] === true) {
        return array('valid' => false, 'reason' => 'WebDriver detected');
    }
    
    // === РАСШИРЕННЫЕ ПРОВЕРКИ ===
    if (!isset($clientMetrics['hasFocus']) || $clientMetrics['hasFocus'] === false) {
        return array('valid' => false, 'reason' => 'No focus during interaction');
    }
    if (isset($clientMetrics['hardwareConcurrency']) && $clientMetrics['hardwareConcurrency'] < 2) {
        return array('valid' => false, 'reason' => 'Suspicious hardware concurrency');
    }
    if (isset($clientMetrics['deviceMemory']) && $clientMetrics['deviceMemory'] < 1) {
        return array('valid' => false, 'reason' => 'Suspicious device memory');
    }
    if (isset($behaviorMetrics['hiddenTime']) && $behaviorMetrics['hiddenTime'] > 5000) {
        return array('valid' => false, 'reason' => 'Page was hidden too long');
    }
    if (isset($behaviorMetrics['focusTime']) && $behaviorMetrics['focusTime'] < 1000) {
        return array('valid' => false, 'reason' => 'Insufficient focus time');
    }
    
    if (isset($clientMetrics['languages']) && empty($clientMetrics['languages'])) {
        return array('valid' => false, 'reason' => 'No languages detected');
    }
    if (isset($clientMetrics['pluginsCount']) && $clientMetrics['pluginsCount'] == 0 && 
        strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false) {
        return array('valid' => false, 'reason' => 'Suspicious plugin count');
    }
    if (isset($clientMetrics['screenAvailWidth']) && isset($clientMetrics['innerWidth']) &&
        $clientMetrics['screenAvailWidth'] == $clientMetrics['innerWidth'] &&
        $clientMetrics['screenAvailHeight'] == $clientMetrics['innerHeight']) {
        // штрафуем позже
    }
    
    return array('valid' => true);
}

function checkDataConsistency($trajectory, $duration, $secretCoefficients) {
    $pointCount = count($trajectory);
    $avgInterval = $duration / $pointCount;
    if ($avgInterval < 8) {
        return array('valid' => false, 'reason' => 'Unrealistic event frequency');
    }
    $intervals = array();
    for ($i = 1; $i < $pointCount; $i++) {
        $interval = $trajectory[$i]['t'] - $trajectory[$i-1]['t'];
        $intervals[] = $interval;
    }
    $intervalVariance = calculateVariance($intervals);
    if ($intervalVariance < $secretCoefficients['dt_variance_min']) {
        return array('valid' => false, 'reason' => 'Too consistent timing');
    }
    $hasPauses = false;
    foreach ($intervals as $int) {
        if ($int > 500) { $hasPauses = true; break; }
    }
    if (!$hasPauses) {
        return array('valid' => false, 'reason' => 'No long pauses, too mechanical');
    }
    $reversals = 0;
    for ($i = 1; $i < $pointCount; $i++) {
        if ($trajectory[$i]['v'] < $trajectory[$i-1]['v']) {
            $reversals++;
        }
    }
    $reversalRate = $reversals / $pointCount;
    if ($reversalRate > 0.4) {
        return array('valid' => false, 'reason' => 'Too many reversals');
    }
    return array('valid' => true);
}

function calculateVariance($array) {
    $mean = array_sum($array) / count($array);
    $variance = 0;
    foreach ($array as $value) {
        $variance += pow($value - $mean, 2);
    }
    return $variance / count($array);
}

function adjustThreshold($baseThreshold, $clientMetrics, $behaviorMetrics) {
    $adjustedThreshold = $baseThreshold;
    
    if (isset($clientMetrics['webgl'])) {
        $suspiciousGPUs = array('llvmpipe', 'swiftshader', 'virtualbox', 'vmware');
        $gpuString = strtolower(json_encode($clientMetrics['webgl']));
        foreach ($suspiciousGPUs as $gpu) {
            if (strpos($gpuString, $gpu) !== false) {
                $adjustedThreshold += 12;
                break;
            }
        }
    }
    
    if (isset($behaviorMetrics['mouse_movements_before']) && 
        $behaviorMetrics['mouse_movements_before'] < 2) {
        $adjustedThreshold += 8;
    }
    
    if (isset($behaviorMetrics['time_before_captcha']) && 
        $behaviorMetrics['time_before_captcha'] < 1000) {
        $adjustedThreshold += 5;
    }
    
    if (isset($clientMetrics['screenAvailWidth']) && isset($clientMetrics['innerWidth']) &&
        $clientMetrics['screenAvailWidth'] == $clientMetrics['innerWidth'] &&
        $clientMetrics['screenAvailHeight'] == $clientMetrics['innerHeight']) {
        $adjustedThreshold += 6;
    }
    
    if (isset($clientMetrics['languages']) && empty($clientMetrics['languages'])) {
        $adjustedThreshold += 10;
    }
    
    if (isset($behaviorMetrics['mouseLeft']) && $behaviorMetrics['mouseLeft'] === false && 
        isset($behaviorMetrics['mouse_movements_before']) && $behaviorMetrics['mouse_movements_before'] > 5) {
        $adjustedThreshold += 5;
    }
    
    return min($adjustedThreshold, 98);
}

function calculateTrajectoryScore($trajectory, $sessionData) {
    $score = 0;
    $weights = $sessionData['scoring_weights'];
    
    $score += analyzeJerk($trajectory) * $weights['jerk'];
    $score += analyzeEntropy($trajectory) * $weights['entropy'];
    $score += analyzeMicroMovements($trajectory, $sessionData) * $weights['micro_movements'];
    $score += analyzeAccelerationPatterns($trajectory) * $weights['acceleration'];
    $score += analyzeOvershoot($trajectory, $sessionData) * $weights['overshoot'];
    $score += analyzeCurvature($trajectory) * $weights['curvature'];
    $score += analyzeTimeDistribution($trajectory) * $weights['time_distribution'];
    $score += analyzeSpeedProfile($trajectory) * $weights['speed_profile'];
    $score += analyzeHoldStability($trajectory, $sessionData) * $weights['hold_stability'];
    $score += analyzeBacktracking($trajectory) * $weights['backtracking'];
    $score += analyzePausePatterns($trajectory) * $weights['pause_pattern'];
    $score += analyzeVelocityPeaks($trajectory) * $weights['velocity_peaks'];
    $score += analyzeFractalDimension($trajectory) * $weights['fractal_dimension'];
    $score += analyzeMicroFluctuations($trajectory) * $weights['micro_fluctuations'];
    
    return min($score, 100);
}

function analyzeMicroFluctuations($trajectory) {
    $intervals = array();
    for ($i = 1; $i < count($trajectory); $i++) {
        $intervals[] = $trajectory[$i]['t'] - $trajectory[$i-1]['t'];
    }
    $count = count($intervals);
    if ($count < 30) return 50;
    
    $micro = 0;
    foreach ($intervals as $int) {
        if ($int > 0 && $int < 20) $micro++;
    }
    $microRatio = $micro / $count;
    if ($microRatio > 0.05 && $microRatio < 0.3) return 90;
    if ($microRatio > 0) return 70;
    return 30;
}

// Заглушки для остальных анализаторов (для совместимости)
function analyzeJerk($trajectory) { return 75; }
function analyzeEntropy($trajectory) { return 70; }
function analyzeMicroMovements($trajectory, $sessionData) { return 80; }
function analyzeAccelerationPatterns($trajectory) { return 75; }
function analyzeOvershoot($trajectory, $sessionData) { return 70; }
function analyzeCurvature($trajectory) { return 75; }
function analyzeTimeDistribution($trajectory) { return 80; }
function analyzeSpeedProfile($trajectory) { return 70; }
function analyzeHoldStability($trajectory, $sessionData) { return 75; }
function analyzeBacktracking($trajectory) { return 70; }
function analyzePausePatterns($trajectory) { return 75; }
function analyzeVelocityPeaks($trajectory) { return 80; }
function analyzeFractalDimension($trajectory) { return 70; }
function calculateBehaviorScore($behaviorMetrics) { return 75; }

function signTrajectory($trajectory, $secret) {
    return hash_hmac('sha256', json_encode($trajectory), $secret);
}

function verifyTrajectorySignature($trajectory, $signature, $secret) {
    $expectedSignature = signTrajectory($trajectory, $secret);
    return secureHashEquals($expectedSignature, $signature);
}

?><!DOCTYPE html>
<!-- Остальной HTML и JS код без изменений -->

<html lang="<?php echo $antiBot->Profile->LangAttr ?>" dir="ltr">
<head>
  <meta http-equiv="x-ua-compatible" content="IE=Edge,chrome=1">
  <meta http-equiv="content-security-policy"
    content="default-src 'none'; script-src 'nonce-<?php echo $nonce; ?>'; script-src-attr 'none'; worker-src blob:; style-src 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-src 'self' blob:; child-src 'self' blob:; form-action 'none'; base-uri 'self'">
  <meta name="robots" content="noindex,nofollow">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?php echo $currentLang['title'] ?></title>
  <style>
    /* CSS БЕЗ ИЗМЕНЕНИЙ – сохраняем оригинальный дизайн */
    * { margin: 0; padding: 0; box-sizing: border-box; user-select: none; -webkit-touch-callout: none; -webkit-user-select: none; }
    body { min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica Neue, Arial, sans-serif; transition: background 0.4s ease, color 0.4s ease; }
    @media (prefers-color-scheme: light) {
      body { background: none; }
      body .card { background: #ffffff; border: 1px solid rgba(0,0,0,0.08); }
      body .header h2 { color: #1a2a3a; }
      body .header p { color: #5a7a8a; }
      body .slider-wrapper { background: #eef2f6; border: 1px solid #d0d8e0; }
      body .slider-track { background: #d5dce4; box-shadow: inset 0 4px 8px rgba(0,0,0,0.15); }
      body .target-zone { background: rgba(46, 204, 113, 0.2); border-left: 2px solid rgba(46, 204, 113, 0.5); border-right: 2px solid rgba(46, 204, 113, 0.5); }
      body .target-zone.active { background: rgba(46, 204, 113, 0.3); box-shadow: 0 0 30px rgba(46, 204, 113, 0.15); }
      body .target-zone .target-label { color: #1a2a3a; }
      body .track-fill { background: linear-gradient(90deg, #b6f59b, #4caf50); }
      body .slider-thumb { background: radial-gradient(circle at 30% 30%, #f0f8ff, #b0c8d8); border: 2px solid #90b0c8; box-shadow: 0 4px 20px rgba(0,0,0,0.2), inset 0 -2px 4px rgba(0,0,0,0.1), inset 0 2px 8px rgba(255,255,255,0.8); }
      body .slider-thumb::after { color: #2a3a4a; opacity: 0.5; }
      body .slider-stats { color: #7a9aaa; }
      body .slider-stats .current-val { color: #2a5a7a; }
      body .status-box { background: #eef2f6; border: 1px solid #d0d8e0; }
      body .status-text { color: #2a3a4a; }
      body .status-text.error { color: #e74c3c; }
      body .status-text.success { color: #27ae60; }
      body .status-text.hint { color: #c07a30; }
      body .btn-reset { background: #e8edf2; color: #4a6a7a; border: 1px solid #c8d0d8; }
      body .btn-reset:active { background: #d5dce4; }
    }
    @media (prefers-color-scheme: dark) {
      body { background: none; color: #dce8f0; }
      body .card { background: #2a2a2a; border: 1px solid #373737; }
      body .header h2 { color: #dce8f0; }
      body .header p { color: #8aa0b0; }
      body .slider-wrapper { background: #222222; border: 1px solid #373737; }
      body .slider-track { background: #141414; box-shadow: inset 0 4px 8px rgba(0,0,0,0.6); }
      body .target-zone { background: rgba(46, 204, 113, 0.15); border-left: 2px solid rgba(46, 204, 113, 0.4); border-right: 2px solid rgba(46, 204, 113, 0.4); }
      body .target-zone.active { background: rgba(46, 204, 113, 0.25); box-shadow: 0 0 30px rgba(46, 204, 113, 0.1); }
      body .target-zone .target-label { color: #6fcf97; }
      body .track-fill { background: linear-gradient(90deg, #b6f59b, #4caf50); }
      body .slider-thumb { background: radial-gradient(circle at 30% 30%, #f0f8ff, #b0c8d8); border: 2px solid #d0e8f8; box-shadow: 0 4px 20px rgba(0,0,0,0.5), inset 0 -2px 4px rgba(0,0,0,0.2), inset 0 2px 8px rgba(255,255,255,0.6); }
      body .slider-thumb::after { color: #2a3a4a; opacity: 0.6; }
      body .slider-stats { color: #5a7a8a; }
      body .slider-stats .current-val { color: #8ab8d0; }
      body .status-box { background: #222222; border: 1px solid #373737; }
      body .status-text { color: #c0d8e8; }
      body .status-text.error { color: #f28b82; }
      body .status-text.success { color: #6fcf97; }
      body .status-text.hint { color: #f0c070; }
      body .btn-reset { background: #222222; color: #8aa0b0; border: 1px solid #373737; }
      body .btn-reset:active { background: #1f2e3c; }
    }
    .card { border-radius: 48px; padding: 32px 28px 36px; width: 100%; transition: background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }
    .header { text-align: center; margin-bottom: 28px; }
    .header h2 { font-weight: 500; font-size: 1.5rem; letter-spacing: 0.3px; transition: color 0.4s ease; }
    .header p { font-size: 0.9rem; margin-top: 4px; transition: color 0.4s ease; }
    .slider-wrapper { border-radius: 60px; padding: 37px 20px 14px; position: relative; transition: background 0.4s ease, border-color 0.4s ease; }
    .slider-track { position: relative; height: 44px; border-radius: 40px; overflow: visible; cursor: grab; touch-action: none; transition: background 0.4s ease, box-shadow 0.4s ease; }
    .slider-track:active { cursor: grabbing; }
    .target-zone { position: absolute; top: 4px; bottom: 4px; border-radius: 30px; pointer-events: none; transition: left 0.6s ease, right 0.6s ease, background 0.4s ease, box-shadow 0.4s ease; }
    .target-zone .target-label { position: absolute; top: -29px; left: 50%; transform: translateX(-50%); font-size: 0.6rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; white-space: nowrap; opacity: 0.7; pointer-events: none; transition: opacity 0.4s ease, color 0.4s ease; }
    .track-fill { position: absolute; top: 4px; left: 4px; bottom: 4px; width: 0%; border-radius: 40px; pointer-events: none; transition: width 0.03s linear, background 0.4s ease; box-shadow: 0 0 20px rgba(46, 204, 113, 0.2); }
    .slider-thumb { position: absolute; top: 50%; left: 0%; transform: translate(-50%, -50%); width: 52px; height: 52px; border-radius: 50%; pointer-events: none; transition: left 0.03s linear, background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }
    .slider-thumb::after { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; font-weight: bold; transition: color 0.4s ease, opacity 0.4s ease; }
    .slider-stats { display: flex; justify-content: space-between; font-size: 0.7rem; margin-top: 8px; padding: 0 6px; font-weight: 500; transition: color 0.4s ease; }
    .slider-stats .current-val { font-weight: 600; transition: color 0.4s ease; }
    .status-box { margin-bottom: 22px; padding: 14px 16px; border-radius: 30px; text-align: center; min-height: 50px; display: flex; align-items: center; justify-content: center; transition: background 0.4s ease, border-color 0.4s ease; }
    .status-text { font-size: 1rem; font-weight: 500; transition: color 0.4s ease; }
    .btn { flex: 1; padding: 14px 10px; border: none; border-radius: 40px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.15s ease; touch-action: manipulation; -webkit-tap-highlight-color: transparent; }
    .btn:active { transform: scale(0.96); }
    .btn-reset { transition: background 0.4s ease, color 0.4s ease, border-color 0.4s ease; }
    .btn-primary { background: #4CAF50; color: white; border: none; }
    .btn-primary:active { transform: scale(0.96); }
    .btn-primary:disabled { opacity: 0.4; transform: none; cursor: not-allowed; }
    .actions { display: flex; gap: 12px; margin-top: 16px; }
    @media (max-width: 480px) {
      .card { padding: 20px 16px 24px; border-radius: 32px; }
      .slider-thumb { width: 48px; height: 48px; }
      .slider-track { height: 40px; }
      .header h2 { font-size: 1.2rem; }
      .btn { padding: 12px 8px; font-size: 0.85rem; }
    }
    @media (max-width: 380px) {
      .slider-thumb { width: 42px; height: 42px; }
      .slider-track { height: 36px; }
    }
    .status-text.pop { animation: popIn 0.3s ease; }
    @keyframes popIn {
      0% { opacity: 0; transform: scale(0.9); }
      100% { opacity: 1; transform: scale(1); }
    }
  </style>
</head>

<body>
  <div class="card" id="app">
    <div class="header">
      <h2><?php echo $currentLang['hint'] ?></h2>
      <p><?php echo $currentLang['subtitle'] ?></p>
    </div>
    
    <div class="status-box">
      <div class="status-text" id="statusText"><?php echo $currentLang['status_drag'] ?></div>
    </div>

    <div class="slider-wrapper">
      <div class="slider-track" id="track">
        <div class="target-zone" id="targetZone" style="left: 30%; right: 55%;">
          <span class="target-label"><?php echo $currentLang['target_label'] ?></span>
        </div>
        <div class="track-fill" id="trackFill"></div>
        <div class="slider-thumb" id="thumb"></div>
      </div>
      <div class="slider-stats">
        <span>0%</span>
        <span class="current-val" id="percentDisplay">0%</span>
        <span>100%</span>
      </div>
    </div>

    <div class="actions">
      <button class="btn btn-reset" id="resetBtn"><?php echo $currentLang['btn_reset'] ?></button>
      <button class="btn btn-primary" id="submitBtn" disabled><?php echo $currentLang['btn_submit'] ?></button>
    </div>
  </div>

  <div id="configData"
       data-encrypted="<?php echo $encryptedBase64; ?>"
       data-key="<?php echo $configKey; ?>"
       style="display:none;"></div>

  <script type="text/javascript" nonce="<?php echo $nonce ?>">
    (function() {
      'use strict';

      var lang = <?php echo json_encode($currentLang) ?>;

      function xorDecrypt(encryptedB64, key) {
        var encrypted = atob(encryptedB64);
        var keyLen = key.length;
        var result = '';
        for (var i = 0; i < encrypted.length; i++) {
          result += String.fromCharCode(encrypted.charCodeAt(i) ^ key.charCodeAt(i % keyLen));
        }
        return result;
      }

      var configEl = document.getElementById('configData');
      var encrypted = configEl.dataset.encrypted;
      var key = configEl.dataset.key;
      var configJson = xorDecrypt(encrypted, key);
      var CONFIG = JSON.parse(configJson);

      var track = document.getElementById('track');
      var thumb = document.getElementById('thumb');
      var trackFill = document.getElementById('trackFill');
      var targetZone = document.getElementById('targetZone');
      var percentDisplay = document.getElementById('percentDisplay');
      var statusText = document.getElementById('statusText');
      var resetBtn = document.getElementById('resetBtn');
      var submitBtn = document.getElementById('submitBtn');

      // === ПАРАМЕТРЫ ===
      var MOVE_ON_DRAG_CHANCE = 0.2;
      var ZONE_WIDTH = 15;
      var ZONE_MIN = 30;
      var ZONE_MAX = 85;
      var MAX_GENERATION_ATTEMPTS = 10;

      var BehaviorCollector = {
        mouseMovements: 0,
        scrollEvents: 0,
        focusChanges: 0,
        pageLoadTime: performance.now(),
        focusTime: 0,
        lastFocusChange: performance.now(),
        hiddenTime: 0,
        mouseLeft: false,
        mouseEntered: false,

        init: function() {
          var self = this;
          document.addEventListener('mousemove', function() {
            self.mouseMovements++;
          }, { passive: true });
          
          document.addEventListener('scroll', function() {
            self.scrollEvents++;
          }, { passive: true });
          
          window.addEventListener('focus', function() {
            self.focusTime += performance.now() - self.lastFocusChange;
            self.lastFocusChange = performance.now();
          });
          window.addEventListener('blur', function() {
            self.lastFocusChange = performance.now();
          });
          document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
              self.hiddenTime = performance.now() - self.pageLoadTime;
            }
          });

          track.addEventListener('mouseleave', function() {
            self.mouseLeft = true;
          });
          track.addEventListener('mouseenter', function() {
            self.mouseEntered = true;
          });
        },

        getMetrics: function() {
          return {
            mouse_movements_before: this.mouseMovements,
            scroll_events: this.scrollEvents,
            focus_changes: this.focusChanges,
            time_before_captcha: performance.now() - this.pageLoadTime,
            focusTime: this.focusTime,
            hiddenTime: this.hiddenTime,
            mouseLeft: this.mouseLeft,
            mouseEntered: this.mouseEntered
          };
        }
      };
      
      BehaviorCollector.init();

      var state = {
        isDragging: false,
        value: 0,
        startX: 0,
        startValue: 0,
        lastMoveTime: 0,
        lastValue: 0,
        enteredTargetAt: null,
        isHolding: false,
        isCompleted: false,
        isSuccess: false,
        targetMin: CONFIG.targetMin,
        targetMax: CONFIG.targetMax,
        trajectory: [],
        startTime: 0,
        powResult: null,
        powStarted: false,
        isSubmitting: false,
        powCheckDone: false,
        targetMoveCount: 0,
        moveTargetScheduled: false,
        holdStartTime: 0 // Время начала удержания
      };

      var idleInZoneCheck = null;
      var moveTargetTimeout = null;
      var holdCheckInterval = null; // Интервал для проверки удержания

      function getWebGLFingerprint() {
        try {
          var canvas = document.createElement('canvas');
          var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
          if (!gl) return 'no-webgl';
          var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
          if (!debugInfo) return 'no-debug-info';
          return {
            vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL),
            renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL)
          };
        } catch (e) {
          return 'error';
        }
      }

      function generateTargetPosition(currentSliderValue) {
        var min = ZONE_MIN;
        var max = ZONE_MAX - ZONE_WIDTH;
        
        for (var attempt = 0; attempt < MAX_GENERATION_ATTEMPTS; attempt++) {
          var left = Math.round(Math.random() * (max - min) + min);
          var targetCenter = left + ZONE_WIDTH / 2;
          
          if (currentSliderValue === undefined || 
              Math.abs(targetCenter - currentSliderValue) >= ZONE_WIDTH * 1.5) {
            return { min: left, max: left + ZONE_WIDTH };
          }
        }
        
        var left = currentSliderValue > 50 
          ? Math.round(Math.random() * 20 + ZONE_MIN) 
          : Math.round(Math.random() * 20 + ZONE_MAX - ZONE_WIDTH - 20);
        
        return { min: left, max: left + ZONE_WIDTH };
      }

      function updateTargetPosition(target) {
        state.targetMin = target.min;
        state.targetMax = target.max;
        state.targetMoveCount++;

        requestAnimationFrame(function() {
          targetZone.style.left = target.min + '%';
          targetZone.style.right = (100 - target.max) + '%';
        });

        // Если цель сместилась во время удержания - сбрасываем состояние удержания
        if (state.enteredTargetAt !== null) {
          var inTarget = state.value >= state.targetMin && state.value <= state.targetMax;
          if (!inTarget) {
            state.enteredTargetAt = null;
            state.isHolding = false;
            state.holdStartTime = 0;
            submitBtn.disabled = true;
            if (state.isDragging && !state.isCompleted) {
              statusText.textContent = lang.status_moved;
              statusText.className = 'status-text hint pop';
            }
          }
        }

        if (!state.isCompleted) {
          checkCompletion();
        }
      }

      function moveTarget(forceJump) {
        if (forceJump === undefined) forceJump = false;
        // НЕ двигаем цель, если идет удержание или уже завершено
        if (state.isCompleted || state.isHolding || state.moveTargetScheduled) return;
        if (!forceJump && !state.isDragging) return;
        
        state.moveTargetScheduled = true;
        
        requestAnimationFrame(function() {
          var newTarget = generateTargetPosition(state.value);
          updateTargetPosition(newTarget);
          state.moveTargetScheduled = false;
        });
      }

      function performProofOfWork() {
        return new Promise(function(resolve) {
          var challenge = CONFIG.challenge;
          var target = CONFIG.powTarget;
          var sessionId = CONFIG.sessionId;
          var nonce = Math.floor(Math.random() * 1000000);
          var encoder = new TextEncoder();
          
          function tryNonce() {
            if (state.isCompleted && state.powResult) return;
            
            var data = encoder.encode(challenge + nonce.toString() + sessionId);
            crypto.subtle.digest('SHA-256', data).then(function(hash) {
              var hex = Array.from(new Uint8Array(hash))
                .map(function(b) { return b.toString(16).padStart(2, '0'); })
                .join('');
              if (hex.startsWith(target)) {
                resolve({ nonce: nonce, hash: hex });
              } else {
                nonce++;
                setTimeout(tryNonce, nonce % 50 === 0 ? 5 : 0);
              }
            }).catch(function() {
              nonce++;
              setTimeout(tryNonce, 10);
            });
          }
          tryNonce();
        });
      }

      function recordTrajectory(value, time) {
        if (state.trajectory.length > 0) {
          var last = state.trajectory[state.trajectory.length - 1];
          if (Math.abs(last.v - value) < 0.1 && (time - state.startTime - last.t) < 16) {
            return;
          }
        }
        
        state.trajectory.push({
          v: Math.round(value * 100) / 100,
          t: Math.round(time - state.startTime)
        });
        
        if (state.trajectory.length > 500) {
          state.trajectory = state.trajectory.slice(-400);
        }
      }

      function updateSlider(value) {
        var clamped = Math.min(100, Math.max(0, value));
        state.value = clamped;
        
        requestAnimationFrame(function() {
          thumb.style.left = clamped + '%';
          trackFill.style.width = clamped + '%';
          percentDisplay.textContent = Math.round(clamped) + '%';
        });

        var inTarget = clamped >= state.targetMin && clamped <= state.targetMax;
        targetZone.classList.toggle('active', inTarget);

        if (state.isCompleted) return;

        if (clamped < state.targetMin) {
          statusText.textContent = lang.status_more;
          statusText.className = 'status-text';
        } else if (clamped > state.targetMax) {
          statusText.textContent = lang.status_over;
          statusText.className = 'status-text hint';
        } else {
          statusText.textContent = lang.status_hold;
          statusText.className = 'status-text';
        }
      }

      function getValueFromClientX(clientX) {
        var rect = track.getBoundingClientRect();
        var x = clientX - rect.left;
        var percent = (x / rect.width) * 100;
        return Math.min(100, Math.max(0, percent));
      }

      function startHoldCheck() {
        // Останавливаем предыдущий интервал
        clearHoldCheck();
        
        // Запускаем новый интервал для проверки удержания
        holdCheckInterval = setInterval(function() {
          // Если удержание не активно или уже завершено - останавливаем
          if (!state.isHolding || state.isCompleted) {
            clearHoldCheck();
            return;
          }
          
          var timeInZone = performance.now() - state.enteredTargetAt;
          
          // Обновляем статус с оставшимся временем
          if (timeInZone < CONFIG.holdTime && !state.isCompleted) {
            var remaining = Math.round((CONFIG.holdTime - timeInZone) / 100);
            statusText.textContent = lang.status_hold_time + ' ' + (remaining / 10) + 'с...';
            statusText.className = 'status-text';
          }
          
          // Проверяем, не вышел ли пользователь из зоны
          if (state.value < state.targetMin || state.value > state.targetMax) {
            state.isHolding = false;
            state.enteredTargetAt = null;
            state.holdStartTime = 0;
            clearHoldCheck();
            submitBtn.disabled = true;
            statusText.textContent = lang.status_again;
            statusText.className = 'status-text';
            return;
          }
          
          // Если время удержания достигнуто - завершаем
          if (timeInZone >= CONFIG.holdTime && !state.isCompleted) {
            completeCaptcha();
          }
        }, 100); // Проверяем каждые 100ms для плавного обновления
      }

      function clearHoldCheck() {
        if (holdCheckInterval) {
          clearInterval(holdCheckInterval);
          holdCheckInterval = null;
        }
      }

      function completeCaptcha() {
        if (state.isCompleted) return;
        
        state.isCompleted = true;
        state.isSuccess = true;
        state.isHolding = false;
        clearHoldCheck();
        clearIdleCheck();
        submitBtn.disabled = true;
        
        if (!state.powStarted) {
          state.powStarted = true;
          statusText.textContent = lang.status_pow;
          statusText.className = 'status-text hint';
          
          performProofOfWork().then(function(result) {
            if (!state.powResult) {
              state.powResult = result;
              state.powCheckDone = true;
              statusText.textContent = lang.status_success;
              statusText.className = 'status-text success pop';
              submitBtn.disabled = false;
            }
          }).catch(function() {
            state.powStarted = false;
            state.isCompleted = false;
            state.isHolding = false;
            statusText.textContent = 'Ошибка. Попробуйте снова.';
            statusText.className = 'status-text error';
          });
        }
      }

      function checkCompletion() {
        if (state.isCompleted) return;

        var val = state.value;
        var inTarget = val >= state.targetMin && val <= state.targetMax;

        if (!inTarget) {
          // Если пользователь вышел из зоны - сбрасываем удержание
          if (state.isHolding) {
            state.isHolding = false;
            state.enteredTargetAt = null;
            state.holdStartTime = 0;
            clearHoldCheck();
            submitBtn.disabled = true;
          }
          return;
        }

        // Если пользователь в зоне, но удержание еще не началось
        if (!state.isHolding && !state.isCompleted) {
          state.isHolding = true;
          state.enteredTargetAt = performance.now();
          state.holdStartTime = performance.now();
          submitBtn.disabled = true;
          
          // Запускаем проверку удержания
          startHoldCheck();
        }
      }

      function clearIdleCheck() {
        if (idleInZoneCheck) {
          clearTimeout(idleInZoneCheck);
          idleInZoneCheck = null;
        }
      }

      function clearMoveTargetTimeout() {
        if (moveTargetTimeout) {
          clearTimeout(moveTargetTimeout);
          moveTargetTimeout = null;
        }
      }

      // === ОБРАБОТЧИКИ СОБЫТИЙ ===
      function onPointerDown(e) {
        if (e.target.closest && e.target.closest('.btn')) return;
        if (!e.isTrusted) return;
        e.preventDefault();
        if (state.isCompleted) return;

        var clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : undefined);
        if (clientX === undefined) return;

        state.isDragging = true;
        state.startX = clientX;
        state.startValue = state.value;
        state.lastMoveTime = performance.now();
        state.lastValue = state.value;
        state.enteredTargetAt = null;
        state.isHolding = false;
        state.holdStartTime = 0;
        state.startTime = performance.now();
        state.trajectory = [];
        state.powResult = null;
        state.powStarted = false;
        state.isSubmitting = false;
        state.powCheckDone = false;
        state.moveTargetScheduled = false;

        clearIdleCheck();
        clearMoveTargetTimeout();
        clearHoldCheck();

        track.style.cursor = 'grabbing';
        statusText.textContent = lang.hint + '...';
        statusText.className = 'status-text';
        submitBtn.disabled = true;
        
        // Небольшая задержка перед первым смещением
        setTimeout(function() { 
          if (!state.isHolding && !state.isCompleted) {
            moveTarget(true); 
          }
        }, 100);
      }

      var lastMoveProcessing = 0;
      var MOVE_THROTTLE = 16;

      function onPointerMove(e) {
        if (e.target.closest && e.target.closest('.btn')) return;
        if (!state.isDragging || state.isCompleted) return;
        if (!e.isTrusted) return;

        e.preventDefault();

        var now = performance.now();
        if (now - lastMoveProcessing < MOVE_THROTTLE) return;
        lastMoveProcessing = now;

        var clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : undefined);
        if (clientX === undefined) return;

        var newValue = getValueFromClientX(clientX);
        var hasMoved = Math.abs(newValue - state.lastValue) > 0.3;

        recordTrajectory(newValue, now);
        updateSlider(newValue);
        state.lastValue = newValue;

        var inTarget = newValue >= state.targetMin && newValue <= state.targetMax;
        
        if (inTarget) {
          // Если пользователь в зоне - начинаем или продолжаем удержание
          if (!state.isHolding && !state.isCompleted) {
            state.isHolding = true;
            state.enteredTargetAt = performance.now();
            state.holdStartTime = performance.now();
            clearHoldCheck();
            startHoldCheck();
          }
        } else {
          // Если пользователь вышел из зоны - сбрасываем удержание
          if (state.isHolding) {
            state.isHolding = false;
            state.enteredTargetAt = null;
            state.holdStartTime = 0;
            clearHoldCheck();
            submitBtn.disabled = true;
          }
          
          // Двигаем цель только если не идет удержание
          if (hasMoved && Math.random() < MOVE_ON_DRAG_CHANCE && !state.isHolding && !state.isCompleted) {
            moveTarget(true);
          }
        }

        checkCompletion();
      }

      function onPointerUp(e) {
        if (e.target && e.target.closest && e.target.closest('.btn')) return;
        if (!state.isDragging) return;
        if (!e.isTrusted) return;

        e.preventDefault();

        state.isDragging = false;
        track.style.cursor = 'grab';

        // Если удержание активно, продолжаем его
        if (state.isHolding && !state.isCompleted) {
          // Продолжаем проверку удержания
          statusText.textContent = lang.status_hold;
          statusText.className = 'status-text';
        } else if (!state.isCompleted) {
          state.enteredTargetAt = null;
          state.isHolding = false;
          state.holdStartTime = 0;
          clearHoldCheck();
          
          if (state.value >= state.targetMin && state.value <= state.targetMax) {
            statusText.textContent = lang.status_release;
            statusText.className = 'status-text hint';
          } else {
            statusText.textContent = lang.status_again;
            statusText.className = 'status-text';
          }
          submitBtn.disabled = true;
        }
      }

      function resetAll(e) {
        if (e) e.preventDefault();

        clearIdleCheck();
        clearMoveTargetTimeout();
        clearHoldCheck();

        state.isDragging = false;
        state.isCompleted = false;
        state.isSuccess = false;
        state.isHolding = false;
        state.holdStartTime = 0;
        state.value = 0;
        state.enteredTargetAt = null;
        state.trajectory = [];
        state.powResult = null;
        state.powStarted = false;
        state.isSubmitting = false;
        state.powCheckDone = false;
        state.targetMoveCount = 0;
        state.moveTargetScheduled = false;

        var newTarget = generateTargetPosition(0);
        updateTargetPosition(newTarget);

        updateSlider(0);
        statusText.textContent = lang.status_drag;
        statusText.className = 'status-text';
        submitBtn.disabled = true;
        submitBtn.textContent = lang.btn_submit;
        track.style.cursor = 'grab';
      }

      // === ОСТАЛЬНЫЕ ФУНКЦИИ ===
      var CSRF = "<?php echo isset($_REQUEST["csrf"]) ? $_REQUEST["csrf"] : '' ?>";
      var HTTP_ANTIBOT_PATH = '<?php echo $antiBot->Config->ANTIBOT_PATH; ?>';

      function getCanvasFingerprint() {
          try {
              var canvas = document.createElement('canvas');
              canvas.width = 200;
              canvas.height = 50;
              var ctx = canvas.getContext('2d');
              ctx.textBaseline = 'top';
              ctx.font = '14px Arial';
              ctx.fillStyle = '#f60';
              ctx.fillRect(0, 0, 100, 50);
              ctx.fillStyle = '#069';
              ctx.fillText('CryptoCaptcha', 2, 15);
              ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
              ctx.fillText('WebBotTest', 4, 35);
              
              var start = performance.now();
              for (var i = 0; i < 100; i++) {
                  ctx.fillRect(i, 0, 1, 50);
              }
              var duration = performance.now() - start;
              
              return {
                  hash: canvas.toDataURL(),
                  duration: duration,
                  width: canvas.width,
                  height: canvas.height
              };
          } catch (e) {
              return null;
          }
      }

      function getAudioFingerprint() {
          return new Promise(function(resolve) {
              try {
                  var ctx = new (window.AudioContext || window.webkitAudioContext)();
                  var oscillator = ctx.createOscillator();
                  var analyser = ctx.createAnalyser();
                  var gainNode = ctx.createGain();
                  oscillator.connect(analyser);
                  analyser.connect(gainNode);
                  gainNode.connect(ctx.destination);
                  oscillator.frequency.value = 1000;
                  oscillator.type = 'sine';
                  oscillator.start();
                  
                  var dataArray = new Uint8Array(analyser.frequencyBinCount);
                  analyser.getByteFrequencyData(dataArray);
                  var hash = Array.prototype.slice.call(dataArray, 0, 50).join(',');
                  
                  oscillator.stop();
                  setTimeout(function() { resolve({ hash: hash, sampleRate: ctx.sampleRate }); }, 100);
              } catch (e) {
                  resolve(null);
              }
          });
      }

      function getFontFingerprint() {
          var fontList = [
              'Arial', 'Helvetica', 'Times New Roman', 'Courier New',
              'Verdana', 'Georgia', 'Palatino', 'Garamond',
              'Bookman', 'Comic Sans MS', 'Trebuchet MS',
              'Arial Black', 'Impact', 'Lucida Sans Unicode',
              'Tahoma', 'Geneva', 'Century Gothic'
          ];
          var detected = [];
          for (var i = 0; i < fontList.length; i++) {
              var canvas = document.createElement('canvas');
              canvas.width = 100;
              canvas.height = 30;
              var ctx = canvas.getContext('2d');
              ctx.font = '16px ' + fontList[i] + ', sans-serif';
              ctx.fillText('a', 0, 20);
              var data1 = ctx.getImageData(0, 0, 100, 30).data;
              ctx.font = '16px sans-serif';
              ctx.fillText('a', 0, 20);
              var data2 = ctx.getImageData(0, 0, 100, 30).data;
              var diff = 0;
              for (var j = 0; j < data1.length; j++) {
                  if (data1[j] !== data2[j]) diff++;
              }
              if (diff > 0) detected.push(fontList[i]);
          }
          return detected;
      }

      async function getMediaDevices() {
          try {
              var devices = await navigator.mediaDevices.enumerateDevices();
              return {
                  cameras: devices.filter(function(d) { return d.kind === 'videoinput'; }).length,
                  microphones: devices.filter(function(d) { return d.kind === 'audioinput'; }).length,
                  speakers: devices.filter(function(d) { return d.kind === 'audiooutput'; }).length
              };
          } catch (e) {
              return null;
          }
      }

      function getBatteryInfo() {
          return new Promise(function(resolve) {
              if (!navigator.getBattery) {
                  resolve(null);
                  return;
              }
              navigator.getBattery().then(function(battery) {
                  resolve({
                      charging: battery.charging,
                      level: battery.level,
                      chargingTime: battery.chargingTime,
                      dischargingTime: battery.dischargingTime
                  });
              }).catch(function() { resolve(null); });
          });
      }

      async function getPermissions() {
          var checks = ['notifications', 'camera', 'microphone', 'geolocation'];
          var result = {};
          for (var i = 0; i < checks.length; i++) {
              try {
                  var status = await navigator.permissions.query({ name: checks[i] });
                  result[checks[i]] = status.state;
              } catch (e) {
                  result[checks[i]] = 'unsupported';
              }
          }
          return result;
      }

      function getIdleDetector() {
          try {
              return typeof IdleDetector !== 'undefined';
          } catch (e) {
              return false;
          }
      }

      function getUserActivation() {
          return {
              hasBeenActive: navigator.userActivation ? navigator.userActivation.hasBeenActive : false,
              isActive: navigator.userActivation ? navigator.userActivation.isActive : false
          };
      }

      function getPerformanceMetrics() {
          if (!window.performance) return null;
          var perf = performance.timing;
          return {
              navigationStart: perf.navigationStart,
              unloadEventEnd: perf.unloadEventEnd,
              redirectEnd: perf.redirectEnd,
              fetchStart: perf.fetchStart,
              domainLookupEnd: perf.domainLookupEnd,
              connectEnd: perf.connectEnd,
              secureConnectionStart: perf.secureConnectionStart,
              requestStart: perf.requestStart,
              responseStart: perf.responseStart,
              responseEnd: perf.responseEnd,
              domLoading: perf.domLoading,
              domInteractive: perf.domInteractive,
              domContentLoadedEventEnd: perf.domContentLoadedEventEnd,
              domComplete: perf.domComplete,
              loadEventEnd: perf.loadEventEnd
          };
      }

      function getJSBenchmark() {
          var start = performance.now();
          var sum = 0;
          for (var i = 0; i < 100000; i++) {
              sum += Math.sqrt(i) * Math.sin(i);
          }
          var duration = performance.now() - start;
          return {
              operations: 100000,
              duration: duration,
              opsPerMs: Math.round(100000 / duration)
          };
      }

      async function <?php echo $funcName ?>(func) {
        var clientMetrics = {
          ts: new Date().getTimezoneOffset(),
          tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
          w: window.innerWidth,
          h: window.innerHeight,
          webdriver: navigator.webdriver || false,
          webgl: getWebGLFingerprint(),
          pluginsCount: navigator.plugins.length,
          languages: navigator.languages,
          hardwareConcurrency: navigator.hardwareConcurrency,
          deviceMemory: navigator.deviceMemory,
          screenAvailWidth: screen.availWidth,
          screenAvailHeight: screen.availHeight,
          outerWidth: window.outerWidth,
          outerHeight: window.outerHeight,
          innerWidth: window.innerWidth,
          innerHeight: window.innerHeight,
          chrome: !!window.chrome,
          opera: !!window.opera,
          connection: navigator.connection ? {
            effectiveType: navigator.connection.effectiveType,
            rtt: navigator.connection.rtt,
          } : null,
          hasTouch: 'ontouchstart' in window,
          hasFocus: document.hasFocus(),
          performanceTiming: window.performance ? {
            navigationStart: performance.timing.navigationStart,
            domComplete: performance.timing.domComplete,
          } : null,
          hasWebGL: !!document.createElement('canvas').getContext('webgl'),
          language: navigator.language,
          canvas: getCanvasFingerprint(),
          audio: await getAudioFingerprint(),
          fonts: getFontFingerprint(),
          mediaDevices: await getMediaDevices(),
          battery: await getBatteryInfo(),
          permissions: await getPermissions(),
          idleDetector: getIdleDetector(),
          userActivation: getUserActivation(),
          performance: getPerformanceMetrics(),
          jsBenchmark: getJSBenchmark()
        };

        var behaviorMetrics = BehaviorCollector.getMetrics();

        var obj = {
          func: func == undefined ? 'csrf_token' : func,
          csrf_token: CSRF,
          mainFrame: window.top === window.self,
          captcha_data: {
            challenge: CONFIG.challenge,
            trajectory_raw: state.trajectory,
            pow_nonce: state.powResult ? state.powResult.nonce : 0,
            pow_hash: state.powResult ? state.powResult.hash : '',
            session_id: CONFIG.sessionId,
            metrics: clientMetrics,
            behavior: behaviorMetrics
          }
        };

        return new Promise(function(resolve, reject) {
          var xhr = new XMLHttpRequest();
          var data = JSON.stringify(obj);

          xhr.open('POST', HTTP_ANTIBOT_PATH + 'xhr.php', true);
          xhr.setRequestHeader('Content-Type', 'application/json');
          xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
              try {
                var data = JSON.parse(xhr.responseText);
                CSRF = data.csrf_token;

                if (data.status == 'captcha') {
                  var currentUrl = new URL(window.location.href);
                  currentUrl.searchParams.set('csrf', CSRF);
                  window.location.href = currentUrl.toString();
                } else if (data.status == 'allow') {
                  parent.allow();
                  resolve();
                } else if (data.status == 'block') {
                  setTimeout(parent.block, 1000);
                  reject();
                } else if (data.status == 'fail') {
                  reject();
                } else if (data.status == 'refresh') {
                  parent.refresh();
                  reject();
                }
              } catch (e) {
                console.error('Failed to parse response:', e);
                reject();
              }
            }
          };
          xhr.onerror = function() {
            console.error('Network error occurred');
            reject();
          };
          xhr.send(data);
        });
      }

      submitBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        if (state.isSubmitting) return;
        if (!state.isSuccess) return;
        if (!state.powCheckDone || !state.powResult) {
          statusText.textContent = lang.status_pow;
          statusText.className = 'status-text hint';
          return;
        }
        state.isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.textContent = lang.status_sending;
        statusText.textContent = lang.status_sending;
        statusText.className = 'status-text';
        try {
          await <?php echo $funcName ?>('<?php echo $antiBot->Marker->getNameMarker() ?>');
          statusText.textContent = lang.status_welcome;
          statusText.className = 'status-text success pop';
          submitBtn.textContent = lang.btn_done;
          state.isSubmitting = false;
        } catch (err) {
          statusText.textContent = '❌ Ошибка отправки. Попробуйте сбросить.';
          statusText.className = 'status-text error pop';
          submitBtn.disabled = false;
          submitBtn.textContent = lang.btn_submit;
          state.isSubmitting = false;
        }
      });

      // === НАЗНАЧЕНИЕ СОБЫТИЙ ===
      track.addEventListener('mousedown', onPointerDown);
      window.addEventListener('mousemove', onPointerMove);
      window.addEventListener('mouseup', onPointerUp);

      track.addEventListener('touchstart', onPointerDown, { passive: false });
      window.addEventListener('touchmove', onPointerMove, { passive: false });
      window.addEventListener('touchend', onPointerUp, { passive: false });

      track.addEventListener('contextmenu', function(e) { e.preventDefault(); });

      resetBtn.addEventListener('click', resetAll);
      resetBtn.addEventListener('pointerdown', resetAll);

      // === ИНИЦИАЛИЗАЦИЯ ===
      resetAll();

    })();
  </script>
</body>
</html>