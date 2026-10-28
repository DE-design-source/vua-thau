<?php
// Top partners scraped from dezon.vn/nha-thau/tiem-nang/, cached so dezon.vn is hit at most every CACHE_TTL.
// ponytail: HTML scrape, breaks if dezon changes the card markup; switch to a dezon REST endpoint if one is added.
const SOURCE = 'https://dezon.vn/nha-thau/tiem-nang/';
const LIMIT = 8;
const CACHE_TTL = 3600;
$cache = sys_get_temp_dir() . '/vuathau-partners.json';

header('Content-Type: application/json; charset=utf-8');

if (is_file($cache) && time() - filemtime($cache) < CACHE_TTL) {
    readfile($cache);
    exit;
}

function scrape(string $html): array {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?>' . $html);
    $xp = new DOMXPath($doc);
    $abs = fn($u) => str_starts_with($u, '/') ? 'https://dezon.vn' . $u : $u;
    $out = [];
    foreach ($xp->query('//article[@data-card]') as $card) {
        $a = $xp->query('.//a[contains(@class,"dznt-name")]', $card)->item(0);
        $img = $xp->query('.//img[@data-src]', $card)->item(0);
        $stats = trim($xp->query('.//div[h3]/p', $card)->item(0)?->textContent ?? '');
        if (!$a || !preg_match('/^(.*?)\s*·\s*(\d+)\s*công trình\s*·\s*(\d+)\s*năm/u', $stats, $m)) continue;
        $tags = [];
        foreach ($xp->query('.//div[h3]/following-sibling::div[1]/span', $card) as $s) {
            $t = trim($s->textContent);
            $tags[] = mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
        }
        $city = trim($m[1]);
        if (preg_match('/hồ chí minh|hcm/iu', $city)) $city = 'TP. HCM';
        elseif (preg_match('/hà nội/iu', $city)) $city = 'Hà Nội';
        $out[] = [
            'name' => trim($a->textContent),
            'href' => $a->getAttribute('href'),
            'img' => $img ? $abs($img->getAttribute('data-src')) : '',
            'city' => $city,
            'projects' => (int)$m[2],
            'years' => (int)$m[3],
            'tags' => $tags,
        ];
        if (count($out) >= LIMIT) break;
    }
    return $out;
}

$ctx = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: vuathau.com.vn\r\n"]]);
$html = @file_get_contents(SOURCE, false, $ctx);
$list = $html ? scrape($html) : [];

if ($list) {
    $json = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($cache, $json, LOCK_EX);
    echo $json;
} elseif (is_file($cache)) {
    readfile($cache); // dezon down or markup changed: serve stale list
} else {
    http_response_code(502);
    echo '[]';
}
