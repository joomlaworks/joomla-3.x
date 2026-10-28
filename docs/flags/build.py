"""Builds media/mod_languages/images from country-flags (PNG renders), flag-icons (regional flags it lacks), Wikimedia
Commons (public domain flags neither has), a hand-drawn Brittany flag and three script names, at twice the old GIFs' size
(36x24, each flag fitted with its own proportions and centred on transparency). Writes PNG and lossless WebP and reports
both totals."""
import os
import subprocess
import sys

from PIL import Image

# Usage: build.py <svg-country-flags package> <flag-icons package> <folder of the Commons SVGs> <output folder>
# (npm svg-country-flags 1.2.10 and flag-icons 7.6.0; Commons: the files named in MAP). Needs Inkscape, optipng, Pillow.
S = os.path.dirname(os.path.abspath(__file__))
CF = sys.argv[1] + '/png1000px/%s.png'
FI = sys.argv[2] + '/flags/4x3/%s.svg'
WC = sys.argv[3] + '/%s'
OUT = sys.argv[4]
BOX = (36, 24)

# The flag each old GIF showed (checked against the GIFs)
MAP = {
    'af': 'cf:za', 'af_za': 'cf:za', 'al': 'cf:al', 'ar': 'fi:arab', 'ar_aa': 'fi:arab', 'at': 'cf:at', 'az': 'cf:az',
    'az_az': 'cf:az', 'be': 'cf:by', 'be_by': 'cf:by', 'belg': 'cf:be', 'bg': 'cf:bg', 'bg_bg': 'cf:bg', 'bn': 'cf:bd',
    'bn_bd': 'cf:bd', 'br': 'own:brittany', 'br_fr': 'own:brittany', 'bs': 'cf:ba', 'bs_ba': 'cf:ba', 'ca': 'fi:es-ct',
    'ca_es': 'fi:es-ct', 'cbk_iq': 'wc:Flag of Kurdistan.svg', 'ch': 'cf:ch', 'cs': 'cf:cz', 'cs_cz': 'cf:cz',
    'cy': 'cf:gb-wls', 'cy_gb': 'cf:gb-wls', 'cz': 'cf:cz', 'cz_cz': 'cf:cz', 'da': 'cf:dk', 'da_dk': 'cf:dk',
    'de': 'cf:de', 'de_at': 'cf:at', 'de_ch': 'cf:ch', 'de_de': 'cf:de', 'de_li': 'cf:li', 'de_lu': 'cf:lu',
    'dk': 'cf:dk', 'dz_bt': 'cf:bt', 'el': 'cf:gr', 'el_gr': 'cf:gr', 'en': 'cf:gb', 'en_au': 'cf:au', 'en_ca': 'cf:ca',
    'en_gb': 'cf:gb', 'en_nz': 'cf:nz', 'en_us': 'cf:us', 'eo': 'wc:Flag of Esperanto.svg',
    'eo_xx': 'wc:Flag of Esperanto.svg', 'es': 'cf:es', 'es_co': 'cf:co', 'es_es': 'cf:es', 'et': 'cf:ee',
    'et_ee': 'cf:ee', 'eu_es': 'fi:es-pv', 'fa': 'cf:ir', 'fa_ir': 'cf:ir', 'fi': 'cf:fi', 'fi_fi': 'cf:fi',
    'fr': 'cf:fr', 'fr_ca': 'wc:Flag of Quebec.svg', 'fr_fr': 'cf:fr', 'ga_ie': 'cf:ie', 'gd': 'cf:gb-sct',
    'gd_gb': 'cf:gb-sct', 'gl': 'fi:es-ga', 'gl_es': 'fi:es-ga', 'he': 'cf:il', 'he_il': 'cf:il', 'hi': 'cf:in',
    'hi_in': 'cf:in', 'hk': 'cf:hk', 'hk_hk': 'cf:hk', 'hr': 'cf:hr', 'hr_hr': 'cf:hr', 'hu': 'cf:hu', 'hu_hu': 'cf:hu',
    'hy': 'cf:am', 'hy_am': 'cf:am', 'id': 'cf:id', 'id_id': 'cf:id', 'is': 'cf:is', 'is_is': 'cf:is', 'it': 'cf:it',
    'it_it': 'cf:it', 'ja': 'cf:jp', 'ja_jp': 'cf:jp', 'ka': 'cf:ge', 'ka_ge': 'cf:ge', 'kk_kz': 'cf:kz', 'km': 'cf:kh',
    'km_kh': 'cf:kh', 'ko': 'cf:kr', 'ko_kr': 'cf:kr', 'ku': 'wc:Flag of Kurdistan.svg', 'lo': 'cf:la', 'lo_la': 'cf:la',
    'lt': 'cf:lt', 'lt_lt': 'cf:lt', 'lv': 'cf:lv', 'lv_lv': 'cf:lv', 'mk': 'cf:mk', 'mk_mk': 'cf:mk', 'mn': 'cf:mn',
    'mn_mn': 'cf:mn', 'ms_my': 'cf:my', 'nb_no': 'cf:no', 'nl': 'cf:nl', 'nl_be': 'cf:be', 'nl_nl': 'cf:nl',
    'nn_no': 'cf:no', 'no': 'cf:no', 'pl': 'cf:pl', 'pl_pl': 'cf:pl', 'prs_af': 'cf:af', 'ps': 'cf:af', 'ps_af': 'cf:af',
    'pt': 'cf:pt', 'pt_br': 'cf:br', 'pt_pt': 'cf:pt', 'ro': 'cf:ro', 'ro_ro': 'cf:ro', 'ru': 'cf:ru', 'ru_ru': 'cf:ru',
    'si': 'text:සිංහල:Noto Sans Sinhala', 'si_lk': 'text:සිංහල:Noto Sans Sinhala', 'sk': 'cf:sk', 'sk_sk': 'cf:sk',
    'sl': 'cf:si', 'sl_si': 'cf:si', 'sq_al': 'cf:al', 'sr': 'cf:rs', 'sr_rs': 'cf:rs', 'sr_yu': 'cf:rs', 'srp_me': 'cf:me',
    'sv': 'cf:se', 'sv_se': 'cf:se', 'sw': 'fi:eac', 'sw_ke': 'fi:eac', 'sy': 'wc:Flag of Assyria.svg',
    'sy_iq': 'wc:Flag of Assyria.svg', 'ta': 'text:தமிழ்:Noto Sans Tamil', 'ta_in': 'text:தமிழ்:Noto Sans Tamil',
    'th': 'cf:th', 'th_th': 'cf:th', 'tk_tm': 'cf:tm', 'tr': 'cf:tr', 'tr_tr': 'cf:tr', 'tw': 'cf:tw',
    'ug_cn': 'text:ئۇيغۇرچە:Noto Sans Arabic', 'uk': 'cf:ua', 'uk_ua': 'cf:ua', 'ur': 'cf:pk', 'ur_pk': 'cf:pk',
    'us': 'cf:us', 'uz': 'cf:uz', 'uz_uz': 'cf:uz', 'vi': 'cf:vn', 'vi_vn': 'cf:vn', 'zh': 'cf:cn', 'zh_cn': 'cf:cn',
    'zh_tw': 'cf:tw',
}

