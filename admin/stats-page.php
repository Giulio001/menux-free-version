<?php
/**
 * MenuX Pro — Statistics Page
 * Stat query helpers and admin analytics dashboard.
 * @package MenuX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function menux_get_device_stats($days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $wpdb->get_results($wpdb->prepare(
        "SELECT device_type, COUNT(*) as clicks FROM $table WHERE clicked_at >= %s AND device_type != '' GROUP BY device_type ORDER BY clicks DESC",
        $since
    ));
}

function menux_get_top_referrers($days = 30, $limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $wpdb->get_results($wpdb->prepare(
        "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1) as domain,
                COUNT(*) as clicks
         FROM $table WHERE clicked_at >= %s AND referrer != ''
         GROUP BY domain ORDER BY clicks DESC LIMIT %d",
        $since, $limit
    ));
}

function menux_get_country_stats($days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'menux_stats';
    $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    return $wpdb->get_results($wpdb->prepare(
        "SELECT country, COUNT(*) as clicks FROM $table WHERE clicked_at >= %s AND country != '' GROUP BY country ORDER BY clicks DESC LIMIT 10",
        $since
    ));
}

/**
 * ──── FEATURE 3: Smart Suggestions Engine ────
 * Analizza le statistiche e genera suggerimenti actionable.
 */
