#!/usr/bin/env python3
"""
Count Bootstrap 3 / AdminLTE 2 / Font Awesome 4 markup and JS plugin usage that the
Bootstrap 5 + AdminLTE 4 migration has to touch.

usage: python3 -I tests/smoke/ui/bs3-inventory.py [--files] <dir> [<dir> ...]

Prints, per pattern, how many occurrences and files were found in each directory.
With --files it also lists the files for every pattern. Run it on the fork (src,
resources/views, resources/assets/laravel-admin) and on consumers (app/Admin,
resources/views/admin) to size and then track the migration.
"""
import os
import re
import sys
from collections import defaultdict

# (group, label, regex, what to do in BS5 / AdminLTE 4)
PATTERNS = [
    # --- Bootstrap 3 -> 5: grid & utilities
    ('bs-grid', 'col-xs-*', r'\bcol-xs-\d+', 'col-*'),
    ('bs-grid', 'col-*-offset-*', r'\bcol-(xs|sm|md|lg)-offset-\d+', 'offset-*'),
    ('bs-grid', 'col-*-push/pull-*', r'\bcol-(xs|sm|md|lg)-(push|pull)-\d+', 'order-*'),
    ('bs-util', 'pull-left/right', r'\bpull-(left|right)\b', 'float-start/end'),
    ('bs-util', 'text-left/right', r'\btext-(left|right)\b', 'text-start/end'),
    ('bs-util', 'hidden-*/visible-*', r'\b(hidden|visible)-(xs|sm|md|lg)\b', 'd-none d-*-block'),
    ('bs-util', 'center-block', r'\bcenter-block\b', 'mx-auto d-block'),
    ('bs-util', 'sr-only', r'\bsr-only\b', 'visually-hidden'),
    ('bs-util', 'img-responsive/img-circle', r'\bimg-(responsive|circle)\b', 'img-fluid / rounded-circle'),
    # --- components
    ('bs-btn', 'btn-default', r'\bbtn-default\b', 'btn-secondary / btn-outline-secondary'),
    ('bs-btn', 'btn-xs', r'\bbtn-xs\b', 'btn-sm (no xs)'),
    ('bs-form', 'form-group', r'\bform-group\b', 'mb-3 (form-group removed)'),
    ('bs-form', 'control-label', r'\bcontrol-label\b', 'form-label / col-form-label'),
    ('bs-form', 'help-block', r'\bhelp-block\b', 'form-text'),
    ('bs-form', 'has-error/success/warning', r'\bhas-(error|success|warning|feedback)\b', 'is-invalid + invalid-feedback'),
    ('bs-form', 'input-group-addon', r'\binput-group-addon\b', 'input-group-text'),
    ('bs-form', 'input-group-btn', r'\binput-group-btn\b', 'buttons directly in .input-group'),
    ('bs-form', 'input-sm/lg', r'\binput-(sm|lg)\b', 'form-control-sm/lg'),
    ('bs-form', 'form-inline/horizontal', r'\bform-(inline|horizontal)\b', 'grid/flex utilities'),
    ('bs-form', 'checkbox/radio(-inline) wrappers', r'class="[^"]*\b(checkbox|radio)(-inline)?\b', 'form-check(-inline)'),
    ('bs-comp', 'label label-*', r'\blabel-(default|primary|success|info|warning|danger)\b', 'badge text-bg-*'),
    ('bs-comp', 'panel*', r'\bpanel(-(default|heading|body|footer|title|group|collapse|primary|success|info|warning|danger))?\b', 'card*'),
    ('bs-comp', 'well', r'\bwell(-sm|-lg)?\b', 'card / bg-light p-3'),
    ('bs-comp', 'thumbnail', r'\bthumbnail\b', 'card / img-thumbnail'),
    ('bs-comp', 'caret', r'\bcaret\b', 'dropdown-toggle::after'),
    ('bs-comp', 'dropdown-menu-right/left', r'\bdropdown-menu-(right|left)\b', 'dropdown-menu-end/start'),
    ('bs-comp', 'li.divider (dropdown)', r'class="divider"', 'hr.dropdown-divider'),
    ('bs-comp', 'table-condensed', r'\btable-condensed\b', 'table-sm'),
    ('bs-comp', 'glyphicon', r'\bglyphicon\b', 'icon font'),
    ('bs-comp', 'close button', r'class="close"', 'btn-close'),
    ('bs-comp', '.in / fade in (state)', r'\b(fade|collapse|tab-pane[^"]*) in\b|Class\(\s*[\'"]in[\'"]', '.show'),
    ('bs-comp', 'navbar-*-right/left', r'\bnavbar-(right|left)\b', 'ms-auto / me-auto'),
    # --- data API & JS
    ('bs-js', 'data-toggle/target/dismiss/...', r'\bdata-(toggle|target|dismiss|parent|ride|slide|spy|placement|content|backdrop|keyboard)=', 'data-bs-*'),
    ('bs-js', 'jQuery BS plugins ($.modal/.tooltip/...)', r'\.(modal|tooltip|popover|tab|collapse|dropdown|button|carousel|affix|scrollspy)\(\s*[\'"{]?', 'bootstrap.* JS API (jQuery bridge still works)'),
    ('bs-js', 'BS3 events (show.bs.*, shown.bs.*)', r'\b(show|shown|hide|hidden)\.bs\.(modal|tab|collapse|dropdown|tooltip|popover)\b', 'same names, check targets'),
    # --- AdminLTE 2 -> 4
    ('lte', 'box / box-*', r'\bbox(-(header|body|footer|title|tools|primary|info|success|warning|danger|default|solid|widget|comment|comments))?\b', 'card / card-*'),
    ('lte', 'btn-box-tool', r'\bbtn-box-tool\b', 'btn btn-tool'),
    ('lte', 'btn-flat', r'\bbtn-flat\b', 'remove'),
    ('lte', 'layout shell (content-wrapper, main-header, main-sidebar...)', r'\b(content-wrapper|content-header|main-header|main-sidebar|main-footer|control-sidebar|sidebar-menu|treeview(-menu)?|sidebar-mini|sidebar-collapse|logo-(mini|lg)|user-panel|navbar-custom-menu|sidebar-toggle)\b', 'app-wrapper / app-header / app-sidebar / app-main'),
    ('lte', 'info-box / small-box', r'\b(info-box|small-box)(-\w+)?\b', 'info-box / small-box (kept, markup changed)'),
    ('lte', 'callout', r'\bcallout(-\w+)?\b', 'callout (kept)'),
    ('lte', 'skin-*', r'\bskin-[a-z-]+\b', 'data-bs-theme / navbar & sidebar color classes'),
    ('lte', 'bg-<color> (aqua, red, ...)', r'\bbg-(aqua|red|yellow|green|blue|light-blue|navy|teal|olive|lime|orange|fuchsia|purple|maroon|black|gray)(-active)?\b', 'text-bg-* / bg-*'),
    ('lte', 'AdminLTE JS (pushMenu, boxWidget, tree...)', r'\b(pushMenu|boxWidget|boxRefresh|layout\.fix|AdminLTE|\$\.AdminLTE)\b', 'AdminLTE 4 JS (no jQuery)'),
    # --- Font Awesome 4
    ('fa', 'fa fa-* icons', r'\bfa fa-[a-z0-9-]+', 'FA 6: fa-solid fa-* (+ renames, v4-shims)'),
    ('fa', "icon names stored as 'fa-*' strings", r'[\'"]fa-[a-z0-9-]+[\'"]', 'map / v4-shims'),
    # --- BS3-only plugins
    ('plugin', 'bootstrap-switch', r'bootstrapSwitch|bootstrap-switch', 'form-switch (native)'),
    ('plugin', 'x-editable (bootstrap3-editable)', r'\.editable\(|bootstrap3-editable|editable-', 'BS5 fork or own popover'),
    ('plugin', 'eonasdan datetimepicker', r'datetimepicker|eonasdan', 'flatpickr / tempus-dominus 6'),
    ('plugin', 'bootstrap-duallistbox', r'bootstrapDualListbox|duallistbox', 'duallistbox fork BS5 / select2 multi'),
    ('plugin', 'bootstrap-fileinput', r'fileinput', 'bootstrap-fileinput 5 (BS5 theme)'),
    ('plugin', 'fontawesome-iconpicker', r'iconpicker', 'iconpicker for FA6 / own'),
    ('plugin', 'bootstrap-colorpicker', r'colorpicker', 'native <input type=color> / Coloris'),
    ('plugin', 'iCheck', r'iCheck|\.icheck|icheckbox', 'native form-check'),
    ('plugin', 'select2', r'select2', 'select2 + select2-bootstrap-5-theme / tom-select'),
    ('plugin', 'ion.rangeSlider', r'ionRangeSlider|ionslider', 'kept (no BS dependency)'),
    ('plugin', 'bootstrap-slider', r'bootstrap-slider|bootstrapSlider', 'remove / native range'),
    ('plugin', 'slimScroll', r'slimScroll|slimscroll', 'native overflow / OverlayScrollbars'),
    ('plugin', 'jquery-pjax', r'pjax', 'kept (jQuery)'),
    ('plugin', 'nestable', r'nestable', 'kept (jQuery) / SortableJS'),
    ('plugin', 'toastr', r'toastr', 'kept'),
    ('plugin', 'sweetalert2', r'swal|sweetalert', 'kept (upgrade)'),
    ('plugin', 'input-mask', r'inputmask', 'kept (no BS dependency)'),
]

