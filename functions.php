<?php
/**
 * GYOSEI DENTAL — GENSEN Child Theme
 * Prestige × Modern brushup (sister of gensen-gyosei / GYOSEI MEDICAL)
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GDENTAL_CHILD_VERSION', '1.0.0');

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'gensen-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'gdental-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['gensen-parent-style'],
        GDENTAL_CHILD_VERSION
    );

    wp_enqueue_style(
        'gdental-google-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Noto+Sans+JP:wght@300;400;500;700&family=Shippori+Mincho+B1:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'gdental-brushup',
        get_stylesheet_directory_uri() . '/assets/css/brushup.css',
        ['gdental-child-style'],
        GDENTAL_CHILD_VERSION
    );

    wp_enqueue_script(
        'gdental-brushup-js',
        get_stylesheet_directory_uri() . '/assets/js/brushup.js',
        [],
        GDENTAL_CHILD_VERSION,
        true
    );
}, 20);

/* =========================================================================
 * SEO / GEO enhancements
 * ========================================================================= */

define('GYOSEI_SITE_NAME', 'GYOSEI DENTAL');
define('GYOSEI_SITE_TAGLINE', '暁星卒業生OB歯科医師の歯科医院・クリニック開業情報サイト');
define('GYOSEI_SITE_DESC', '暁星学園を卒業され歯科医院およびクリニックを開業されているOB歯科医師の情報ポータル。診療科目、エリア、卒業年代から信頼できる歯科医療機関を探せる暁星OB歯科医師ネットワーク。');
define('GYOSEI_OGP_IMAGE', 'https://gyosei-dental.com/wp-content/uploads/2024/06/logo.png');
define('GYOSEI_CONTACT_EMAIL', 'info@gyosei-dental.com');

/**
 * Strip empty meta description tags output by the parent theme,
 * then our own richer tags run later via wp_head hook.
 */
add_action('wp_head', function () { ob_start(); }, 0);
add_action('wp_head', function () {
    $head = ob_get_clean();
    if (is_string($head) && $head !== '') {
        $head = preg_replace(
            '/<meta\s+name=["\']description["\']\s+content=["\']\s*["\']\s*\/?>\s*/i',
            '',
            $head
        );
        echo $head;
    }
}, PHP_INT_MAX);

/**
 * Build title/description/image/url context for the current page.
 */
function gyosei_seo_context() {
    $ctx = [
        'title'       => GYOSEI_SITE_NAME . ' | ' . GYOSEI_SITE_TAGLINE,
        'description' => GYOSEI_SITE_DESC,
        'image'       => GYOSEI_OGP_IMAGE,
        'url'         => home_url('/'),
        'type'        => 'website',
    ];

    if (is_front_page() || is_home()) {
        // defaults above
    } elseif (is_singular('post')) {
        $clinic_title = get_the_title();
        $cats = get_the_category();
        $specialty = !empty($cats) ? $cats[0]->name : null;
        $area = null;
        $grad = null;
        // Custom taxonomies for area + graduation year (TCD uses separate category taxonomies)
        $tax_area = get_the_terms(get_the_ID(), 'category2');
        if (!is_wp_error($tax_area) && !empty($tax_area)) { $area = $tax_area[0]->name; }
        $tax_grad = get_the_terms(get_the_ID(), 'category3');
        if (!is_wp_error($tax_grad) && !empty($tax_grad)) { $grad = $tax_grad[0]->name; }

        $desc_parts = ['暁星学園OB歯科医師が開業する「' . $clinic_title . '」の情報。'];
        if ($specialty) $desc_parts[] = '診療科目：' . $specialty . '。';
        if ($area) $desc_parts[] = 'エリア：' . $area . '。';
        if ($grad) $desc_parts[] = '院長暁星卒業年代：' . $grad . '。';
        $desc_parts[] = 'GYOSEI DENTALは暁星卒業生OB歯科医師の歯科医院・クリニックを集約する情報サイトです。';

        $ctx['title']       = $clinic_title . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = mb_substr(implode('', $desc_parts), 0, 160);
        $thumb = get_the_post_thumbnail_url(null, 'full');
        if ($thumb) $ctx['image'] = $thumb;
        $ctx['url']  = get_permalink();
        $ctx['type'] = 'article';
    } elseif (is_page()) {
        $ctx['title']       = get_the_title() . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = wp_strip_all_tags(get_the_excerpt()) ?: GYOSEI_SITE_DESC;
        $ctx['description'] = mb_substr($ctx['description'], 0, 160);
        $ctx['url']         = get_permalink();
        $ctx['type']        = 'article';
    } elseif (is_category() || is_tax() || is_archive()) {
        $obj = get_queried_object();
        $name = is_object($obj) && !empty($obj->name) ? $obj->name : '一覧';
        $ctx['title']       = $name . ' | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = $name . 'に該当する暁星OB歯科医師の歯科医院・クリニック一覧。診療科目、エリア、卒業年代から検索できる暁星OB歯科医師ネットワーク。';
        $ctx['url']         = is_object($obj) ? get_term_link($obj) : home_url('/');
    } elseif (is_search()) {
        $q = get_search_query();
        $ctx['title']       = '「' . $q . '」の検索結果 | ' . GYOSEI_SITE_NAME;
        $ctx['description'] = '「' . $q . '」に該当する暁星OB歯科医師の歯科医院・クリニック検索結果。';

    }
    return $ctx;
}

