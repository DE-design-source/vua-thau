# Deploy vuathau.com.vn

## Layout

```
site/
  index.html            → vuathau.com.vn/            Trang chủ (chủ nhà)
  nha-thau/index.html   → vuathau.com.vn/nha-thau/   Trang đối tác (menu "Dành cho nhà thầu")
  api/partners.php      partner cards on both pages: top 8 from dezon.vn/nha-thau/tiem-nang/, cached 1h
  api/lead.php          homepage form "Gửi nhu cầu xây nhà" → email
  api/register.php      /nha-thau form "Đăng ký đối tác"     → email
  api/_lib.php          shared: recipients (TO), sender, throttle, mail()
deploy/                 Docker (PHP 8.3 + Apache + msmtp) for the shared server
tools/unbundle.py       unpack a Claude Design standalone export
```

Recipients: `hungkts@decox.vn, support@dezon.vn` (`TO` in `site/api/_lib.php`).

## Where it runs

Shared Dezon server **222.255.184.134** (CloudFly, Ubuntu 24.04, SSH `root`, key `id_ed25519`), next to **QS Pro** (dezon.tech).

```
internet :80/:443 → qspro-web-1 (Caddy, /opt/qspro/infra/deploy/Caddyfile)
                       ├─ dezon.tech, www.dezon.tech   → QS Pro
                       └─ vuathau.com.vn, www.…        → vuathau-web:80  (this repo, /opt/vuathau)
```

- QS Pro's Caddy owns 80/443 and HTTPS certificates. vuathau only adds a site block at the end of that Caddyfile:

  ```caddy
  vuathau.com.vn {
  	encode zstd gzip
  	reverse_proxy vuathau-web:80
  }
  www.vuathau.com.vn {
  	redir https://vuathau.com.vn{uri} permanent
  }
  ```

  **QS Pro deploys must keep this block** (tell whoever owns QS Pro's source; the server repo has no remote).
- vuathau-web joins the `qspro_default` network (external) — nothing in QS Pro's compose file changes.

## Update the site

```bash
ssh root@222.255.184.134 'git -C /opt/vuathau pull'
```

`site/` is mounted read-only, so no restart. After changing `deploy/Dockerfile` or `deploy/apache.conf`:
`cd /opt/vuathau/deploy && docker compose up -d --build`.

## Mail

PHP `mail()` → msmtp → Gmail SMTP (`deploy/msmtprc`, not in git). Needs a Google Workspace **App Password** for `support@dezon.vn`
(or another Workspace account) — see `deploy/msmtprc.example`. Gmail sends From that account; the visitor's email is in Reply-To.
Until it's set, forms show "Chưa gửi được, vui lòng gọi 0931 168 749…".
After editing `msmtprc` run `docker restart vuathau-web`: it's a single-file bind mount, and editors/`sed -i` replace the file, so the running container keeps reading the old one.

## DNS (PA Việt Nam → "Cấu hình bản ghi tên miền")

| Type | Host | Value |
|---|---|---|
| A | `@` | 222.255.184.134 |
| A | `www` | 222.255.184.134 |

Keep PA's name servers. Caddy issues HTTPS automatically once DNS resolves.

## Known gaps (content, not code)

- Placeholder links go to `#top`: Chính sách bảo mật, Điều khoản sử dụng, Liên hệ, Dự án, Ước tính chi phí ("Hoặc ước tính chi phí sơ bộ →" in the hero has no target section).
- Homepage FAQ still has a design note: "…Nội dung này chờ chủ sở hữu phê duyệt."
- /nha-thau form footnote links to "/doi-tac/dang-ky" which doesn't exist.
- No favicon / OG image.

## Re-importing a new design export

`python3 -I tools/unbundle.py <exported.html> <out_dir> "<title>" "<description>"` only unpacks. The pages in `site/` carry
hand edits (API wiring, `<img>` instead of `image-slot`, cross-page links, absolute `/…/assets/` paths) that must be re-applied.
