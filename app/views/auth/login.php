<div class="login-page">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo_completo.png" alt="<?= APP_NAME ?>" class="login-logo-img">
            <p>Plataforma de Operaciones Aeroportuarias</p>
        </div>

        <!-- Mensaje de error -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" style="font-size:13px;">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="POST" action="<?= BASE_URL ?>/auth/login" autocomplete="off" novalidate>

            <div class="mb-3">
                <label for="usuario" class="form-label">
                    <i class="bi bi-person me-1"></i> Usuario
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="usuario"
                    name="usuario"
                    value="<?= isset($usuario) ? htmlspecialchars($usuario) : '' ?>"
                    placeholder="Ingrese su usuario"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i> Contraseña
                </label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Ingrese su contraseña"
                        required
                    >
                    <button
                        type="button"
                        class="btn btn-light"
                        style="border:1.5px solid #E2E8F0; border-left:none;"
                        onclick="togglePassword()"
                        tabindex="-1"
                    >
                        <i class="bi bi-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right"></i>
                Iniciar Sesión
            </button>
        </form>

        <p class="text-center mt-4 mb-0" style="font-size:12px; color:#9CA3AF;">
            <?= APP_NAME ?> &copy; <?= date('Y') ?>
        </p>
    </div>
</div>

<script>
function togglePassword() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