/**
 * Inject OGP, Twitter Card, and a meta description.
 * Suppressed when Rank Math SEO is active to avoid duplicate tags.
 */
add_action('wp_head', function () {
    if (defined('RANK_MATH_VERSION')) return;
    $ctx = gyosei_seo_context();
    $title = esc_attr($ctx['title']);
    $desc  = esc_attr($ctx['description']);
    $image = esc_url($ctx['image']);
    $url   = esc_url($ctx['url']);
    $type  = esc_attr($ctx['type']);

    echo "\n<!-- GYOSEI DENTAL SEO -->\n";
    echo '<meta name="description" content="' . $desc . '">' . "\n";
    echo '<meta property="og:type" content="' . $type . '">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $desc . '">' . "\n";
    echo '<meta property="og:url" content="' . $url . '">' . "\n";
    echo '<meta property="og:image" content="' . $image . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(GYOSEI_SITE_NAME) . '">' . "\n";
    echo '<meta property="og:locale" content="ja_JP">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $desc . '">' . "\n";
    echo '<meta name="twitter:image" content="' . $image . '">' . "\n";
}, 2);

/**
 * Override the document title for legibility across AI/search engines.
 * Only when Rank Math is not handling titles.
 */
add_filter('pre_get_document_title', function ($title) {
    if (defined('RANK_MATH_VERSION')) return $title;
    $ctx = gyosei_seo_context();
    return $ctx['title'] ?: $title;
}, 20);

/**
 * Inject JSON-LD structured data for GEO (LLM/Generative Engine) discovery.
 *
 * Rank Math already outputs Organization + WebSite + BreadcrumbList, so when
 * it is active we only add our unique Dentist schema (Rank Math Free
 * does not emit Dentist). When Rank Math is absent, emit the full set.
 */