# Brittany (Gwenn-ha-du): nine stripes, black first, and a white canton with eleven ermine spots (drawn here: the design is
# in the public domain, the Commons drawing isn't)
ERMINE = ''.join(
    '<path transform="translate(%g %g)" d="M0 -2.2 L0.9 0.2 L2.2 1.6 L0.6 1.2 L0 2.4 L-0.6 1.2 L-2.2 1.6 L-0.9 0.2 Z"/>' % (x, y)
    for (x, y) in ((5, 3.2), (13, 3.2), (21, 3.2), (9, 7.2), (17, 7.2), (5, 11.2), (13, 11.2), (21, 11.2),
                   (9, 15.2), (17, 15.2), (13, 18.4)))
BRITTANY = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 54 36">'
            + ''.join('<rect y="%g" width="54" height="4" fill="%s"/>' % (i * 4, '#000' if i % 2 == 0 else '#fff') for i in range(9))
            + '<rect width="26" height="20" fill="#fff"/><g fill="#000">' + ERMINE + '</g></svg>')


def inkscape(svg_path, png_path, width=1000):
    subprocess.run(['inkscape', svg_path, '--export-type=png', '--export-filename=' + png_path, '-w', str(width)],
                   check=True, capture_output=True)


def source(spec, tmp):
    kind, _, rest = spec.partition(':')

    if kind == 'cf':
        return Image.open(CF % rest).convert('RGBA')

    png = '%s/%s.png' % (tmp, spec.replace(':', '_').replace(' ', '_').replace('/', '_'))

    if not os.path.exists(png):
        if kind == 'fi':
            inkscape(FI % rest, png)
        elif kind == 'wc':
            inkscape(WC % rest, png)
        elif kind == 'own':
            svg = tmp + '/brittany.svg'
            open(svg, 'w').write(BRITTANY)
            inkscape(svg, png)
        elif kind == 'text':
            text, font = rest.split(':')
            svg = '%s/text-%s.svg' % (tmp, font.replace(' ', '_'))
            open(svg, 'w', encoding='utf-8').write(
                '<svg xmlns="http://www.w3.org/2000/svg" width="180" height="120" viewBox="0 0 180 120">'
                '<text x="90" y="80" text-anchor="middle" font-family="%s" font-size="58" font-weight="600" fill="#333">%s</text>'
                '</svg>' % (font, text))
            subprocess.run(['inkscape', svg, '--export-type=png', '--export-filename=' + png, '-w', '1080',
                            '--export-area-drawing'], check=True, capture_output=True)

    return Image.open(png).convert('RGBA')


def fit(image):
    scale = min(BOX[0] / image.width, BOX[1] / image.height)
    size = (max(1, round(image.width * scale)), max(1, round(image.height * scale)))
    small = image.resize(size, Image.LANCZOS)
    canvas = Image.new('RGBA', BOX, (0, 0, 0, 0))
    canvas.paste(small, ((BOX[0] - size[0]) // 2, (BOX[1] - size[1]) // 2), small)

    return canvas


def main():
    tmp = OUT + '/tmp'
    os.makedirs(tmp, exist_ok=True)
    os.makedirs(OUT + '/png', exist_ok=True)
    os.makedirs(OUT + '/webp', exist_ok=True)
    totals = {'png': 0, 'webp': 0}

    for name, spec in sorted(MAP.items()):
        flag = fit(source(spec, tmp))
        png = '%s/png/%s.png' % (OUT, name)
        webp = '%s/webp/%s.webp' % (OUT, name)
        flag.save(png, optimize=True)
        subprocess.run(['optipng', '-quiet', '-o7', png], check=True)
        flag.save(webp, lossless=True, method=6, quality=100)
        totals['png'] += os.path.getsize(png)
        totals['webp'] += os.path.getsize(webp)

    print('%d flags: PNG %d bytes, WebP (lossless) %d bytes' % (len(MAP), totals['png'], totals['webp']))


main()