EXTENSIONS = ('.php', '.js', '.css', '.html')
SKIP_DIRS = {'node_modules', 'vendor', '.git'}
SKIP_FILES = re.compile(r'\.min\.(js|css)$')


def scan(root):
    results = defaultdict(lambda: [0, set()])
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for name in filenames:
            if not name.endswith(EXTENSIONS) or SKIP_FILES.search(name):
                continue
            path = os.path.join(dirpath, name)
            try:
                text = open(path, encoding='utf-8', errors='ignore').read()
            except OSError:
                continue
            for group, label, regex, _ in PATTERNS:
                hits = len(re.findall(regex, text))
                if hits:
                    results[label][0] += hits
                    results[label][1].add(os.path.relpath(path, root))
    return results


def main(argv):
    show_files = '--files' in argv
    roots = [a for a in argv if a != '--files']
    if not roots:
        print(__doc__)
        return 2
    scans = {root: scan(root) for root in roots}
    header = ['group', 'pattern'] + [f'{os.path.basename(os.path.normpath(r)) or r} (hits/files)' for r in roots] + ['BS5 / AdminLTE 4']
    print('| ' + ' | '.join(header) + ' |')
    print('|' + '---|' * len(header))
    for group, label, _, target in PATTERNS:
        cells = []
        for root in roots:
            hits, files = scans[root].get(label, [0, set()])
            cells.append(f'{hits}/{len(files)}' if hits else '-')
        if all(c == '-' for c in cells):
            continue
        print(f'| {group} | {label} | ' + ' | '.join(cells) + f' | {target} |')
    if show_files:
        for root in roots:
            print(f'\n## {root}')
            for group, label, _, _ in PATTERNS:
                hits, files = scans[root].get(label, [0, set()])
                if files:
                    print(f'\n### {label} ({hits})')
                    for f in sorted(files):
                        print(f'- {f}')
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