add_action('wp_head', function () {
    $json_flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $rankmath_active = defined('RANK_MATH_VERSION');

    if ($rankmath_active) {
        // Rank Math handles Organization / WebSite / BreadcrumbList — only add Dentist here
        if (is_singular('post')) {
            $cats = get_the_category();
            $specialty = !empty($cats) ? $cats[0]->name : null;
            $area = null;
            $tax_area = get_the_terms(get_the_ID(), 'category2');
            if (!is_wp_error($tax_area) && !empty($tax_area)) { $area = $tax_area[0]->name; }
            $thumb = get_the_post_thumbnail_url(null, 'full');

            $clinic = [
                '@context'     => 'https://schema.org',
                '@type'        => 'Dentist',
                '@id'          => get_permalink() . '#clinic',
                'name'         => get_the_title(),
                'url'          => get_permalink(),
                'description'  => '暁星学園OB歯科医師が開業する' . ($specialty ?: '歯科医療機関') . '。GYOSEI DENTAL掲載。',
                'parentOrganization' => [
                    '@type' => 'Organization',
                    'name'  => GYOSEI_SITE_NAME,
                    'url'   => home_url('/'),
                ],
            ];
            if ($thumb) $clinic['image'] = $thumb;
            if ($specialty) $clinic['medicalSpecialty'] = $specialty;
            if ($area) {
                $clinic['areaServed'] = [
                    '@type' => 'AdministrativeArea',
                    'name'  => $area,
                ];
            }
            echo "\n<!-- GYOSEI DENTAL Dentist JSON-LD -->\n";
            echo '<script type="application/ld+json">' . wp_json_encode($clinic, $json_flags) . '</script>' . "\n";
        }
        return;
    }

    // Organization (site-wide)
    $organization = [
        '@context'     => 'https://schema.org',
        '@type'        => 'Organization',
        '@id'          => home_url('/#organization'),
        'name'         => GYOSEI_SITE_NAME,
        'alternateName'=> '暁星OB歯科医師ネットワーク',
        'url'          => home_url('/'),
        'logo'         => [
            '@type'  => 'ImageObject',
            'url'    => GYOSEI_OGP_IMAGE,
            'width'  => 800,
            'height' => 200,
        ],
        'description'  => GYOSEI_SITE_DESC,
        'email'        => GYOSEI_CONTACT_EMAIL,
        'sameAs'       => [
            'https://gyosei-medical.com/',
        ],
    ];

    // WebSite + SearchAction
    $website = [
        '@context'         => 'https://schema.org',
        '@type'            => 'WebSite',
        '@id'              => home_url('/#website'),
        'name'             => GYOSEI_SITE_NAME,
        'alternateName'    => '暁星OB歯科医師の歯科医院・クリニック情報サイト',
        'url'              => home_url('/'),
        'description'      => GYOSEI_SITE_DESC,
        'inLanguage'       => 'ja',
        'publisher'        => ['@id' => home_url('/#organization')],
        'potentialAction'  => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url('/clinic/?search_cat1={search_term_string}'),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    echo "\n<!-- GYOSEI DENTAL JSON-LD -->\n";
    echo '<script type="application/ld+json">' . wp_json_encode($organization, $json_flags) . '</script>' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode($website, $json_flags) . '</script>' . "\n";

    // Dentist per individual clinic page
    if (is_singular('post')) {
        $cats = get_the_category();
        $specialty = !empty($cats) ? $cats[0]->name : null;

        $area = null;
        $tax_area = get_the_terms(get_the_ID(), 'category2');
        if (!is_wp_error($tax_area) && !empty($tax_area)) { $area = $tax_area[0]->name; }

        $thumb = get_the_post_thumbnail_url(null, 'full');

        $clinic = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Dentist',
            '@id'              => get_permalink() . '#clinic',
            'name'             => get_the_title(),
            'url'              => get_permalink(),
            'description'      => '暁星学園OB歯科医師が開業する' . ($specialty ?: '歯科医療機関') . '。GYOSEI DENTAL掲載。',
            'parentOrganization' => [
                '@type' => 'Organization',
                'name'  => GYOSEI_SITE_NAME,
                'url'   => home_url('/'),
            ],
            'isPartOf'         => ['@id' => home_url('/#website')],
        ];
        if ($thumb) {
            $clinic['image'] = $thumb;
        }
        if ($specialty) {
            $clinic['medicalSpecialty'] = $specialty;
        }
        if ($area) {
            $clinic['areaServed'] = [
                '@type' => 'AdministrativeArea',
                'name'  => $area,
            ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($clinic, $json_flags) . '</script>' . "\n";
    }

    // BreadcrumbList everywhere except the front page
    if (!is_front_page()) {
        $items = [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'ホーム',
                'item'     => home_url('/'),
            ],
        ];
        $pos = 2;
        if (is_singular('post')) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => 'クリニック一覧',
                'item'     => home_url('/clinic/'),
            ];
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        } elseif (is_category() || is_tax()) {
            $obj = get_queried_object();
            if ($obj) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $obj->name,
                    'item'     => get_term_link($obj),
                ];
            }
        } elseif (is_page()) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        }

        $breadcrumb = [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb, $json_flags) . '</script>' . "\n";
    }
}, 3);

/**
 * Hint AI crawlers explicitly via robots meta (complement robots.txt).
 * Keeps standard max-image-preview + explicitly allows snippet generation.
 */
add_filter('wp_robots', function ($robots) {
    $robots['max-image-preview'] = 'large';
    $robots['max-snippet']       = -1;
    $robots['max-video-preview'] = -1;
    return $robots;
});

