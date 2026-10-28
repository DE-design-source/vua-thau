<?php
// Shared bits for the form endpoints: JSON input, validation helpers, throttle, mail.
const TO = 'hungkts@decox.vn, support@dezon.vn';
const FROM = '=?UTF-8?B?VnVhIFRo4bqndQ==?= <no-reply@vuathau.com.vn>'; // "Vua Thầu"
const PHONE_RE = '/^(0|\+84)\d{9,10}$/';
date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $msg) { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE); exit; }

// Returns the decoded JSON body; ends the request for non-POST, bad JSON, honeypot hits and repeat senders.
function input(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail(405, 'POST only');
    $f = json_decode(file_get_contents('php://input'), true);
    if (!is_array($f)) fail(400, 'Dữ liệu không hợp lệ.');
    if (!empty($f['website'])) { echo json_encode(['ok' => true, 'ref' => 'VT-000000']); exit; } // honeypot: pretend success
    // ponytail: per-IP file throttle in tmp, swap for a real rate limiter if spam gets through
    if (is_file(lock()) && time() - filemtime(lock()) < 30) fail(429, 'Bạn vừa gửi, vui lòng thử lại sau ít phút.');
    return $f;
}

function lock(): string { return sys_get_temp_dir() . '/vuathau-form-' . md5($_SERVER['REMOTE_ADDR'] ?? ''); }

// Single-line, length-capped field (no CR/LF reaches mail headers or the body layout).
function field(array $f, string $k, int $max = 200): string {
    return trim(preg_replace('/[\r\n\t]+/', ' ', mb_substr((string)($f[$k] ?? ''), 0, $max)));
}

function send(string $subject, array $lines, string $replyTo = ''): void {
    $lines[] = 'Thời gian: ' . date('d/m/Y H:i');
    $lines[] = 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '');
    $headers = "From: " . FROM . ($replyTo ? "\r\nReply-To: $replyTo" : '')
        . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    if (!mail(TO, '=?UTF-8?B?' . base64_encode($subject) . '?=', implode("\n", $lines) . "\n", $headers))
        fail(500, 'Chưa gửi được, vui lòng gọi 0931 168 749 hoặc thử lại sau.');
    touch(lock());
}

function ref(string $prefix): string { return $prefix . strtoupper(bin2hex(random_bytes(3))); }
