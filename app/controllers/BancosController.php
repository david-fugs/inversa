<?php
/**
 * BancosController - CRUD de bancos (módulo de Pagos a Proveedores)
 */

class BancosController extends Controller {

    private Banco $bancoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->bancoModel = new Banco();
    }

    /** Solo Admin Pagos puede crear/editar/eliminar bancos */
    private function requireAdminPagos(): void {
        if (Session::get('user_rol') !== 'Admin Pagos') {
            $this->redirectWith('pagos', 'error', 'Acceso denegado.');
            exit;
        }
    }

    public function index(): void {
        $bancos = $this->bancoModel->getAll();
        $this->view('bancos/index', [
            'pageTitle'   => 'Bancos',
            'breadcrumbs' => ['Bancos' => null],
            'bancos'      => $bancos,
        ], 'pagos');
    }

    public function createForm(): void {
        $this->requireAdminPagos();
        $this->view('bancos/create', [
            'pageTitle'   => 'Nuevo Banco',
            'breadcrumbs' => ['Bancos' => BASE_URL . '/bancos', 'Nuevo' => null],
            'errors'      => [],
            'old'         => [],
        ], 'pagos');
    }

    public function store(): void {
        $this->requireAdminPagos();

        $codigo = $this->input('codigo', '');
        $nombre = $this->input('nombre', '');
        $errors = [];

        if (empty($codigo)) {
            $errors['codigo'] = 'El código del banco es obligatorio.';
        } elseif ($this->bancoModel->codigoExists($codigo)) {
            $errors['codigo'] = 'Ya existe un banco con este código.';
        }

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre del banco es obligatorio.';
        } elseif ($this->bancoModel->nombreExists($nombre)) {
            $errors['nombre'] = 'Ya existe un banco con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('bancos/create', [
                'pageTitle'   => 'Nuevo Banco',
                'breadcrumbs' => ['Bancos' => BASE_URL . '/bancos', 'Nuevo' => null],
                'errors'      => $errors,
                'old'         => ['codigo' => $codigo, 'nombre' => $nombre],
            ], 'pagos');
            return;
        }

        $this->bancoModel->create(['codigo' => $codigo, 'nombre' => $nombre]);
        $this->redirectWith('bancos', 'success', 'Banco creado correctamente.');
    }

    public function editForm(string $id): void {
        $this->requireAdminPagos();
        $banco = $this->bancoModel->findById((int)$id);
        if (!$banco) {
            $this->redirectWith('bancos', 'error', 'Banco no encontrado.');
            return;
        }
        $this->view('bancos/edit', [
            'pageTitle'   => 'Editar Banco',
            'breadcrumbs' => ['Bancos' => BASE_URL . '/bancos', 'Editar' => null],
            'banco'       => $banco,
            'errors'      => [],
        ], 'pagos');
    }

    public function update(string $id): void {
        $this->requireAdminPagos();

        $bancoId = (int)$id;
        $banco   = $this->bancoModel->findById($bancoId);
        if (!$banco) {
            $this->redirectWith('bancos', 'error', 'Banco no encontrado.');
            return;
        }

        $codigo = $this->input('codigo', '');
        $nombre = $this->input('nombre', '');
        $errors = [];

        if (empty($codigo)) {
            $errors['codigo'] = 'El código del banco es obligatorio.';
        } elseif ($this->bancoModel->codigoExists($codigo, $bancoId)) {
            $errors['codigo'] = 'Ya existe un banco con este código.';
        }

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre del banco es obligatorio.';
        } elseif ($this->bancoModel->nombreExists($nombre, $bancoId)) {
            $errors['nombre'] = 'Ya existe un banco con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('bancos/edit', [
                'pageTitle'   => 'Editar Banco',
                'breadcrumbs' => ['Bancos' => BASE_URL . '/bancos', 'Editar' => null],
                'banco'       => array_merge($banco, ['codigo' => $codigo, 'nombre' => $nombre]),
                'errors'      => $errors,
            ], 'pagos');
            return;
        }

        $this->bancoModel->update($bancoId, ['codigo' => $codigo, 'nombre' => $nombre]);
        $this->redirectWith('bancos', 'success', 'Banco actualizado correctamente.');
    }

    public function delete(string $id): void {
        $this->requireAdminPagos();

        $bancoId = (int)$id;

        if ($this->bancoModel->hasProveedores($bancoId)) {
            $this->redirectWith('bancos', 'error', 'No se puede eliminar: el banco tiene proveedores asociados.');
            return;
        }

        if ($this->bancoModel->delete($bancoId)) {
            $this->redirectWith('bancos', 'success', 'Banco eliminado correctamente.');
        } else {
            $this->redirectWith('bancos', 'error', 'No se pudo eliminar el banco.');
        }
    }
}
