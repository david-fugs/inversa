<?php
/**
 * ProveedoresController - CRUD de proveedores (módulo de Pagos a Proveedores)
 */

class ProveedoresController extends Controller {

    private Proveedor    $proveedorModel;
    private Banco        $bancoModel;
    private TipoProducto $tipoProductoModel;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->proveedorModel    = new Proveedor();
        $this->bancoModel        = new Banco();
        $this->tipoProductoModel = new TipoProducto();
    }

    public function index(): void {
        $proveedores = $this->proveedorModel->getAll();
        $this->view('proveedores/index', [
            'pageTitle'   => 'Proveedores',
            'breadcrumbs' => ['Proveedores' => null],
            'proveedores' => $proveedores,
        ], 'pagos');
    }

    public function createForm(): void {
        $this->view('proveedores/create', [
            'pageTitle'     => 'Nuevo Proveedor',
            'breadcrumbs'   => ['Proveedores' => BASE_URL . '/proveedores', 'Nuevo' => null],
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => [],
            'old'           => [],
        ], 'pagos');
    }

    public function store(): void {
        $data = $this->readFormData();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $this->view('proveedores/create', [
                'pageTitle'     => 'Nuevo Proveedor',
                'breadcrumbs'   => ['Proveedores' => BASE_URL . '/proveedores', 'Nuevo' => null],
                'bancos'        => $this->bancoModel->getAll(),
                'tiposProducto' => $this->tipoProductoModel->getAll(),
                'errors'        => $errors,
                'old'           => $data,
            ], 'pagos');
            return;
        }

        $this->proveedorModel->create($data);
        $this->redirectWith('proveedores', 'success', 'Proveedor creado correctamente.');
    }

    public function editForm(string $id): void {
        $proveedor = $this->proveedorModel->findById((int)$id);
        if (!$proveedor) {
            $this->redirectWith('proveedores', 'error', 'Proveedor no encontrado.');
            return;
        }
        $this->view('proveedores/edit', [
            'pageTitle'     => 'Editar Proveedor',
            'breadcrumbs'   => ['Proveedores' => BASE_URL . '/proveedores', 'Editar' => null],
            'proveedor'     => $proveedor,
            'bancos'        => $this->bancoModel->getAll(),
            'tiposProducto' => $this->tipoProductoModel->getAll(),
            'errors'        => [],
        ], 'pagos');
    }

    public function update(string $id): void {
        $proveedorId = (int)$id;
        $proveedor   = $this->proveedorModel->findById($proveedorId);
        if (!$proveedor) {
            $this->redirectWith('proveedores', 'error', 'Proveedor no encontrado.');
            return;
        }

        $data   = $this->readFormData();
        $errors = $this->validate($data, $proveedorId);

        if (!empty($errors)) {
            $this->view('proveedores/edit', [
                'pageTitle'     => 'Editar Proveedor',
                'breadcrumbs'   => ['Proveedores' => BASE_URL . '/proveedores', 'Editar' => null],
                'proveedor'     => array_merge($proveedor, $data),
                'bancos'        => $this->bancoModel->getAll(),
                'tiposProducto' => $this->tipoProductoModel->getAll(),
                'errors'        => $errors,
            ], 'pagos');
            return;
        }

        $this->proveedorModel->update($proveedorId, $data);
        $this->redirectWith('proveedores', 'success', 'Proveedor actualizado correctamente.');
    }

    public function delete(string $id): void {
        $proveedorId = (int)$id;

        if ($this->proveedorModel->hasPagos($proveedorId)) {
            $this->redirectWith('proveedores', 'error', 'No se puede eliminar: el proveedor tiene pagos registrados.');
            return;
        }

        if ($this->proveedorModel->delete($proveedorId)) {
            $this->redirectWith('proveedores', 'success', 'Proveedor eliminado correctamente.');
        } else {
            $this->redirectWith('proveedores', 'error', 'No se pudo eliminar el proveedor.');
        }
    }

    /**
     * Endpoint JSON: datos del proveedor para precargar banco / tipo de
     * producto / número de producto en el formulario de pagos (mismo
     * patrón que AircraftTypesController::byAirline).
     */
    public function infoJson(string $id): void {
        $proveedor = $this->proveedorModel->findById((int)$id);
        if (!$proveedor) {
            $this->json(['error' => 'Proveedor no encontrado.'], 404);
            return;
        }
        $this->json([
            'banco_id'              => (int)$proveedor['banco_id'],
            'banco_nombre'          => $proveedor['banco_nombre'],
            'tipo_producto_id'      => (int)$proveedor['tipo_producto_id'],
            'tipo_producto_nombre'  => $proveedor['tipo_producto_nombre'],
            'numero_producto'       => $proveedor['numero_producto'],
        ]);
    }

    private function readFormData(): array {
        return [
            'numero_identificacion' => $this->input('numero_identificacion', ''),
            'nombre'                => $this->input('nombre', ''),
            'banco_id'              => (int)$this->input('banco_id', 0),
            'tipo_producto_id'      => (int)$this->input('tipo_producto_id', 0),
            'numero_producto'       => $this->input('numero_producto', ''),
        ];
    }

    private function validate(array $data, int $excludeId = 0): array {
        $errors = [];

        if (empty($data['numero_identificacion'])) {
            $errors['numero_identificacion'] = 'El número de identificación es obligatorio.';
        } elseif ($this->proveedorModel->numeroIdentificacionExists($data['numero_identificacion'], $excludeId)) {
            $errors['numero_identificacion'] = 'Ya existe un proveedor con este número de identificación.';
        }

        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre del proveedor es obligatorio.';
        }

        if (empty($data['banco_id']) || !$this->bancoModel->findById($data['banco_id'])) {
            $errors['banco_id'] = 'Seleccione un banco válido.';
        }

        if (empty($data['tipo_producto_id']) || !$this->tipoProductoModel->findById($data['tipo_producto_id'])) {
            $errors['tipo_producto_id'] = 'Seleccione un tipo de producto válido.';
        }

        if (empty($data['numero_producto'])) {
            $errors['numero_producto'] = 'El número de producto o servicio es obligatorio.';
        }

        return $errors;
    }
}
