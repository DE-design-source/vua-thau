"""Unpack a Claude Design standalone bundle into index.html + assets/ for static hosting.

usage: python3 -I tools/unbundle.py <bundle.html> <out_dir> <title> <description>
"""
import base64, gzip, json, os, re, sys
from html import escape as html_escape

EXT = {'image/png': 'png', 'image/jpeg': 'jpg', 'image/svg+xml': 'svg', 'image/webp': 'webp',
       'text/javascript': 'js', 'text/css': 'css', 'font/woff2': 'woff2', 'font/woff': 'woff'}

HEAD = '<title>%s</title><meta name="description" content="%s">'  # the design export has neither


def block(html, kind):
    m = re.search(r'<script type="__bundler/%s">\s*(.*?)\s*</script>' % kind, html, re.S)
    return json.loads(m.group(1)) if m else None


def unbundle(src, out, title, desc):
    html = open(src, encoding='utf-8').read()
    manifest, template = block(html, 'manifest'), block(html, 'template')
    ext_res = block(html, 'ext_resources') or []
    assert manifest and template, 'not a bundler page'
    assert not block(html, 'page_order'), 'nested pages not supported'

    os.makedirs(os.path.join(out, 'assets'), exist_ok=True)
    paths = {}
    for uuid, e in manifest.items():
        data = base64.b64decode(e['data'])
        if e.get('compressed'):
            data = gzip.decompress(data)
        rel = 'assets/%s.%s' % (uuid[:8], EXT.get(e['mime'], 'bin'))
        open(os.path.join(out, rel), 'wb').write(data)
        paths[uuid] = rel

    for uuid, rel in paths.items():
        template = template.replace(uuid, rel)
    template = re.sub(r'\s+(integrity|crossorigin)="[^"]*"', '', template)
    res = {r['id']: paths[r['uuid']] for r in ext_res if r['uuid'] in paths}
    script = '<script>window.__resources = %s;</script>' % json.dumps(res).replace('</', '<\\/')
    template = re.sub(r'(<head[^>]*>)', lambda m: m.group(1) + script + HEAD % (html_escape(title), html_escape(desc)), template, count=1)
    template = template.replace('<html>', '<html lang="vi">', 1)
    template = re.sub(r'<div id="__claude_design_branding">.*?</div>(?=</body>)', '', template, flags=re.S)
    open(os.path.join(out, 'index.html'), 'w', encoding='utf-8').write(template)
    return paths


if __name__ == '__main__':
    for u, p in unbundle(*sys.argv[1:5]).items():
        print(p, os.path.getsize(os.path.join(sys.argv[2], p)))
