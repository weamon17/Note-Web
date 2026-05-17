<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data   = [];

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    // ─── Factory ─────────────────────────────────────────────────────────────

    public static function make(array $data, array $rules): static
    {
        $v = new static($data);
        foreach ($rules as $field => $ruleString) {
            $v->applyRules($field, $ruleString);
        }
        return $v;
    }

    // ─── Result ──────────────────────────────────────────────────────────────

    public function fails(): bool  { return !empty($this->errors); }
    public function passes(): bool { return empty($this->errors); }

    public function errors(): array { return $this->errors; }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /** Flat list of all error messages. */
    public function allErrors(): array
    {
        return array_merge(...array_values($this->errors));
    }

    /** Add an external error (e.g. "email already taken" from DB check). */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ─── Internal rule dispatch ───────────────────────────────────────────────

    private function applyRules(string $field, string $ruleString): void
    {
        $value = $this->data[$field] ?? null;
        $rules = explode('|', $ruleString);

        // If 'nullable' and value is empty, skip remaining rules
        if (in_array('nullable', $rules, true) && ($value === null || $value === '')) {
            return;
        }

        foreach ($rules as $rule) {
            if ($rule === 'nullable') {
                continue;
            }
            [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
            $this->applyRule($field, $value, $name, $param);
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->errors[$field][] = "{$label} is required.";
                }
                break;

            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "{$label} must be a valid email address.";
                }
                break;

            case 'min':
                if ($value !== null && mb_strlen((string)$value) < (int)$param) {
                    $this->errors[$field][] = "{$label} must be at least {$param} characters.";
                }
                break;

            case 'max':
                if ($value !== null && mb_strlen((string)$value) > (int)$param) {
                    $this->errors[$field][] = "{$label} must not exceed {$param} characters.";
                }
                break;

            case 'confirmed':
                // Expects field_confirmation key in data
                $confirmKey = $field . '_confirmation';
                if ($value !== ($this->data[$confirmKey] ?? null)) {
                    $this->errors[$field][] = "{$label} confirmation does not match.";
                }
                break;

            case 'same':
                if ($value !== ($this->data[$param] ?? null)) {
                    $paramLabel = ucfirst(str_replace('_', ' ', (string)$param));
                    $this->errors[$field][] = "{$label} must match {$paramLabel}.";
                }
                break;

            case 'in':
                $allowed = explode(',', (string)$param);
                if ($value !== null && !in_array($value, $allowed, true)) {
                    $this->errors[$field][] = "{$label} must be one of: " . implode(', ', $allowed) . '.';
                }
                break;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->errors[$field][] = "{$label} must be a number.";
                }
                break;

            case 'integer':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field][] = "{$label} must be an integer.";
                }
                break;

            case 'url':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field][] = "{$label} must be a valid URL.";
                }
                break;

            // 'unique' and 'exists' are handled in the controller via DB check + addError()
        }
    }
}
