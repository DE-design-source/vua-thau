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

Shared Dezon server **222.255.184.134** (CloudFly, Ubuntu 24.04, SSH `root`, key `id_ed25519`), next to **QS Pro** (dezon.tech),
fully independent of it: QS Pro's Caddy keeps ports 80/443, vuathau has its own port behind Cloudflare.

```
visitor → Cloudflare (DNS + HTTPS, proxied) ──Origin Rule: port 8443──→ 222.255.184.134:8443 → vuathau-web (Apache, /opt/vuathau)
visitor → dezon.tech → 222.255.184.134:443 → QS Pro Caddy (untouched)
```

Cloudflare settings for vuathau.com.vn:
- DNS: `A @` and `A www` → 222.255.184.134, **Proxied** (orange). Mail records (`mail`, `mx`, `dkim._domainkey` CNAMEs, MX, SPF/DMARC TXT) **DNS only** (grey).
- SSL/TLS mode **Full** (origin has a self-signed cert). Always Use HTTPS on.
- Rules → Origin Rules: hostname is `vuathau.com.vn` or `www.vuathau.com.vn` → destination port **8443**.
- Apache on :8443 only answers Cloudflare IPs (`deploy/apache.conf`), so the port can't be used to bypass Cloudflare.
- Registrar PA Việt Nam: name servers = the two Cloudflare ones; DNSSEC at PA must be off (or re-enabled via Cloudflare's DS record).

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

## Known gaps (content, not code)

- Placeholder links go to `#top`: Chính sách bảo mật, Điều khoản sử dụng, Liên hệ, Dự án, Ước tính chi phí ("Hoặc ước tính chi phí sơ bộ →" in the hero has no target section).
- Homepage FAQ still has a design note: "…Nội dung này chờ chủ sở hữu phê duyệt."
- /nha-thau form footnote links to "/doi-tac/dang-ky" which doesn't exist.
- No favicon / OG image.

## Re-importing a new design export

`python3 -I tools/unbundle.py <exported.html> <out_dir> "<title>" "<description>"` only unpacks. The pages in `site/` carry
hand edits (API wiring, `<img>` instead of `image-slot`, cross-page links, absolute `/…/assets/` paths) that must be re-applied.
