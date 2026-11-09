#!/usr/bin/env python3
"""Prüft die uploadfertigen ZIPs, mitgeliefertes Theme und Versionen ohne WordPress auszuführen."""
import hashlib
import json
from pathlib import Path
import re
import sys
from zipfile import ZipFile

folder, version = Path(sys.argv[1]), sys.argv[2]
assert re.fullmatch(r'\d+\.\d+\.\d+', version)
for kind, name, root in [('plugin', f'michatheme-v5-plugin-{version}.zip', 'michatheme-v5/'), ('theme', f'michatheme-v5-woocommerce-{version}.zip', 'michatheme-v5-woocommerce/')]:
    with ZipFile(folder / name) as archive:
        files = {n: archive.read(n) for n in archive.namelist() if not n.endswith('/')}
        assert all(n.startswith(root) and '..' not in n.split('/') and '\\' not in n and not any(p in {'.git', '.github', 'tools', 'tests', 'node_modules'} or p.startswith('.env') for p in n.split('/')) for n in files)
        theme = root + ('theme/michatheme-v5/' if kind == 'plugin' else '')
        assert re.search(r'^Version:\s*' + re.escape(version) + r'\s*$', files[theme + 'style.css'].decode(), re.M)
        assert theme + 'functions.php' in files
        if kind == 'plugin':
            assert re.search(r'^\s*\* Version:\s*' + re.escape(version) + r'\s*$', files[root + 'michatheme-v5.php'].decode(), re.M)
            manifest = json.loads(files[theme + 'build-integrity.json'])
            actual = {n[len(theme):]: hashlib.sha256(b).hexdigest() for n, b in files.items() if n.startswith(theme) and n != theme + 'build-integrity.json'}
            assert manifest['version'] == version and manifest['files'] == actual
    print(name, len(files), 'Dateien geprüft')
