# GYOSEI DENTAL — GENSEN Child Theme

Prestige × Modern brushup for [gyosei-dental.com](https://gyosei-dental.com/).
Child theme of TCD GENSEN (`gensen_tcd050`). Sister of `gensen-gyosei` (GYOSEI MEDICAL) — identical design system (navy × antique gold × ivory, Shippori Mincho B1 + Cormorant Garamond) for brand consistency across the暁星OB portal network.

## Palette

| Token | Hex | Use |
| --- | --- | --- |
| `--gm-navy` | `#0A1F3D` | Primary, headings, footer |
| `--gm-gold` | `#B8935A` | Accent, hairlines, hover |
| `--gm-forest` | `#1F4D3A` | Dental/medical accent |
| `--gm-ivory` | `#FAF7F1` | Page background |
| `--gm-ink` | `#141720` | Body text |

(CSS variables keep the `--gm-` prefix so the brushup stylesheet is shared 1:1 with the sister theme.)

## Structure

```
gyosei-dental/
  style.css            child theme header
  functions.php        enqueue + SEO + HTTPS/image rewrite + banner restructure
  assets/
    css/brushup.css    full visual override (shared design system)
    js/brushup.js      section wrap, banner relabel, archive enrichment
```

## Deploy

Path on XServer: `~/gyosei-dental.com/public_html/wp-content/themes/gensen-dental/`

```bash
ssh xserver-xagm
cd ~/gyosei-dental.com/public_html/wp-content/themes/
git clone https://github.com/nishida-coder/gyosei-dental.git gensen-dental
# Activate via WP admin → 外観 → テーマ
```

Update after a push:

```bash
ssh xserver-xagm "cd ~/gyosei-dental.com/public_html/wp-content/themes/gensen-dental && git pull"
```
