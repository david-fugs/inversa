<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-shield-lock-fill"></i> Cambiar Contraseña</h5>
            </div>
            <div class="card-body">

                <form method="POST" action="<?= BASE_URL ?>/auth/change-password" novalidate>

                    <div class="mb-4">
                        <label for="password_actual" class="form-label">
                            Contraseña Actual <span class="required-mark">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control <?= isset($errors['password_actual']) ? 'is-invalid' : '' ?>"
                                   id="password_actual" name="password_actual"
                                   placeholder="Ingrese su contraseña actual" autocomplete="current-password">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="password_actual" tabindex="-1">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <?php if (isset($errors['password_actual'])): ?>
                                <div class="invalid-feedback"><?= $errors['password_actual'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label for="password_nueva" class="form-label">
                            Nueva Contraseña <span class="required-mark">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control <?= isset($errors['password_nueva']) ? 'is-invalid' : '' ?>"
                                   id="password_nueva" name="password_nueva"
                                   placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="password_nueva" tabindex="-1">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <?php if (isset($errors['password_nueva'])): ?>
                                <div class="invalid-feedback"><?= $errors['password_nueva'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div id="passStrength" class="mt-1" style="height:4px;border-radius:2px;transition:all .3s;"></div>
                        <small id="passStrengthText" class="text-muted"></small>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmar" class="form-label">
                            Confirmar Nueva Contraseña <span class="required-mark">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control <?= isset($errors['password_confirmar']) ? 'is-invalid' : '' ?>"
                                   id="password_confirmar" name="password_confirmar"
                                   placeholder="Repita la nueva contraseña" autocomplete="new-password">
                            <button type="button" class="btn btn-outline-secondary toggle-pass" data-target="password_confirmar" tabindex="-1">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <?php if (isset($errors['password_confirmar'])): ?>
                                <div class="invalid-feedback"><?= $errors['password_confirmar'] ?></div>
                            <?php endif; ?>
                        </div>
                        <small id="matchMsg" class="mt-1 d-block"></small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill"></i> Actualizar Contraseña
                        </button>
                        <a href="<?= BASE_URL ?>/flight-services" class="btn btn-light">Cancelar</a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar contraseña
document.querySelectorAll('.toggle-pass').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
    });
});

// Indicador de fortaleza
document.getElementById('password_nueva').addEventListener('input', function() {
    const val = this.value;
    const bar = document.getElementById('passStrength');
    const txt = document.getElementById('passStrengthText');
    let score = 0;
    if (val.length >= 8)  score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { color: '#E74C3C', label: 'Muy débil' },
        { color: '#E8651A', label: 'Débil' },
        { color: '#F1C40F', label: 'Regular' },
        { color: '#27AE60', label: 'Fuerte' },
        { color: '#1B4F8A', label: 'Muy fuerte' },
    ];
    const lvl = val.length === 0 ? null : levels[score];
    bar.style.width    = val.length === 0 ? '0' : ((score + 1) * 20) + '%';
    bar.style.background = lvl ? lvl.color : '';
    txt.textContent    = lvl ? lvl.label : '';
    txt.style.color    = lvl ? lvl.color : '';
});

// Validación coincidencia en tiempo real
document.getElementById('password_confirmar').addEventListener('input', function() {
    const nueva = document.getElementById('password_nueva').value;
    const msg   = document.getElementById('matchMsg');
    if (this.value === '') {
        msg.textContent = '';
    } else if (this.value === nueva) {
        msg.textContent = '✓ Las contraseñas coinciden';
        msg.style.color = '#27AE60';
    } else {
        msg.textContent = '✗ Las contraseñas no coinciden';
        msg.style.color = '#E74C3C';
    }
});
</script>
