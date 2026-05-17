<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Validator;
use App\Models\Label;
use App\Models\Note;

class LabelController extends Controller
{
    private function sidebarLabels(): array
    {
        return (new Label())->getAllByUser(Auth::id());
    }

    // ─── Label management page ────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireLogin();

        $errors = $_SESSION['_errors'] ?? [];
        $old    = $_SESSION['_old']    ?? [];
        unset($_SESSION['_errors'], $_SESSION['_old']);

        $labels = $this->sidebarLabels();

        $this->view('labels.manage', [
            'pageTitle'     => 'Labels',
            'labels'        => $labels,
            'errors'        => $errors,
            'old'           => $old,
            'sidebarLabels' => $labels,
        ]);
    }

    // ─── Create label ─────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $name  = trim($this->post('name', ''));
        $color = trim($this->post('color', '#6c757d'));

        $v = Validator::make($_POST, [
            'name' => 'required|max:50',
        ]);

        if ($v->fails()) {
            $_SESSION['_errors'] = $v->errors();
            $_SESSION['_old']    = compact('name', 'color');
            $this->redirect('labels');
        }

        $labelModel = new Label();

        if ($labelModel->nameExists(Auth::id(), $name)) {
            $_SESSION['_errors'] = ['name' => ['A label with this name already exists.']];
            $_SESSION['_old']    = compact('name', 'color');
            $this->redirect('labels');
        }

        // Sanitize color: must be a valid hex
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#6c757d';
        }

        $labelModel->create(Auth::id(), $name, $color);
        $this->flash('success', 'Label "' . htmlspecialchars($name) . '" created.');
        $this->redirect('labels');
    }

    // ─── Update label ─────────────────────────────────────────────────────────

    public function update(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $labelId    = (int) $id;
        $labelModel = new Label();
        $label      = $labelModel->findById($labelId, Auth::id());

        if (!$label) {
            $this->flash('error', 'Label not found.');
            $this->redirect('labels');
        }

        $name  = trim($this->post('name', ''));
        $color = trim($this->post('color', '#6c757d'));

        $v = Validator::make(['name' => $name], ['name' => 'required|max:50']);
        if ($v->fails()) {
            $this->flash('error', $v->errors()['name'][0] ?? 'Invalid name.');
            $this->redirect('labels');
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#6c757d';
        }

        if ($labelModel->nameExists(Auth::id(), $name, $labelId)) {
            $this->flash('error', 'A label with this name already exists.');
            $this->redirect('labels');
        }

        $labelModel->update($labelId, Auth::id(), $name, $color);
        $this->flash('success', 'Label updated.');
        $this->redirect('labels');
    }

    // ─── Delete label ─────────────────────────────────────────────────────────

    public function delete(string $id): void
    {
        Auth::requireLogin();
        CSRF::validateRequest();

        $labelId    = (int) $id;
        $labelModel = new Label();

        if (!$labelModel->findById($labelId, Auth::id())) {
            $this->flash('error', 'Label not found.');
            $this->redirect('labels');
        }

        $labelModel->delete($labelId, Auth::id());
        $this->flash('success', 'Label deleted.');
        $this->redirect('labels');
    }

    // ─── Notes for a label ────────────────────────────────────────────────────

    public function notes(string $id): void
    {
        Auth::requireLogin();

        $labelId    = (int) $id;
        $labelModel = new Label();
        $label      = $labelModel->findById($labelId, Auth::id());

        if (!$label) {
            $this->flash('error', 'Label not found.');
            $this->redirect('notes');
        }

        $noteModel = new Note();
        $rawNotes  = $noteModel->getByLabel(Auth::id(), $labelId);

        // Attach labels to each note
        $ids = array_map(fn($n) => (int) $n['id'], $rawNotes);
        $map = $noteModel->getLabelsByNoteIds($ids);
        foreach ($rawNotes as &$n) {
            $n['labels'] = $map[(int) $n['id']] ?? [];
        }
        unset($n);

        $this->view('notes.index', [
            'pageTitle'     => '#' . htmlspecialchars($label['name']),
            'notes'         => $rawNotes,
            'filter'        => '',
            'labelId'       => $labelId,
            'labelName'     => $label['name'],
            'sidebarLabels' => $this->sidebarLabels(),
        ]);
    }
}
