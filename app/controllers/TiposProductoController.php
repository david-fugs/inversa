<?php
/**
 * TiposProductoController - CRUD de tipos de producto (módulo de Pagos a Proveedores)
 */

class TiposProductoController extends Controller {

    private TipoProducto $tipoProductoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->tipoProductoModel = new TipoProducto();
    }

    /** Solo Admin Pagos puede crear/editar/eliminar tipos de producto */
    private function requireAdminPagos(): void {
        if (Session::get('user_rol') !== 'Admin Pagos') {
            $this->redirectWith('pagos', 'error', 'Acceso denegado.');
            exit;
        }
    }

    public function index(): void {
        $tiposProducto = $this->tipoProductoModel->getAll();
        $this->view('tipos_producto/index', [
            'pageTitle'     => 'Tipos de Producto',
            'breadcrumbs'   => ['Tipos de Producto' => null],
            'tiposProducto' => $tiposProducto,
        ], 'pagos');
    }

    public function createForm(): void {
        $this->requireAdminPagos();
        $this->view('tipos_producto/create', [
            'pageTitle'   => 'Nuevo Tipo de Producto',
            'breadcrumbs' => ['Tipos de Producto' => BASE_URL . '/tipos-producto', 'Nuevo' => null],
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
            $errors['codigo'] = 'El código es obligatorio.';
        } elseif ($this->tipoProductoModel->codigoExists($codigo)) {
            $errors['codigo'] = 'Ya existe un tipo de producto con este código.';
        }

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif ($this->tipoProductoModel->nombreExists($nombre)) {
            $errors['nombre'] = 'Ya existe un tipo de producto con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('tipos_producto/create', [
                'pageTitle'   => 'Nuevo Tipo de Producto',
                'breadcrumbs' => ['Tipos de Producto' => BASE_URL . '/tipos-producto', 'Nuevo' => null],
                'errors'      => $errors,
                'old'         => ['codigo' => $codigo, 'nombre' => $nombre],
            ], 'pagos');
            return;
        }

        $this->tipoProductoModel->create(['codigo' => $codigo, 'nombre' => $nombre]);
        $this->redirectWith('tipos-producto', 'success', 'Tipo de producto creado correctamente.');
    }

    public function editForm(string $id): void {
        $this->requireAdminPagos();
        $tipoProducto = $this->tipoProductoModel->findById((int)$id);
        if (!$tipoProducto) {
            $this->redirectWith('tipos-producto', 'error', 'Tipo de producto no encontrado.');
            return;
        }
        $this->view('tipos_producto/edit', [
            'pageTitle'    => 'Editar Tipo de Producto',
            'breadcrumbs'  => ['Tipos de Producto' => BASE_URL . '/tipos-producto', 'Editar' => null],
            'tipoProducto' => $tipoProducto,
            'errors'       => [],
        ], 'pagos');
    }

    public function update(string $id): void {
        $this->requireAdminPagos();

        $tipoProductoId = (int)$id;
        $tipoProducto   = $this->tipoProductoModel->findById($tipoProductoId);
        if (!$tipoProducto) {
            $this->redirectWith('tipos-producto', 'error', 'Tipo de producto no encontrado.');
            return;
        }

        $codigo = $this->input('codigo', '');
        $nombre = $this->input('nombre', '');
        $errors = [];

        if (empty($codigo)) {
            $errors['codigo'] = 'El código es obligatorio.';
        } elseif ($this->tipoProductoModel->codigoExists($codigo, $tipoProductoId)) {
            $errors['codigo'] = 'Ya existe un tipo de producto con este código.';
        }

        if (empty($nombre)) {
            $errors['nombre'] = 'El nombre es obligatorio.';
        } elseif ($this->tipoProductoModel->nombreExists($nombre, $tipoProductoId)) {
            $errors['nombre'] = 'Ya existe un tipo de producto con este nombre.';
        }

        if (!empty($errors)) {
            $this->view('tipos_producto/edit', [
                'pageTitle'    => 'Editar Tipo de Producto',
                'breadcrumbs'  => ['Tipos de Producto' => BASE_URL . '/tipos-producto', 'Editar' => null],
                'tipoProducto' => array_merge($tipoProducto, ['codigo' => $codigo, 'nombre' => $nombre]),
                'errors'       => $errors,
            ], 'pagos');
            return;
        }

        $this->tipoProductoModel->update($tipoProductoId, ['codigo' => $codigo, 'nombre' => $nombre]);
        $this->redirectWith('tipos-producto', 'success', 'Tipo de producto actualizado correctamente.');
    }

    public function delete(string $id): void {
        $this->requireAdminPagos();

        $tipoProductoId = (int)$id;

        if ($this->tipoProductoModel->hasProveedores($tipoProductoId)) {
            $this->redirectWith('tipos-producto', 'error', 'No se puede eliminar: el tipo de producto tiene proveedores asociados.');
            return;
        }

        if ($this->tipoProductoModel->delete($tipoProductoId)) {
            $this->redirectWith('tipos-producto', 'success', 'Tipo de producto eliminado correctamente.');
        } else {
            $this->redirectWith('tipos-producto', 'error', 'No se pudo eliminar el tipo de producto.');
        }
    }
}
