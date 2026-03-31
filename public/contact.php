<?php
/**
 * ZiPZAP お問い合わせフォーム処理
 * reCAPTCHA v3 + mb_send_mail (Xサーバー対応)
 */

// 機密設定を別ファイルから読み込む（contact-config.php は .gitignore 対象）
require_once __DIR__ . '/contact-config.php';

define('SITE_NAME', 'ZiPZAP');
define('RECAPTCHA_THRESHOLD', 0.5); // スコア閾値（0.0〜1.0、低いほど人間判定が厳しい）

// ---------------------------------------------------------------------------
// 基本設定
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// POST以外は弾く
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

// ---------------------------------------------------------------------------
// 入力値の取得・サニタイズ
$name    = trim(strip_tags($_POST['name']    ?? ''));
$company = trim(strip_tags($_POST['company'] ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$subject = trim(strip_tags($_POST['subject'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));
$token   = trim($_POST['recaptcha_token']    ?? '');

// ---------------------------------------------------------------------------
// バリデーション
$errors = [];
if (mb_strlen($name) < 1)    $errors[] = 'お名前を入力してください。';
if (mb_strlen($name) > 100)  $errors[] = 'お名前は100文字以内で入力してください。';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'メールアドレスの形式が正しくありません。';
if (mb_strlen($message) < 10) $errors[] = 'ご相談内容は10文字以上入力してください。';
if (mb_strlen($message) > 3000) $errors[] = 'ご相談内容は3000文字以内で入力してください。';
if (empty($token)) $errors[] = 'reCAPTCHA トークンがありません。';

if (!empty($errors)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => implode(' ', $errors)]));
}

// ---------------------------------------------------------------------------
// reCAPTCHA v3 検証
$recaptcha_response = file_get_contents(
    'https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
        'secret'   => RECAPTCHA_SECRET,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ])
);

if ($recaptcha_response === false) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'reCAPTCHA の検証に失敗しました。']));
}

$recaptcha = json_decode($recaptcha_response, true);

if (
    empty($recaptcha['success']) ||
    $recaptcha['action'] !== 'contact' ||
    $recaptcha['score'] < RECAPTCHA_THRESHOLD
) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'スパムと判定されました。お手数ですが再度お試しください。']));
}

// ---------------------------------------------------------------------------
// メール本文の組み立て
$mail_body = implode("\n", [
    SITE_NAME . ' お問い合わせフォームから新着メッセージが届きました。',
    str_repeat('-', 40),
    '【お名前】',
    $name,
    '',
    '【会社名・屋号】',
    $company ?: '（未入力）',
    '',
    '【メールアドレス】',
    $email,
    '',
    '【ご相談の種類】',
    $subject ?: '（未選択）',
    '',
    '【ご相談内容】',
    $message,
    str_repeat('-', 40),
    '送信日時: ' . date('Y-m-d H:i:s'),
    'IPアドレス: ' . ($_SERVER['REMOTE_ADDR'] ?? '不明'),
]);

// 自動返信メール本文
$auto_reply_body = implode("\n", [
    $name . ' 様',
    '',
    'お問い合わせいただきありがとうございます。',
    SITE_NAME . ' です。',
    '',
    '以下の内容でお問い合わせを受け付けました。',
    '2営業日以内にご返信いたします。',
    '',
    str_repeat('-', 40),
    '【ご相談内容】',
    $message,
    str_repeat('-', 40),
    '',
    '※ このメールは自動送信です。返信はできません。',
    '※ お急ぎの場合はお手数ですが再度ご連絡ください。',
    '',
    SITE_NAME,
]);

// ---------------------------------------------------------------------------
// メール送信（mb_send_mail はXサーバーで文字化けしにくい）
mb_language('Japanese');
mb_internal_encoding('UTF-8');

$mail_subject      = '[' . SITE_NAME . '] お問い合わせが届きました（' . $name . ' 様）';
$auto_reply_subject = '[' . SITE_NAME . '] お問い合わせを受け付けました';

$headers = implode("\r\n", [
    'From: ' . SITE_NAME . ' <' . FROM_EMAIL . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion(),
]);

$auto_headers = implode("\r\n", [
    'From: ' . SITE_NAME . ' <' . FROM_EMAIL . '>',
    'X-Mailer: PHP/' . phpversion(),
]);

// 管理者宛て送信
$sent = mb_send_mail(TO_EMAIL, $mail_subject, $mail_body, $headers);

// 自動返信
if ($sent) {
    mb_send_mail($email, $auto_reply_subject, $auto_reply_body, $auto_headers);
}

if (!$sent) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'メールの送信に失敗しました。お手数ですが時間をおいて再度お試しください。']));
}

// ---------------------------------------------------------------------------
// 成功レスポンス
exit(json_encode(['success' => true, 'message' => '送信完了しました。']));
