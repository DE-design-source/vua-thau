<?php
// /nha-thau partner sign-up form -> email. Same rules as the client-side validation in nha-thau/index.html.
require __DIR__ . '/_lib.php';
const SERVICES = ['Xây nhà trọn gói', 'Xây biệt thự', 'Xây dựng phần thô', 'Cải tạo công trình', 'Hoàn thiện công trình', 'Thiết kế & xây dựng'];

$f = input();
$company = field($f, 'company'); $rep = field($f, 'rep'); $region = field($f, 'region');
$phone = preg_replace('/[\s.]/', '', field($f, 'phone', 20));
$email = field($f, 'email', 120);
$services = array_values(array_intersect(SERVICES, (array)($f['services'] ?? [])));

if (mb_strlen($company) < 2 || mb_strlen($rep) < 2 || mb_strlen($region) < 2
    || !preg_match(PHONE_RE, $phone)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !$services || empty($f['consent'])) fail(422, 'Thông tin chưa hợp lệ, vui lòng kiểm tra lại.');

$ref = ref('VT-DT-');
send("[Vua Thầu] Đăng ký đối tác: $company", [
    'Đăng ký đối tác mới từ vuathau.com.vn/nha-thau', '',
    "Mã hồ sơ: $ref",
    "Tên doanh nghiệp: $company",
    "Người đại diện: $rep",
    "Số điện thoại: $phone",
    "Email: $email",
    "Khu vực hoạt động: $region",
    'Hạng mục thi công: ' . implode(', ', $services),
], $email);
echo json_encode(['ok' => true, 'ref' => $ref]);