function menux_get_smart_suggestions($top, $total, $days) {
    $suggestions = array();
    if (empty($top) || $total == 0) return $suggestions;

    $menu_items = get_option('menux_menu_items', array());
    $item_keys_in_stats = array_column((array)$top, 'item_key');

    // 1. Voci con 0 click
    foreach ($menu_items as $item) {
        $key = $item['item_key'] ?? '';
        if (!empty($key) && !in_array($key, $item_keys_in_stats)) {
            $label = '';
            foreach ($item as $k => $v) {
                if (strpos($k, 'lang_') === 0 && !empty($v)) { $label = $v; break; }
            }
            if (empty($label) && $item['type'] === 'page') $label = get_the_title($item['id'] ?? 0);
            if (empty($label)) $label = $key;
            $suggestions[] = array(
                'type'    => 'warning',
                'icon'    => '⚠️',
                'title'   => 'Zero clicks on "' . esc_html($label) . '"',
                'text'    => 'This item received no clicks in ' . $days . ' days. Consider repositioning or removing it.',
                'action'  => 'Manage',
            );
        }
    }

    // 2. Voce con alto CTR ma in basso nella lista
    if (count($top) > 3) {
        foreach (array_slice((array)$top, 3) as $row) {
            $share = round(($row->total_clicks / $total) * 100);
            if ($share >= 15) {
                $suggestions[] = array(
                    'type'  => 'success',
                    'icon'  => '📈',
                    'title' => '"' . esc_html($row->item_label ?: $row->item_key) . '" is very popular',
                    'text'  => 'With ' . $share . '% of clicks, it deserves a higher position in the menu.',
                    'action'=> 'Move up',
                );
            }
        }
    }

    // 3. Click concentrati solo su loggati → ospiti ignorano il menu
    $logged = !empty($top) ? array_sum(array_column((array)$top, 'logged_clicks')) : 0;
    $guest  = $total - $logged;
    if ($total > 50 && $guest < $total * 0.15) {
        $suggestions[] = array(
            'type'  => 'info',
            'icon'  => '👥',
            'title' => 'Guests almost never click',
            'text'  => 'Only ' . round($guest / $total * 100) . '% of clicks come from non-logged-in visitors. Make sure the menu is visible and useful for new visitors too.',
            'action'=> 'Check visibility',
        );
    }

    // 4. Trend in discesa
    $prev_total = menux_get_total_clicks($days * 2) - $total;
    if ($prev_total > 20 && $total < $prev_total * 0.7) {
        $drop = round((1 - $total / $prev_total) * 100);
        $suggestions[] = array(
            'type'  => 'danger',
            'icon'  => '📉',
            'title' => 'Traffic decline of ' . $drop . '%',
            'text'  => 'The menu received fewer clicks compared to the previous period. This may indicate a visibility or content issue.',
            'action'=> 'Analyze',
        );
    }

    return array_slice($suggestions, 0, 4); // Max 4 suggerimenti
}
function menux_render_stats_page() {
    if (!current_user_can('manage_options')) return;
    $days  = isset($_GET['days']) ? intval($_GET['days']) : 30;
    $days  = in_array($days, array(7,14,30,90), true) ? $days : 30;
    $top   = menux_get_top_clicks(20, $days);
    $daily = menux_get_daily_clicks($days);
    $total = menux_get_total_clicks($days);
    $max_clicks = !empty($top) ? max(array_column((array)$top, 'total_clicks')) : 1;
    $reset_nonce = wp_create_nonce('menux_reset_stats_nonce');

    // ─── Calcoli per trend / KPI avanzati ───
    $unique_items = count($top);
    $logged_total = !empty($top) ? array_sum(array_column((array)$top, 'logged_clicks')) : 0;
    $guest_total  = $total - $logged_total;

    // Confronto col periodo precedente per calcolare il trend
    $prev_total   = menux_get_total_clicks($days * 2) - $total;
    $trend_pct    = 0;
    if ($prev_total > 0) {
        $trend_pct = round((($total - $prev_total) / $prev_total) * 100);
    } elseif ($total > 0) {
        $trend_pct = 100;
    }

    // Media click/giorno
    $avg_per_day = $days > 0 ? round($total / $days, 1) : 0;

    // Top performer
    $top_performer = !empty($top) ? $top[0] : null;
    ?>
    <style>
    .bm-stats-wrap { max-width: 1400px; }
    .bm-stats-wrap * { box-sizing: border-box; }

    /* Header hero */
    .bm-stats-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: #fff;
        margin: 16px 0 24px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 40px -10px rgba(102,126,234,.4);
    }
    .bm-stats-hero::before {
        content: '';
        position: absolute;
        top: -50%; right: -10%;
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .bm-stats-hero h1 {
        margin: 0 0 6px;
        font-size: 26px;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .bm-stats-hero .bm-subtitle {
        font-size: 14px;
        opacity: .85;
        margin: 0;
    }
    .bm-stats-hero .bm-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,.2);
        backdrop-filter: blur(10px);
        color: #fff;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        margin-top: 14px;
        transition: background .2s;
        border: 1px solid rgba(255,255,255,.3);
    }
    .bm-stats-hero .bm-back-btn:hover { background: rgba(255,255,255,.3); color: #fff; }

    /* Toolbar */
    .bm-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .bm-toolbar-label {
        font-weight: 600;
        font-size: 13px;
        color: #4b5563;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .bm-pill {
        padding: 7px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid transparent;
        transition: all .2s;
    }
    .bm-pill:hover { background: #e5e7eb; color: #1f2937; }
    .bm-pill.active {
        background: linear-gradient(135deg,#667eea,#764ba2);
        color: #fff !important;
        box-shadow: 0 4px 12px -2px rgba(102,126,234,.5);
    }
    .bm-btn-danger {
        margin-left: auto;
        background: #fff;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .bm-btn-danger:hover { background: #fef2f2; border-color: #f87171; }
    .bm-btn-export {
        background: #fff;
        border: 1px solid #d1d5db;
        color: #374151;
        padding: 7px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
    }
    .bm-btn-export:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }

    /* KPI Cards */
    .bm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .bm-kpi-card {
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.03);
        transition: transform .2s, box-shadow .2s;
        border: 1px solid #f1f5f9;
    }
    .bm-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -8px rgba(0,0,0,.1);
    }
    .bm-kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--bm-accent, #667eea);
    }
    .bm-kpi-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        background: var(--bm-bg, #eef2ff);
        margin-bottom: 12px;
    }
    .bm-kpi-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin: 0 0 6px;
    }
    .bm-kpi-value {
        font-size: 30px;
        font-weight: 700;
        color: #111827;
        line-height: 1.1;
        margin: 0;
        font-variant-numeric: tabular-nums;
    }
    .bm-kpi-extra {
        font-size: 12px;
        margin-top: 8px;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .bm-trend-up   { color: #059669; font-weight: 600; }
    .bm-trend-down { color: #dc2626; font-weight: 600; }
    .bm-trend-flat { color: #6b7280; font-weight: 600; }

    /* Panel */
    .bm-panel {
        background: #fff;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,.05);
        border: 1px solid #f1f5f9;
        margin-bottom: 20px;
    }
    .bm-panel-title {
        margin: 0 0 18px;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bm-panel-title .bm-badge {
        background: #eef2ff;
        color: #4f46e5;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 999px;
        margin-left: auto;
    }

    /* Top performer hero */
    .bm-top-hero {
        background: linear-gradient(135deg,#fef3c7 0%, #fde68a 100%);
        border: 1px solid #fcd34d;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }
    .bm-trophy {
        width: 60px; height: 60px;
        background: #fff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(0,0,0,.1);
    }
    .bm-top-hero-content { flex: 1; min-width: 200px; }
    .bm-top-hero-content small {
        font-size: 11px;
        color: #92400e;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: .5px;
    }
    .bm-top-hero-content strong {
        display: block;
        font-size: 20px;
        color: #78350f;
        margin: 2px 0 4px;
        font-weight: 700;
    }
    .bm-top-hero-content span {
        font-size: 12px;
        color: #92400e;
    }
    .bm-top-hero-stat {
        text-align: center;
        padding: 0 16px;
        border-left: 1px dashed #d97706;
    }
    .bm-top-hero-stat b {
        display: block;
        font-size: 26px;
        color: #78350f;
        line-height: 1;
        font-weight: 700;
    }
    .bm-top-hero-stat small {
        font-size: 10px;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* Ranking list */
    .bm-rank-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .bm-rank-item:last-child { border-bottom: none; }
    .bm-rank-pos {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
        background: #f3f4f6;
        color: #6b7280;
    }
    .bm-rank-pos.bm-pos-1 { background: linear-gradient(135deg,#fbbf24,#f59e0b); color: #fff; }
    .bm-rank-pos.bm-pos-2 { background: linear-gradient(135deg,#d1d5db,#9ca3af); color: #fff; }
    .bm-rank-pos.bm-pos-3 { background: linear-gradient(135deg,#f97316,#ea580c); color: #fff; }
    .bm-rank-body { flex: 1; min-width: 0; }
    .bm-rank-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        margin-bottom: 4px;
        gap: 8px;
    }
    .bm-rank-label {
        font-size: 14px;
        font-weight: 600;
        color: #111827;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .bm-rank-clicks {
        font-size: 13px;
        font-weight: 700;
        color: #4f46e5;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .bm-rank-bar {
        height: 8px;
        background: #f3f4f6;
        border-radius: 99px;
        overflow: hidden;
        margin-bottom: 4px;
    }
    .bm-rank-bar-fill {
        height: 100%;
        background: linear-gradient(90deg,#667eea,#764ba2);
        border-radius: 99px;
        animation: bm-bar-grow 1s cubic-bezier(.4,0,.2,1) both;
    }
    @keyframes bm-bar-grow { from { width: 0 !important; } }
    .bm-rank-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 11px;
        color: #6b7280;
    }
    .bm-rank-meta .bm-chip {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 1px 8px;
        background: #f9fafb;
        border-radius: 999px;
        border: 1px solid #f3f4f6;
    }
    .bm-rank-url {
        font-size: 10px;
        color: #9ca3af;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-left: auto;
        max-width: 50%;
    }

    /* Empty state */
    .bm-empty {
        background: linear-gradient(135deg,#f9fafb,#fff);
        border: 2px dashed #e5e7eb;
        border-radius: 16px;
        padding: 60px 20px;
        text-align: center;
    }
    .bm-empty-icon {
        font-size: 56px;
        margin-bottom: 16px;
        opacity: .5;
    }
    .bm-empty h3 {
        font-size: 18px;
        color: #374151;
        margin: 0 0 8px;
    }
    .bm-empty p {
        font-size: 14px;
        color: #9ca3af;
        margin: 0 auto;
        max-width: 480px;
    }

    /* Two-col grid */
    .bm-grid-2 {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    @media (min-width: 1100px) {
        .bm-grid-2 { grid-template-columns: 1.3fr 1fr; }
    }

    /* SVG chart container */
    .bm-chart-svg-wrap {
        position: relative;
        overflow-x: auto;
        padding: 8px 0 4px;
    }
    .bm-bar-group rect { transition: opacity .2s, filter .2s; cursor: pointer; }
    .bm-bar-group:hover rect { opacity: 1 !important; filter: drop-shadow(0 2px 4px rgba(102,126,234,.4)); }

    /* Donut */
    .bm-donut-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    .bm-donut-legend {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .bm-legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
    }
    .bm-legend-dot {
        width: 12px; height: 12px;
        border-radius: 4px;
        flex-shrink: 0;
    }
    .bm-legend-item b { font-size: 16px; color: #111827; min-width: 40px; }
    .bm-legend-item small { color: #9ca3af; font-size: 11px; }

    /* Hide WP H1 */
    .bm-stats-wrap > h1.wp-heading-inline { display: none; }
    </style>

    <div class="wrap bm-stats-wrap">
        <h1 class="wp-heading-inline" style="display:none;">MenuX Statistics</h1>

        <!-- HERO HEADER -->
        <div class="bm-stats-hero">
            <h1>📊 Statistics MenuX</h1>
            <p class="bm-subtitle">Analyze your visitors' behavior and optimize the menu based on real data</p>
            <a href="<?php echo admin_url('admin.php?page=menux'); ?>" class="bm-back-btn">
                <span style="font-size:16px;">←</span> Back to configuration
            </a>
        </div>

        <!-- TOOLBAR -->
        <div class="bm-toolbar">
            <span class="bm-toolbar-label">⏱️ Period</span>
            <?php foreach (array(7=>'7 days',14=>'14 days',30=>'30 days',90=>'90 days') as $d => $label): ?>
                <a href="<?php echo admin_url("admin.php?page=menux-stats&days={$d}"); ?>"
                   class="bm-pill<?php echo $days == $d ? ' active' : ''; ?>"><?php echo $label; ?></a>
            <?php endforeach; ?>
            <button type="button" class="bm-btn-export" onclick="menux_exportCSV()" title="Export CSV">
                📥 Export CSV
            </button>
            <button type="button" class="bm-btn-danger" onclick="if(confirm('Delete all statistics? This action cannot be undone.')){menux_resetStats();}">
                🗑️ Reset
            </button>
        </div>

        <!-- KPI GRID -->
        <div class="bm-kpi-grid">
            <!-- Total clicks con trend -->
            <div class="bm-kpi-card" style="--bm-accent:#667eea; --bm-bg:#eef2ff;">
                <div class="bm-kpi-icon">🖱️</div>
                <p class="bm-kpi-label">Total clicks</p>
                <p class="bm-kpi-value"><?php echo number_format($total); ?></p>
                <div class="bm-kpi-extra">
                    <?php if ($trend_pct > 0): ?>
                        <span class="bm-trend-up">▲ +<?php echo $trend_pct; ?>%</span>
                    <?php elseif ($trend_pct < 0): ?>
                        <span class="bm-trend-down">▼ <?php echo $trend_pct; ?>%</span>
                    <?php else: ?>
                        <span class="bm-trend-flat">— stabile</span>
                    <?php endif; ?>
                    <span style="color:#9ca3af;">vs periodo precedente</span>
                </div>
            </div>

            <!-- Media per giorno -->
            <div class="bm-kpi-card" style="--bm-accent:#10b981; --bm-bg:#d1fae5;">
                <div class="bm-kpi-icon">📈</div>
                <p class="bm-kpi-label">Daily average</p>
                <p class="bm-kpi-value"><?php echo number_format($avg_per_day, 1); ?></p>
                <div class="bm-kpi-extra">
                    <span style="color:#9ca3af;">clicks per day · <?php echo $unique_items; ?> voci attive</span>
                </div>
            </div>

            <!-- Logged in -->
            <div class="bm-kpi-card" style="--bm-accent:#f59e0b; --bm-bg:#fef3c7;">
                <div class="bm-kpi-icon">👤</div>
                <p class="bm-kpi-label">From logged-in users</p>
                <p class="bm-kpi-value"><?php echo number_format($logged_total); ?></p>
                <div class="bm-kpi-extra">
                    <?php $lp = $total > 0 ? round($logged_total / $total * 100) : 0; ?>
                    <span style="font-weight:600;color:#f59e0b;"><?php echo $lp; ?>%</span>
                    <span style="color:#9ca3af;">of total</span>
                </div>
            </div>

            <!-- Guests -->
            <div class="bm-kpi-card" style="--bm-accent:#6b7280; --bm-bg:#f3f4f6;">
                <div class="bm-kpi-icon">👥</div>
                <p class="bm-kpi-label">Guests</p>
                <p class="bm-kpi-value"><?php echo number_format($guest_total); ?></p>
                <div class="bm-kpi-extra">
                    <?php $gp = $total > 0 ? round($guest_total / $total * 100) : 0; ?>
                    <span style="font-weight:600;color:#6b7280;"><?php echo $gp; ?>%</span>
                    <span style="color:#9ca3af;">of total</span>
                </div>
            </div>
        </div>

        <?php if (empty($top)): ?>
            <div class="bm-empty">
                <div class="bm-empty-icon">📭</div>
                <h3>No data for this period</h3>
                <p>Clicks are automatically recorded when users interact with the menu, both logged-in and guests. Check back after a few hours of traffic.</p>
            </div>
        <?php else: ?>

        <!-- ──── FEATURE 3: SMART SUGGESTIONS ──── -->
        <?php
        $suggestions = menux_get_smart_suggestions($top, $total, $days);
        if (!empty($suggestions)):
        ?>
        <div style="margin-bottom:20px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px;">
            <?php
            $sg_colors = array(
                'warning' => array('bg'=>'#fffbeb','border'=>'#fde68a','color'=>'#92400e'),
                'success' => array('bg'=>'#ecfdf5','border'=>'#a7f3d0','color'=>'#065f46'),
                'info'    => array('bg'=>'#eff6ff','border'=>'#bfdbfe','color'=>'#1e40af'),
                'danger'  => array('bg'=>'#fef2f2','border'=>'#fecaca','color'=>'#991b1b'),
            );
            foreach ($suggestions as $sg):
                $c = $sg_colors[$sg['type']] ?? $sg_colors['info'];
            ?>
            <div style="background:<?php echo $c['bg']; ?>; border:1px solid <?php echo $c['border']; ?>; border-radius:12px; padding:14px 16px; display:flex; gap:12px; align-items:flex-start;">
                <span style="font-size:22px; flex-shrink:0; line-height:1;"><?php echo $sg['icon']; ?></span>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13px; font-weight:700; color:<?php echo $c['color']; ?>; margin-bottom:3px;"><?php echo $sg['title']; ?></div>
                    <div style="font-size:12px; color:<?php echo $c['color']; ?>; opacity:.85; line-height:1.4;"><?php echo $sg['text']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- TOP PERFORMER HERO -->
        <?php if ($top_performer):
            $tp_label = $top_performer->item_label ?: $top_performer->item_key;
            $tp_total = (int) $top_performer->total_clicks;
            $tp_share = $total > 0 ? round($tp_total / $total * 100) : 0;
        ?>
        <div class="bm-top-hero">
            <div class="bm-trophy">🏆</div>
            <div class="bm-top-hero-content">
                <small>Most clicked item</small>
                <strong><?php echo esc_html($tp_label); ?></strong>
                <span><?php echo esc_html($top_performer->item_url ?: $top_performer->item_key); ?></span>
            </div>
            <div class="bm-top-hero-stat">
                <b><?php echo number_format($tp_total); ?></b>
                <small>Click</small>
            </div>
            <div class="bm-top-hero-stat">
                <b><?php echo $tp_share; ?>%</b>
                <small>of traffic</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRID 2 COLONNE: Andamento + User distribution -->
        <div class="bm-grid-2">

            <!-- ANDAMENTO -->
            <div class="bm-panel">
                <h2 class="bm-panel-title">
                    📈 Daily click trend
                    <span class="bm-badge"><?php echo $days; ?> days</span>
                </h2>
                <?php if (!empty($daily)):
                    $all_clicks   = array_column((array)$daily, 'clicks');
                    $max_day      = max(array_map('intval', $all_clicks));
                    $max_day      = max($max_day, 1);
                    $chart_w      = 800;
                    $chart_h      = 220;
                    $pad_left     = 40;
                    $pad_right    = 14;
                    $pad_top      = 20;
                    $pad_bottom   = 40;
                    $draw_w       = $chart_w - $pad_left - $pad_right;
                    $draw_h       = $chart_h - $pad_top - $pad_bottom;
                    $n            = count($daily);
                    $bar_gap      = 4;
                    $bar_w        = max(4, ($draw_w / max($n, 1)) - $bar_gap);
                    $slot_w       = $draw_w / max($n, 1);
                    $y_labels     = array(0, round($max_day*0.25), round($max_day*0.5), round($max_day*0.75), $max_day);
                ?>
                <div class="bm-chart-svg-wrap">
                <svg viewBox="0 0 <?php echo $chart_w; ?> <?php echo $chart_h; ?>" style="width:100%;max-width:<?php echo $chart_w;?>px;height:auto;display:block;" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="bm-bar-grad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#667eea" stop-opacity="1"/>
                            <stop offset="100%" stop-color="#764ba2" stop-opacity=".85"/>
                        </linearGradient>
                        <linearGradient id="bm-area-grad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#667eea" stop-opacity=".25"/>
                            <stop offset="100%" stop-color="#667eea" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    <!-- Grid lines -->
                    <?php foreach ($y_labels as $yv):
                        $y = $pad_top + $draw_h - round(($yv / $max_day) * $draw_h);
                    ?>
                    <line x1="<?php echo $pad_left;?>" y1="<?php echo $y;?>" x2="<?php echo $chart_w - $pad_right;?>" y2="<?php echo $y;?>" stroke="#f3f4f6" stroke-width="1" stroke-dasharray="<?php echo $yv > 0 ? '3,4' : 'none';?>"/>
                    <text x="<?php echo $pad_left - 6;?>" y="<?php echo $y + 4;?>" text-anchor="end" font-size="10" font-weight="500" fill="#9ca3af"><?php echo $yv;?></text>
                    <?php endforeach; ?>

                    <!-- Area path -->
                    <?php
                    $area_pts = array();
                    foreach ($daily as $i => $d) {
                        $clicks = intval($d->clicks);
                        $cx = $pad_left + ($i * $slot_w) + $slot_w/2;
                        $cy = $pad_top + $draw_h - round(($clicks / $max_day) * $draw_h);
                        $area_pts[] = round($cx).','.$cy;
                    }
                    if (count($area_pts) > 1) {
                        $first_x = $pad_left + $slot_w/2;
                        $last_x  = $pad_left + (($n-1) * $slot_w) + $slot_w/2;
                        $base_y  = $pad_top + $draw_h;
                        $area_d  = 'M'.round($first_x).','.$base_y.' L'.implode(' L', $area_pts).' L'.round($last_x).','.$base_y.' Z';
                        echo '<path d="'.$area_d.'" fill="url(#bm-area-grad)"/>';
                    }
                    ?>

                    <!-- Bars -->
                    <?php foreach ($daily as $i => $d):
                        $clicks   = intval($d->clicks);
                        $bar_h_px = max(2, round(($clicks / $max_day) * $draw_h));
                        $x        = $pad_left + ($i * $slot_w) + ($slot_w - $bar_w) / 2;
                        $y        = $pad_top + $draw_h - $bar_h_px;
                        $label    = date('d/m', strtotime($d->day));
                    ?>
                    <g class="bm-bar-group">
                        <rect x="<?php echo round($x);?>" y="<?php echo $y;?>" width="<?php echo round($bar_w);?>" height="<?php echo $bar_h_px;?>"
                              rx="4" ry="4" fill="url(#bm-bar-grad)" opacity="0.92">
                            <title><?php echo esc_attr($d->day . ': ' . $clicks . ' click');?></title>
                        </rect>
                        <?php if ($bar_h_px > 18): ?>
                        <text x="<?php echo round($x + $bar_w/2);?>" y="<?php echo $y - 5;?>" text-anchor="middle" font-size="10" font-weight="700" fill="#4f46e5"><?php echo $clicks;?></text>
                        <?php endif; ?>
                        <text x="<?php echo round($x + $bar_w/2);?>" y="<?php echo $pad_top + $draw_h + 16;?>" text-anchor="middle" font-size="<?php echo $n > 20 ? '8' : '10';?>" fill="#9ca3af" font-weight="500"
                              transform="rotate(-30,<?php echo round($x + $bar_w/2);?>,<?php echo $pad_top + $draw_h + 16;?>)"><?php echo $label;?></text>
                    </g>
                    <?php endforeach; ?>

                    <!-- Baseline -->
                    <line x1="<?php echo $pad_left;?>" y1="<?php echo $pad_top + $draw_h;?>" x2="<?php echo $chart_w - $pad_right;?>" y2="<?php echo $pad_top + $draw_h;?>" stroke="#e5e7eb" stroke-width="1.5"/>
                </svg>
                </div>
                <?php else: ?>
                    <p style="color:#9ca3af; font-style:italic; font-size:13px; text-align:center; padding:24px 0;">No data available.</p>
                <?php endif; ?>
            </div>

            <!-- DONUT: Logged in vs Guests -->
            <div class="bm-panel">
                <h2 class="bm-panel-title">👥 User distribution</h2>
                <?php
                    $logged_pct = $total > 0 ? ($logged_total / $total) : 0;
                    $r_donut = 70;
                    $cx_d = 100; $cy_d = 100;
                    $circ = 2 * M_PI * $r_donut;
                    $logged_dash = $circ * $logged_pct;
                ?>
                <div class="bm-donut-wrap">
                    <svg width="200" height="200" viewBox="0 0 200 200">
                        <defs>
                            <linearGradient id="bm-donut-logged" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#fbbf24"/>
                                <stop offset="100%" stop-color="#f59e0b"/>
                            </linearGradient>
                        </defs>
                        <!-- Background ring (rappresenta gli ospiti) -->
                        <circle cx="<?php echo $cx_d; ?>" cy="<?php echo $cy_d; ?>" r="<?php echo $r_donut; ?>" fill="none" stroke="#e5e7eb" stroke-width="22"/>
                        <!-- Logged segment -->
                        <?php if ($logged_total > 0): ?>
                        <circle cx="<?php echo $cx_d; ?>" cy="<?php echo $cy_d; ?>" r="<?php echo $r_donut; ?>" fill="none"
                                stroke="url(#bm-donut-logged)" stroke-width="22"
                                stroke-dasharray="<?php echo $logged_dash; ?> <?php echo $circ; ?>"
                                stroke-linecap="round"
                                transform="rotate(-90 <?php echo $cx_d; ?> <?php echo $cy_d; ?>)"/>
                        <?php endif; ?>
                        <!-- Center text -->
                        <text x="<?php echo $cx_d; ?>" y="<?php echo $cy_d - 4; ?>" text-anchor="middle" font-size="28" font-weight="700" fill="#111827"><?php echo number_format($total); ?></text>
                        <text x="<?php echo $cx_d; ?>" y="<?php echo $cy_d + 16; ?>" text-anchor="middle" font-size="11" fill="#9ca3af" font-weight="500">TOTAL CLICKS</text>
                    </svg>

                    <div class="bm-donut-legend">
                        <div class="bm-legend-item">
                            <span class="bm-legend-dot" style="background:linear-gradient(135deg,#fbbf24,#f59e0b);"></span>
                            <div>
                                <b><?php echo number_format($logged_total); ?></b>
                                <div><small>Logged in · <?php echo $total > 0 ? round($logged_total/$total*100) : 0; ?>%</small></div>
                            </div>
                        </div>
                        <div class="bm-legend-item">
                            <span class="bm-legend-dot" style="background:#e5e7eb;"></span>
                            <div>
                                <b><?php echo number_format($guest_total); ?></b>
                                <div><small>Guests · <?php echo $total > 0 ? round($guest_total/$total*100) : 0; ?>%</small></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- RANKING (full width) -->
        <div class="bm-panel">
            <h2 class="bm-panel-title">
                🏆 Most clicked items ranking
                <span class="bm-badge">TOP <?php echo count($top); ?></span>
            </h2>
            <?php
            $medals = array('🥇','🥈','🥉');
            foreach ($top as $idx => $row):
                $pct = $max_clicks > 0 ? round(($row->total_clicks / $max_clicks) * 100) : 0;
                $label = $row->item_label ?: $row->item_key;
                $pos = $idx + 1;
                $pos_class = $pos <= 3 ? 'bm-pos-' . $pos : '';
                $pos_label = $pos <= 3 ? $medals[$pos-1] : '#' . $pos;
            ?>
            <div class="bm-rank-item">
                <div class="bm-rank-pos <?php echo $pos_class; ?>"><?php echo $pos_label; ?></div>
                <div class="bm-rank-body">
                    <div class="bm-rank-head">
                        <span class="bm-rank-label" title="<?php echo esc_attr($label); ?>"><?php echo esc_html($label); ?></span>
                        <span class="bm-rank-clicks"><?php echo number_format($row->total_clicks); ?> click</span>
                    </div>
                    <div class="bm-rank-bar">
                        <div class="bm-rank-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                    <div class="bm-rank-meta">
                        <span class="bm-chip" title="Clicks from logged-in users">👤 <?php echo $row->logged_clicks; ?></span>
                        <span class="bm-chip" title="Clicks from guests">👥 <?php echo $row->guest_clicks; ?></span>
                        <span class="bm-chip" title="Last click">🕒 <?php echo human_time_diff(strtotime($row->last_click), current_time('timestamp')); ?> ago</span>
                        <?php if (!empty($row->item_url)): ?>
                        <span class="bm-rank-url" title="<?php echo esc_attr($row->item_url); ?>"><?php echo esc_html($row->item_url); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <script>
    var menux_reset_stats_nonce = '<?php echo $reset_nonce; ?>';
    function menux_resetStats() {
        jQuery.post(ajaxurl, { action:'menux_reset_stats', nonce: menux_reset_stats_nonce }, function(r) {
            if (r.success) { location.reload(); }
            else alert('Error.');
        });
    }
    function menux_exportCSV() {
        var rows = <?php echo wp_json_encode(array_map(function($r){
            return array(
                'label'    => $r->item_label ?: $r->item_key,
                'url'      => $r->item_url,
                'total'    => (int)$r->total_clicks,
                'logged'   => (int)$r->logged_clicks,
                'guest'    => (int)$r->guest_clicks,
                'last'     => $r->last_click,
            );
        }, $top ?: array())); ?>;
        if (!rows.length) { alert('No data to export.'); return; }
        var csv = 'Item,URL,Total clicks,Logged in,Guests,Last click\n';
        rows.forEach(function(r){
            var line = [r.label, r.url, r.total, r.logged, r.guest, r.last]
                .map(function(v){ v = (v||'').toString().replace(/"/g,'""'); return '"'+v+'"'; })
                .join(',');
            csv += line + '\n';
        });
        var blob = new Blob([csv], {type: 'text/csv;charset=utf-8'});
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href = url;
        a.download = 'menux-stats-<?php echo $days; ?>d-' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
    <?php
}
