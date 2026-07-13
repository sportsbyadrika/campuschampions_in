<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight rule-based validator.
 *
 * Rules: required, email, min:N, max:N, numeric, integer, in:a,b,c,
 *        date, confirmed, unique:table,column[,ignoreId], exists:table,column,
 *        regex:/.../, boolean.
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $labels;

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data   = $data;
        $this->rules  = $rules;
        $this->labels = $labels;
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        $this->run();
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** First error message per field, flattened. */
    public function firstErrors(): array
    {
        $out = [];
        foreach ($this->errors as $field => $messages) {
            $out[$field] = $messages[0];
        }
        return $out;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucwords(str_replace('_', ' ', $field));
    }

    private function run(): void
    {
        $this->errors = [];
        foreach ($this->rules as $field => $ruleset) {
            $rules = is_array($ruleset) ? $ruleset : explode('|', $ruleset);
            $value = $this->data[$field] ?? null;

            $isRequired = in_array('required', $rules, true);
            $isEmpty    = ($value === null || $value === '');

            // Skip optional empty fields
            if (!$isRequired && $isEmpty) {
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $label = $this->label($field);

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->addError($field, "$label is required.");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "$label must be a valid email address.");
                }
                break;

            case 'min':
                if (is_numeric($value)) {
                    if ($value < (float) $param) {
                        $this->addError($field, "$label must be at least $param.");
                    }
                } elseif (mb_strlen((string) $value) < (int) $param) {
                    $this->addError($field, "$label must be at least $param characters.");
                }
                break;

            case 'max':
                if (is_numeric($value) && !is_string($value)) {
                    if ($value > (float) $param) {
                        $this->addError($field, "$label must not exceed $param.");
                    }
                } elseif (mb_strlen((string) $value) > (int) $param) {
                    $this->addError($field, "$label must not exceed $param characters.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "$label must be a number.");
                }
                break;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "$label must be an integer.");
                }
                break;

            case 'boolean':
                if (!in_array((string) $value, ['0', '1', 'true', 'false'], true)) {
                    $this->addError($field, "$label must be true or false.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array((string) $value, $allowed, true)) {
                    $this->addError($field, "$label is invalid.");
                }
                break;

            case 'date':
                $d = date_create((string) $value);
                if (!$d) {
                    $this->addError($field, "$label must be a valid date.");
                }
                break;

            case 'regex':
                if (!preg_match((string) $param, (string) $value)) {
                    $this->addError($field, "$label format is invalid.");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (($this->data[$confirmField] ?? null) !== $value) {
                    $this->addError($field, "$label confirmation does not match.");
                }
                break;

            case 'unique':
                // unique:table,column[,ignoreId]
                [$table, $column, $ignoreId] = array_pad(explode(',', (string) $param), 3, null);
                $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
                $bind = [$value];
                if ($ignoreId !== null && $ignoreId !== '') {
                    $sql .= " AND id <> ?";
                    $bind[] = $ignoreId;
                }
                if ((int) Database::instance()->scalar($sql, $bind) > 0) {
                    $this->addError($field, "$label is already taken.");
                }
                break;

            case 'exists':
                // exists:table,column
                [$table, $column] = explode(',', (string) $param);
                $count = (int) Database::instance()->scalar(
                    "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?",
                    [$value]
                );
                if ($count === 0) {
                    $this->addError($field, "Selected $label is invalid.");
                }
                break;
        }
    }
}