/**
 * Force HTTPS + fix broken cross-domain image references on gyosei-dental.com.
 *
 * TCD pagebuilder and some legacy post content reference images via
 * http://gyosei-dental.com/... (mixed-content blocked over HTTPS) and
 * a stray http://gyosei-medical.com/.../SP用-1.png that lives on the dental
 * server, not medical. We buffer the full page output and rewrite in one pass.
 */
add_action('template_redirect', 'gyosei_force_https_buffer', 1);
function gyosei_force_https_buffer() {
    if (is_admin()) return;
    ob_start('gyosei_force_https_rewrite');
}

function gyosei_force_https_rewrite($html) {
    if (!is_string($html) || $html === '') return $html;

    // 1a) Cross-domain broken reference: SP用-1.png lives on gyosei-dental, not gyosei-medical.
    //     Rewrite the host and upgrade scheme in one step.
    $html = str_replace(
        [
            'http://gyosei-medical.com/wp-content/uploads/2024/06/SP用-1.png',
            'https://gyosei-medical.com/wp-content/uploads/2024/06/SP用-1.png',
        ],
        'https://gyosei-dental.com/wp-content/uploads/2024/06/SP用-1.png',
        $html
    );

    // 1b) Force HTTPS on gyosei-dental.com asset URLs (mixed-content fix)
    $patterns = [
        'http://gyosei-dental.com/',
        'http://www.gyosei-dental.com/',
        'http://gyosei-medical.com/',
        'http://www.gyosei-medical.com/',
    ];
    $replace = [
        'https://gyosei-dental.com/',
        'https://www.gyosei-dental.com/',
        'https://gyosei-medical.com/',
        'https://www.gyosei-medical.com/',
    ];
    $html = str_replace($patterns, $replace, $html);

    // 1c) Strip `js-ellipsis` from DR-card doctor-name titles inside #post_list.
    //     The parent theme applies `$('.js-ellipsis').textOverflowEllipsis()` in footer.php,
    //     which truncates text after a <br>, hiding the "(XX年卒)" second line.
    $html = preg_replace(
        '#(<p class="title)\s+js-ellipsis(" style="margin-left: 10px;"><strong>)#u',
        '$1$2',
        $html
    );

    // 1d) Rewrite DR card doctor name into a clean <div> block OUTSIDE of `.title`.
    //     The parent theme applies `.title { display:-webkit-box; -webkit-line-clamp:2; height:3.4em; overflow:hidden }`
    //     to everything matching `#post_list .article .title`, which was clipping
    //     the grad year off. We emit a sibling <div class="gd-dr-meta"> that has no
    //     inherited clipping rules.
    //     Source variants:
    //       <strong>青柳 隆<br>(99年卒)</strong>
    //       <strong>福田 隆慧（03卒）</strong>
    //       <strong>豊村 康太<br>(00年卒)</strong>
    $html = preg_replace_callback(
        '#<p class="title" style="margin-left: 10px;"><strong>(.*?)</strong></p>#u',
        function ($m) {
            $raw = $m[1];
            $tmp = preg_replace('#<br\s*/?\s*>#i', '|', $raw);
            $name = $tmp;
            $grad = '';
            if (strpos($tmp, '|') !== false) {
                $parts = explode('|', $tmp, 2);
                $name = trim($parts[0]);
                $grad = trim($parts[1]);
            } elseif (preg_match('#^(.+?)[（(]([^）)]*?卒[^）)]*?)[）)]\s*$#u', $tmp, $mm)) {
                $name = trim($mm[1]);
                $grad = '(' . trim($mm[2]) . ')';
            }
            $name_esc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $grad_esc = htmlspecialchars($grad, ENT_QUOTES, 'UTF-8');
            $out  = '<div class="gd-dr-meta">';
            $out .= '<span class="gd-dr-name">' . $name_esc . '</span>';
            if ($grad !== '') {
                $out .= '<span class="gd-dr-grad">' . $grad_esc . '</span>';
            }
            $out .= '</div>';
            return $out;
        },
        $html
    );

    // 1e) Hero catchphrase: parent theme hardcodes color:#8fa5a2 (nearly invisible)
    //     and the inline span has a broken `opacity:0.2` declaration.
    //     Rebuild the <p class="catchphrase"> with a clean class so CSS can fully restyle.
    $html = preg_replace_callback(
        '#<p class="catchphrase rich_font"[^>]*>.*?</p>#us',
        function ($m) {
            // Was previously the same wording on both PC/SP captions.
            return '<p class="catchphrase rich_font gd-hero-catch">'
                . '<span class="gd-hero-catch-box">'
                . '<span class="gd-hero-catch-line1">暁星OB</span>'
                . '<span class="gd-hero-catch-x">×</span>'
                . '<span class="gd-hero-catch-line2">歯科開業医 情報ポータル</span>'
                . '</span></p>';
        },
        $html
    );

    // 1f) Rebuild footer SNS icons with inline SVG (parent theme uses the
    //     design_plus icon-font but it fails to render on this install).
    $sns_svg_fb = '<svg class="gd-sns-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8.2h2.76l.41-3.2H13.5V7.55c0-.92.26-1.55 1.58-1.55h1.69V3.14C16.48 3.1 15.48 3 14.33 3 11.9 3 10.24 4.48 10.24 7.2v2.4H7.5v3.2h2.74V21h3.26z"/></svg>';
    $sns_svg_ig = '<svg class="gd-sns-svg" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M12 2.2c3.2 0 3.58 0 4.85.07 1.17.05 1.8.25 2.23.42.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.37 1.06.42 2.23.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.05 1.17-.25 1.8-.42 2.23a3.7 3.7 0 0 1-.9 1.38 3.7 3.7 0 0 1-1.38.9c-.42.16-1.06.37-2.23.42-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-1.17-.05-1.8-.25-2.23-.42a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.37-1.06-.42-2.23C2.2 15.58 2.2 15.2 2.2 12s0-3.58.07-4.85c.05-1.17.25-1.8.42-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.37 2.23-.42C8.42 2.2 8.8 2.2 12 2.2zm0 1.98c-3.15 0-3.5 0-4.73.07-1.07.05-1.65.23-2.04.38-.5.2-.87.43-1.25.82-.39.38-.62.75-.82 1.25-.15.39-.33.97-.38 2.04-.06 1.24-.07 1.58-.07 4.73s0 3.5.07 4.73c.05 1.07.23 1.65.38 2.04.2.5.43.87.82 1.25.38.39.75.62 1.25.82.39.15.97.33 2.04.38 1.24.06 1.58.07 4.73.07s3.5 0 4.73-.07c1.07-.05 1.65-.23 2.04-.38.5-.2.87-.43 1.25-.82.39-.38.62-.75.82-1.25.15-.39.33-.97.38-2.04.06-1.24.07-1.58.07-4.73s0-3.5-.07-4.73c-.05-1.07-.23-1.65-.38-2.04a3.4 3.4 0 0 0-.82-1.25 3.4 3.4 0 0 0-1.25-.82c-.39-.15-.97-.33-2.04-.38-1.24-.06-1.58-.07-4.73-.07zm0 3.37a5.02 5.02 0 1 1 0 10.04 5.02 5.02 0 0 1 0-10.04zm0 8.28a3.26 3.26 0 1 0 0-6.52 3.26 3.26 0 0 0 0 6.52zm6.4-8.48a1.17 1.17 0 1 1-2.34 0 1.17 1.17 0 0 1 2.34 0z"/></svg>';
    $sns_html = '<ul id="footer_social_link" class="gd-sns-list">'
        . '<li class="gd-sns-item gd-sns-facebook"><a href="https://www.facebook.com/profile.php?id=61559697644215" target="_blank" rel="noopener" aria-label="Facebook">' . $sns_svg_fb . '</a></li>'
        . '<li class="gd-sns-item gd-sns-instagram"><a href="https://www.instagram.com/gyosei_medical/" target="_blank" rel="noopener" aria-label="Instagram">' . $sns_svg_ig . '</a></li>'
        . '</ul>';
    $html = preg_replace(
        '#<ul id="footer_social_link">.*?</ul>#us',
        $sns_html,
        $html
    );

    // 1g) Clinic detail pages: rebuild the WEB section.
    //     Legacy post template hardcodes:
    //       <div ...><P ...>WEB</P></div>
    //       <section style="background-color: #F3F2E9; ...">
    //         <a href=" {SITE_URL}"><img src="https://gyosei-medical.com/.../っs.png">  ← 404 on both hosts
    //         <a href=" {URL}"><img src="...1.png">  ← cross-domain SNS icon, often href=" "
    //         <a href=" {URL}"><img src="...2.png">
    //         <a href=" {URL}"><img src="...3.png">
    //       </section>
    //     We extract whichever hrefs are non-empty and emit a clean styled block.
    $sns_icon_set = [
        'instagram' => [
            'match' => '#(?:instagram\.com|instagr\.am)#i',
            'label' => 'Instagram',
            'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M12 2.2c3.2 0 3.58 0 4.85.07 1.17.05 1.8.25 2.23.42.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.37 1.06.42 2.23.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.05 1.17-.25 1.8-.42 2.23a3.7 3.7 0 0 1-.9 1.38 3.7 3.7 0 0 1-1.38.9c-.42.16-1.06.37-2.23.42-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-1.17-.05-1.8-.25-2.23-.42a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.37-1.06-.42-2.23C2.2 15.58 2.2 15.2 2.2 12s0-3.58.07-4.85c.05-1.17.25-1.8.42-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.37 2.23-.42C8.42 2.2 8.8 2.2 12 2.2zm0 1.98c-3.15 0-3.5 0-4.73.07-1.07.05-1.65.23-2.04.38-.5.2-.87.43-1.25.82-.39.38-.62.75-.82 1.25-.15.39-.33.97-.38 2.04-.06 1.24-.07 1.58-.07 4.73s0 3.5.07 4.73c.05 1.07.23 1.65.38 2.04.2.5.43.87.82 1.25.38.39.75.62 1.25.82.39.15.97.33 2.04.38 1.24.06 1.58.07 4.73.07s3.5 0 4.73-.07c1.07-.05 1.65-.23 2.04-.38.5-.2.87-.43 1.25-.82.39-.38.62-.75.82-1.25.15-.39.33-.97.38-2.04.06-1.24.07-1.58.07-4.73s0-3.5-.07-4.73c-.05-1.07-.23-1.65-.38-2.04a3.4 3.4 0 0 0-.82-1.25 3.4 3.4 0 0 0-1.25-.82c-.39-.15-.97-.33-2.04-.38-1.24-.06-1.58-.07-4.73-.07zm0 3.37a5.02 5.02 0 1 1 0 10.04 5.02 5.02 0 0 1 0-10.04zm0 8.28a3.26 3.26 0 1 0 0-6.52 3.26 3.26 0 0 0 0 6.52zm6.4-8.48a1.17 1.17 0 1 1-2.34 0 1.17 1.17 0 0 1 2.34 0z"/></svg>',
        ],
        'facebook' => [
            'match' => '#facebook\.com#i',
            'label' => 'Facebook',
            'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8.2h2.76l.41-3.2H13.5V7.55c0-.92.26-1.55 1.58-1.55h1.69V3.14C16.48 3.1 15.48 3 14.33 3 11.9 3 10.24 4.48 10.24 7.2v2.4H7.5v3.2h2.74V21h3.26z"/></svg>',
        ],
        'x' => [
            'match' => '#(?:twitter\.com|x\.com)#i',
            'label' => 'X',
            'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M18.244 2H21l-6.563 7.5L22 22h-6.797l-4.71-6.18L4.99 22H2.232l7.027-8.026L2 2h6.953l4.243 5.62L18.244 2zm-1.19 18.4h1.51L7.06 3.49H5.44l11.614 16.91z"/></svg>',
        ],
        'line' => [
            'match' => '#(?:line\.me|lin\.ee)#i',
            'label' => 'LINE',
            'svg'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M19.365 9.89c.41 0 .75.341.75.762a.755.755 0 0 1-.75.761h-2.087v1.337h2.087c.41 0 .75.34.75.76a.755.755 0 0 1-.75.762h-2.84a.755.755 0 0 1-.75-.762V8.027c0-.42.337-.762.75-.762h2.84c.41 0 .75.341.75.762 0 .42-.337.761-.75.761h-2.087v1.337h2.087zm-4.144 3.62a.755.755 0 0 1-.75.761.74.74 0 0 1-.6-.299l-2.91-3.96v3.498a.755.755 0 0 1-.75.761.755.755 0 0 1-.75-.761V8.027c0-.327.21-.617.514-.72a.7.7 0 0 1 .234-.04c.226 0 .445.114.583.3l2.93 3.972V8.027c0-.42.336-.762.75-.762.41 0 .75.341.75.762v5.483zm-7.119 0a.755.755 0 0 1-.75.761.755.755 0 0 1-.75-.761V8.027c0-.42.337-.762.75-.762.413 0 .75.341.75.762v5.483zm-2.65.761H2.612a.756.756 0 0 1-.751-.761V8.027c0-.42.337-.762.75-.762.414 0 .75.341.75.762v4.722h2.091c.41 0 .75.34.75.761 0 .42-.337.762-.75.762zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.302.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>',
        ],
    ];
    // NOTE: regex delimiter is `~` (not `#`) because the section style contains
    // a literal `#F3F2E9` color that would otherwise terminate the pattern early.
    $clinic_web_pattern =
        '~<div[^>]*style="[^"]*margin:\s*25px[^"]*"[^>]*>\s*' .
        '<P[^>]*>\s*WEB\s*</P>\s*</div>\s*' .
        '<section[^>]*style="[^"]*background-color:\s*\#F3F2E9[^"]*"[^>]*>(.*?)</section>~us';

    $html = preg_replace_callback(
        $clinic_web_pattern,
        function ($m) use ($sns_icon_set) {
            $body = $m[1];
            $site_url = '';
            // Main site CTA — first <a> wrapping an img with width="60%" or っs.png filename.
            if (preg_match('#<a\s+href="\s*([^"\s][^"]*?)\s*"[^>]*>\s*<img[^>]*(?:width="60%"|っs\.png)[^>]*>#u', $body, $sm)) {
                $site_url = trim($sm[1]);
            }
            // SNS row — collect every <a href="..."><img src="..." width="50px" ...></a>
            // Only keep entries with non-empty href.
            preg_match_all(
                '#<a\s+href="\s*([^"]*?)\s*"[^>]*>\s*<img[^>]*width="50px"[^>]*>\s*</a>#u',
                $body,
                $sns_matches,
                PREG_SET_ORDER
            );
            $sns_html = '';
            foreach ($sns_matches as $sm2) {
                $url = trim($sm2[1]);
                if ($url === '' || $url === '#') continue;
                // Detect platform from URL
                $svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3zM5 5h7v2H7v10h10v-5h2v7H5V5z"/></svg>';
                $label = 'リンク';
                foreach ($sns_icon_set as $info) {
                    if (preg_match($info['match'], $url)) {
                        $svg   = $info['svg'];
                        $label = $info['label'];
                        break;
                    }
                }
                $sns_html .= '<a class="gd-clinic-web-sns-item" href="' . htmlspecialchars($url, ENT_QUOTES) . '" target="_blank" rel="noopener" aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '">' . $svg . '</a>';
            }
            $out  = '<div class="gd-clinic-web">';
            $out .= '<h3 class="gd-clinic-web-label">WEB</h3>';
            if ($site_url !== '' && $site_url !== '#') {
                $out .= '<a class="gd-clinic-web-cta" href="' . htmlspecialchars($site_url, ENT_QUOTES) . '" target="_blank" rel="noopener">';
                $out .= '<span class="gd-clinic-web-cta-text">公式サイトを見る</span>';
                $out .= '<span class="gd-clinic-web-cta-arrow" aria-hidden="true">&rsaquo;</span>';
                $out .= '</a>';
            } else {
                $out .= '<p class="gd-clinic-web-empty">公式サイト情報は準備中です。</p>';
            }
            if ($sns_html !== '') {
                $out .= '<div class="gd-clinic-web-sns">' . $sns_html . '</div>';
            }
            $out .= '</div>';
            return $out;
        },
        $html
    );

    // 1h) Replace deprecated Google Maps `pb=` embed iframes with the legacy
    //     parameterless `?q=lat,lng&output=embed` format. The pb= URLs return
    //     404 + X-Frame-Options:SAMEORIGIN now (Google deprecated them), so the
    //     ACCESS section appears as empty space. We extract !2d{lon} and
    //     !3d{lat} from the original src to preserve the pin location.
    $html = preg_replace_callback(
        '#<iframe([^>]*?)\s+src="https://www\.google\.com/maps/embed\?pb=([^"]+)"([^>]*)>(\s*</iframe>)#u',
        function ($m) {
            $before = $m[1];
            $pb     = $m[2];
            $after  = $m[3];
            $end    = $m[4];
            $lat = $lon = null;
            if (preg_match('#!2d(-?\d+(?:\.\d+)?)#', $pb, $mlon)) $lon = $mlon[1];
            if (preg_match('#!3d(-?\d+(?:\.\d+)?)#', $pb, $mlat)) $lat = $mlat[1];
            if ($lat === null || $lon === null) return $m[0];
            $new_src = 'https://www.google.com/maps?q=' . $lat . ',' . $lon . '&z=16&output=embed';
            return '<iframe' . $before . ' src="' . $new_src . '"' . $after . '>' . $end;
        },
        $html
    );

    // 2) On the homepage, restructure the bottom banner strip:
    //    - add a .gm-home-banners class hook to the clearfix container so CSS grids it
    //    - inject a single CTA button after the banner strip
    if (strpos($html, '<!-- END #main_col -->') !== false &&
        strpos($html, 'cb_content-wysiwyg') !== false &&
        !strpos($html, 'gm-home-cta-btn')) {

        // Tag the clearfix container so CSS can grid it. TCD renders:
        //     <div id="cb_1" class="cb_content cb_content-wysiwyg">
        //         <div class="inner">
        //             <div class=" clearfix">   <-- we add gm-home-banners here
        $html = preg_replace(
            '#(<div id="cb_1"[^>]*cb_content-wysiwyg[^>]*>\s*<div class="inner">\s*<div class=")(\s*clearfix)(")#u',
            '$1$2 gm-home-banners$3',
            $html
        );

        // Tag each `<div class="">` banner child with gm-home-banner-item
        $html = preg_replace(
            '#<div class=""(\s+style="padding-bottom:\s*30px[^"]*")?>#u',
            '<div class="gm-home-banner-item"$1>',
            $html
        );

        // Rebuild each banner card individually with clean HTML.
        // Each replace is narrowly scoped to one banner's image filename so the
        // runs don't interfere with each other.
        $banner_labels = [
            'GM_バナー適正サイズ' => ['title' => 'GYOSEI MEDICAL', 'sub' => '暁星OB医師開業情報ポータル'],
            '1-2.png'             => ['title' => 'GYOSEI EATS',    'sub' => '暁星OB飲食店ポータル'],
            '2-2.png'             => ['title' => 'LIBUN',          'sub' => 'Reputation / webPR'],
        ];

        foreach ($banner_labels as $match_str => $label) {
            $safe = preg_quote($match_str, '#');
            $pattern =
                '#<div class="gm-home-banner-item"[^>]*>' .
                '\s*<center[^>]*>\s*<a\s+href="([^"]+)"[^>]*>\s*' .
                '<img[^>]+src="([^"]*' . $safe . '[^"]*)"[^>]*>\s*' .
                '</a>\s*</center>' .
                '(?:(?!<div class="gm-home-).)*?' .
                '</div>#us';

            $title = $label['title'];
            $sub   = $label['sub'];

            $html = preg_replace_callback(
                $pattern,
                function ($m) use ($title) {
                    $href = $m[1];
                    $src  = $m[2];
                    $is_external = (strpos($href, 'gyosei-dental.com') === false);
                    $target_attr = $is_external ? ' target="_blank" rel="noopener"' : '';
                    // Banner image already contains brand name graphic — no extra label needed.
                    return '<div class="gm-home-banner-item">' .
                        '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"' . $target_attr . '>' .
                        '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="' . htmlspecialchars($title, ENT_QUOTES) . '">' .
                        '</a>' .
                        '</div>';
                },
                $html
            );
        }

        // Inject CTA card as the 4th item INSIDE the gm-home-banners grid.
        // The 5 closing </div>s at the end of #main_col are, in order:
        //   1) close last banner card
        //   2) close .gm-home-banners (clearfix)
        //   3) close .inner
        //   4) close #cb_1
        //   5) close #main_col
        // We inject the CTA card between 1 and 2 so it lives inside gm-home-banners.
        $cta_card =
            '<div class="gm-home-banner-item gm-home-cta-card">' .
            '<a href="/join/" class="gm-home-cta-btn">' .
            '<span class="gm-home-cta-label">掲載をご希望の方はこちら</span>' .
            '<span class="gm-home-cta-arrow">&rsaquo;</span>' .
            '</a></div>';
        $html = preg_replace(
            '#(</div>)(\s*</div>\s*</div>\s*</div>\s*</div>\s*<!-- END \#main_col -->)#u',
            '$1' . $cta_card . '$2',
            $html,
            1
        );
    }

    return $html;
}

