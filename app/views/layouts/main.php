<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- App CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">
</head>
<body>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-logo">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="<?= APP_NAME ?>" class="brand-icon-img">
            <div class="brand-text">
                <h5>Operaciones</h5>

                <span>Aeroportuarias</span>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <nav class="sidebar-nav">
        <p class="nav-section-title">Principal</p>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/flight-services" class="nav-link">
                <i class="bi bi-clipboard2-pulse-fill"></i>
                <span>Ground Handling</span>
            </a>
        </div>

        <?php if (Session::get('user_rol') === 'Administrador'): ?>
        <p class="nav-section-title">Catálogos</p>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/airlines" class="nav-link">
                <i class="bi bi-airplane-fill"></i>
                <span>Aerolíneas</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/aircraft-types" class="nav-link">
                <i class="bi bi-airplane"></i>
                <span>Tipos de Avión</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/tarifas-cobros" class="nav-link">
                <i class="bi bi-cash-coin"></i>
                <span>Tarifas / Cobros</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/bases" class="nav-link">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Bases</span>
            </a>
        </div>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/base-destinos" class="nav-link">
                <i class="bi bi-signpost-split-fill"></i>
                <span>Bases Destino</span>
            </a>
        </div>

        <p class="nav-section-title">Administración</p>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/users" class="nav-link">
                <i class="bi bi-people-fill"></i>
                <span>Usuarios</span>
            </a>
        </div>
        <?php endif; ?>

        <p class="nav-section-title">Mi Cuenta</p>

        <div class="nav-item">
            <a href="<?= BASE_URL ?>/auth/change-password" class="nav-link">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Cambiar Contraseña</span>
            </a>
        </div>

    </nav>

    <!-- Footer usuario -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="user-details">
                <p><?= htmlspecialchars(Session::get('user_nombre', 'Usuario')) ?></p>
                <span><?= htmlspecialchars(Session::get('user_rol', '')) ?></span>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout" class="btn-logout"
           data-confirm="¿Desea cerrar sesión?">
            <i class="bi bi-box-arrow-left"></i>
            <span>Cerrar sesión</span>
        </a>
    </div>
</aside>

<!-- ═══ CONTENIDO PRINCIPAL ══════════════════════════════ -->
<div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light btn-icon topbar-toggle" id="sidebarToggle">
                <i class="bi bi-list" style="font-size:20px"></i>
            </button>
            <div class="topbar-title">
                <h4><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME ?></h4>
                <?php if (isset($breadcrumbs) && count($breadcrumbs)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>/flight-services">Inicio</a>
                        </li>
                        <?php foreach ($breadcrumbs as $label => $url): ?>
                            <?php if ($url): ?>
                                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active"><?= htmlspecialchars($label) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenido de la página -->
    <main class="page-content">

        <!-- Mensajes flash -->
        <?php $flashSuccess = Session::getFlash('success'); ?>
        <?php $flashError   = Session::getFlash('error'); ?>
        <?php $flashWarning = Session::getFlash('warning'); ?>

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success" data-auto-dismiss="5000">
                <i class="bi bi-check-circle-fill"></i>
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger" data-auto-dismiss="8000">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <?php if ($flashWarning): ?>
            <div class="alert alert-warning" data-auto-dismiss="6000">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($flashWarning) ?>
            </div>
        <?php endif; ?>

        <!-- Vista inyectada -->
        <?= $content ?>

    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= BASE_URL ?>/public/js/app.js"></script>
<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>
</html>
