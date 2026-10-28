<?php
// Homepage "Gửi nhu cầu xây nhà" form -> email. Same rules as leadErrors() in index.html.
require __DIR__ . '/_lib.php';

$f = input();
$name = field($f, 'name'); $city = field($f, 'city');
$phone = preg_replace('/[\s.]/', '', field($f, 'phone', 20));
$service = field($f, 'service'); $type = field($f, 'type'); $budget = field($f, 'budget');
$note = trim(mb_substr((string)($f['note'] ?? ''), 0, 2000)); // body only, multi-line allowed

if (mb_strlen($name) < 2 || mb_strlen($city) < 2 || !preg_match(PHONE_RE, $phone) || empty($f['consent']))
    fail(422, 'Thông tin chưa hợp lệ, vui lòng kiểm tra lại.');

$ref = ref('VT-');
send("[Vua Thầu] Yêu cầu xây nhà: $name – $city", [
    'Yêu cầu mới từ chủ nhà trên vuathau.com.vn', '',
    "Mã yêu cầu: $ref",
    "Họ tên: $name",
    "Số điện thoại: $phone",
    "Hạng mục: $service",
    "Loại công trình: $type",
    "Tỉnh/Thành phố: $city",
    "Ngân sách dự kiến: $budget",
    'Mô tả thêm: ' . ($note === '' ? '(không có)' : "\n$note"),
]);
echo json_encode(['ok' => true, 'ref' => $ref]);
