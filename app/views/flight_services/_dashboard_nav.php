<?php
/** Barra de pestañas del Panel Analítico: General + paneles por aerolínea.
 *  Requiere $navActivo ('general' | 'avianca' | 'clic' | 'satena'). */
$navItems = [
    'general' => ['texto' => 'Panel general', 'url' => BASE_URL . '/flight-services/dashboard',                    'icono' => 'bi-grid-1x2-fill', 'color' => '#1B4F8A', 'logo' => null],
    'avianca' => ['texto' => 'AVIANCA',       'url' => BASE_URL . '/flight-services/dashboard-aerolinea/avianca',   'icono' => null, 'color' => '#E30613', 'logo' => 'logo_avianca.png'],
    'clic'    => ['texto' => 'CLIC',          'url' => BASE_URL . '/flight-services/dashboard-aerolinea/clic',      'icono' => null, 'color' => '#D9142F', 'logo' => 'logo_clic.png'],
    'satena'  => ['texto' => 'SATENA',        'url' => BASE_URL . '/flight-services/dashboard-aerolinea/satena',    'icono' => null, 'color' => '#2F5597', 'logo' => 'logo_satena.png'],
];
?>
<style>
.dash-tabs { display:flex; flex-wrap:wrap; gap:8px; padding:8px; margin-bottom:16px;
    background:#fff; border:1px solid #e1e0d9; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.dash-tab { --tab-color:#1B4F8A; display:flex; align-items:center; gap:10px; padding:8px 18px; min-height:48px;
    border-radius:8px; border-bottom:3px solid transparent; text-decoration:none; color:#4a4a45;
    font-weight:700; font-size:14px; letter-spacing:.06em; text-transform:uppercase;
    transition:background .15s, color .15s, transform .15s; }
.dash-tab:hover { background:rgba(0,0,0,.04); color:var(--tab-color); transform:translateY(-1px); }
.dash-tab.is-active { background:color-mix(in srgb, var(--tab-color) 10%, #fff); color:var(--tab-color); border-bottom-color:var(--tab-color); }
.dash-tab i { font-size:18px; }
.dash-tab-logo { height:30px; width:72px; display:flex; align-items:center; justify-content:center; border-radius:6px; padding:3px 6px; background:#fff; }
.dash-tab-logo img { max-height:100%; max-width:100%; object-fit:contain; }
.dash-tab-logo.is-avianca { background:linear-gradient(90deg,#e8001c 0%,#d4004a 55%,#b0007f 100%); }
</style>
<nav class="dash-tabs" aria-label="Paneles del Panel Analítico">
    <?php foreach ($navItems as $clave => $it): $activo = ($navActivo ?? 'general') === $clave; ?>
        <a class="dash-tab <?= $activo ? 'is-active' : '' ?>" href="<?= $it['url'] ?>"
           style="--tab-color:<?= $it['color'] ?>" <?= $activo ? 'aria-current="page"' : '' ?>>
            <?php if ($it['logo'] && is_file(ROOT_PATH . '/img/' . $it['logo'])): ?>
                <span class="dash-tab-logo <?= $clave === 'avianca' ? 'is-avianca' : '' ?>">
                    <img src="<?= BASE_URL ?>/img/<?= $it['logo'] ?>" alt="">
                </span>
            <?php else: ?>
                <i class="bi <?= $it['icono'] ?: 'bi-airplane-fill' ?>"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($it['texto']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
