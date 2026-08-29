<?php
/**
 * CodigoDemorasController - CRUD del catálogo de Código Demoras
 *
 * Igual que TarifasGpuController, el listado usa un modal para crear
 * y editar (no hay páginas separadas de create/edit). Si el guardado
 * falla la validación, se vuelve a renderizar el listado con el modal
 * abierto y los errores/valores anteriores.
 */

class CodigoDemorasController extends Controller {

    private CodigoDemora $model;

    public function __construct() {
        parent::__construct();
        Session::requireAuth();
        $this->model = new CodigoDemora();
    }

    public function index(): void {
        $this->renderIndex();
    }

    public function store(): void {
        $data   = $this->collectInput();
        $errors = $this->validate($data);

        if (!empty($errors)) {
            $this->renderIndex($errors, $data, 'create');
            return;
        }

        $this->model->create($data);
        $this->redirectWith('codigo-demoras', 'success', 'Código de demora creado correctamente.');
    }

    public function update(string $id): void {
        $codigoDemoraId = (int)$id;
        $registro = $this->model->findById($codigoDemoraId);
        if (!$registro) {
            $this->redirectWith('codigo-demoras', 'error', 'Código de demora no encontrado.');
            return;
        }

        $data   = $this->collectInput();
        $errors = $this->validate($data, $codigoDemoraId);

        if (!empty($errors)) {
            $data['id'] = $codigoDemoraId;
            $this->renderIndex($errors, $data, 'edit');
            return;
        }

        $this->model->update($codigoDemoraId, $data);
        $this->redirectWith('codigo-demoras', 'success', 'Código de demora actualizado correctamente.');
    }

    public function delete(string $id): void {
        $codigoDemoraId = (int)$id;
        if ($this->model->hasFlightServices($codigoDemoraId)) {
            $this->redirectWith('codigo-demoras', 'error', 'No se puede eliminar: el código de demora está siendo usado en servicios de vuelo.');
            return;
        }

        if ($this->model->delete($codigoDemoraId)) {
            $this->redirectWith('codigo-demoras', 'success', 'Código de demora eliminado correctamente.');
        } else {
            $this->redirectWith('codigo-demoras', 'error', 'No se pudo eliminar el código de demora.');
        }
    }

    private function collectInput(): array {
        return [
            'codigo'      => strtoupper($this->input('codigo', '')),
            'descripcion' => $this->input('descripcion', ''),
        ];
    }

    private function validate(array $data, int $excludeId = 0): array {
        $errors = [];

        if ($data['codigo'] === '') {
            $errors['codigo'] = 'El código de demora es obligatorio.';
        } elseif (!preg_match('/^[A-Z0-9]+$/', $data['codigo'])) {
            $errors['codigo'] = 'El código solo puede contener letras y números, sin espacios.';
        } elseif (strlen($data['codigo']) > 20) {
            $errors['codigo'] = 'El código no puede tener más de 20 caracteres.';
        } elseif ($this->model->codigoExists($data['codigo'], $excludeId)) {
            $errors['codigo'] = 'Ya existe un código de demora con ese valor.';
        }

        if ($data['descripcion'] === '') {
            $errors['descripcion'] = 'La descripción es obligatoria.';
        } elseif (strlen($data['descripcion']) > 255) {
            $errors['descripcion'] = 'La descripción no puede tener más de 255 caracteres.';
        }

        return $errors;
    }

    private function renderIndex(array $errors = [], array $old = [], ?string $openModal = null): void {
        $codigoDemoras = $this->model->getAll();
        $this->view('codigo_demoras/index', [
            'pageTitle'     => 'Código Demoras',
            'breadcrumbs'   => ['Código Demoras' => null],
            'codigoDemoras' => $codigoDemoras,
            'errors'        => $errors,
            'old'           => $old,
            'openModal'     => $openModal,
        ]);
    }
}
