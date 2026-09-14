<div class="page-actions">
    <a href="<?= BASE_URL ?>/pagos" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card" style="max-width:520px;">
    <div class="card-header">
        <h5><i class="bi bi-plus-circle-fill"></i> Nuevo Lote de Pago</h5>
    </div>
    <div class="card-body">
        <p class="text-muted" style="font-size:13px;">
            Escriba el consecutivo del lote. Si ya existe uno abierto con ese
            consecutivo, se continuará agregando pagos a él en vez de crear uno nuevo.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/pagos/lotes/verificar" novalidate>

            <div class="mb-3">
                <label for="consecutivo" class="form-label">
                    Consecutivo <span class="required-mark">*</span>
                </label>
                <input type="text" class="form-control <?= isset($errors['consecutivo']) ? 'is-invalid' : '' ?>"
                    id="consecutivo" name="consecutivo"
                    value="<?= htmlspecialchars($old['consecutivo'] ?? '') ?>"
                    placeholder="Ej: LOTE-2026-001"
                    autofocus>
                <?php if (isset($errors['consecutivo'])): ?>
                    <div class="invalid-feedback"><?= $errors['consecutivo'] ?></div>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-arrow-right-circle"></i> Continuar
                </button>
                <a href="<?= BASE_URL ?>/pagos" class="btn btn-light">Cancelar</a>
            </div>
        </form>
    </div>
</div>
