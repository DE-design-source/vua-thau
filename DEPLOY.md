# Deploy vuathau.com.vn

Upload the **contents** of `site/` to the docroot. Needs **PHP ≥ 8.0** (with `dom`, `mbstring`) and a working `mail()` — no Node, no DB.

```
site/
  index.html            → vuathau.com.vn/            Trang chủ (chủ nhà)
  assets/
  nha-thau/index.html   → vuathau.com.vn/nha-thau/   Trang đối tác (nhà thầu); menu "Dành cho nhà thầu" links here
  nha-thau/assets/
  api/partners.php      "Hồ sơ nhà thầu / Đối tác tiêu biểu" on both pages: top 8 from dezon.vn/nha-thau/tiem-nang/, cached 1h
  api/lead.php          homepage form "Gửi nhu cầu xây nhà"  → email
  api/register.php      /nha-thau form "Đăng ký đối tác"      → email
  api/_lib.php          shared: recipients, sender, throttle, mail()
```

Recipients: `hungkts@decox.vn, support@dezon.vn` (`TO` in `api/_lib.php`). Sender: `no-reply@vuathau.com.vn`.

If dezon.vn is down or changes its markup, the partner section falls back to the stale cache, then to the list hard-coded in the page.

## Server (waiting on chị Lan's config)

1. DNS: `vuathau.com.vn` + `www` → A record to the Dezon server IP.
2. New vhost, docroot = uploaded `site/` contents. Sample nginx (adjust the PHP-FPM socket):

```nginx
server {
    server_name vuathau.com.vn www.vuathau.com.vn;
    root /var/www/vuathau;
    index index.html;
    location / { try_files $uri $uri/ =404; }
    location ~ /assets/ { expires 30d; add_header Cache-Control "public"; }
    location = /api/_lib.php { deny all; }
    location ~ ^/api/[a-z]+\.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

   On cPanel/DirectAdmin: add domain, upload into its `public_html`. Nothing else needed.
3. SSL: Let's Encrypt (`certbot --nginx -d vuathau.com.vn -d www.vuathau.com.vn`, or AutoSSL in the panel).
4. **Mail deliverability:** add an SPF record for `vuathau.com.vn` that authorises the server's IP (and DKIM if the panel offers it), or form emails will land in spam. If the server can't send mail directly, `mail()` must be pointed at an SMTP relay (msmtp / panel SMTP settings) — ask chị Lan which one Dezon uses.

## Smoke test after go-live

- `/` and `/nha-thau/` load, partner cards show real dezon.vn photos.
- `curl https://vuathau.com.vn/api/partners.php` returns 8 items.
- Submit each form once → both inboxes receive it (check spam).

## Re-importing a new design export

```bash
python3 -I tools/unbundle.py <exported.html> <out_dir> "<title>" "<description>"
```

This only unpacks. The pages in `site/` carry hand edits on top (API wiring, `<img>` instead of `image-slot`, cross-page links, absolute `/…/assets/` paths), so after a re-import those edits must be re-applied.

## Known gaps (content, not code)

- Placeholder links go to `#top`: Chính sách bảo mật, Điều khoản sử dụng, Liên hệ, Dự án, Ước tính chi phí ("Hoặc ước tính chi phí sơ bộ →" in the hero has no target section).
- Homepage FAQ still has a design note: "…Nội dung này chờ chủ sở hữu phê duyệt."
- /nha-thau form footnote links to "/doi-tac/dang-ky" which doesn't exist.
- No favicon / OG image.
