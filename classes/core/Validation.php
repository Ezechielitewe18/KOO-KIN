<?php

declare(strict_types=1);

namespace KooKin\Core;

class Validation
{
    private array $errors = [];
    private array $data = [];

    public function make(array $inputs, array $rules): self
    {
        $this->data = $inputs;
        foreach ($rules as $field => $fieldRules) {
            $label = $fieldRules['label'] ?? $field;
            $value = $inputs[$field] ?? null;
            foreach (explode('|', $fieldRules['rules'] ?? '') as $rule) {
                if ($rule === '') {
                    continue;
                }
                $this->apply($field, $label, $value, $rule);
            }
        }
        return $this;
    }

    private function apply(string $field, string $label, mixed $value, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$rule, $paramsStr] = explode(':', $rule, 2);
            $params = explode(',', $paramsStr);
        }

        switch ($rule) {
            case 'required':
                if ($value === null || trim((string) $value) === '' || $value === []) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » est requis.';
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) < $min) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit contenir au moins ' . $min . ' caractères.';
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) > $max) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit contenir au plus ' . $max . ' caractères.';
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être un nombre.';
                }
                break;

            case 'int':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être un entier.';
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une adresse email valide.';
                }
                break;

            case 'phone':
                if (!preg_match('/^[0-9+ ()-]{7,30}$/', trim((string) $value))) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être un numéro de téléphone valide.';
                }
                break;

            case 'date':
                $format = $params[0] ?? 'Y-m-d';
                $d = \DateTime::createFromFormat($format, (string) $value);
                if (!$d || $d->format($format) !== (string) $value) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une date valide.';
                }
                break;

            case 'time':
                if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', (string) $value)) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être une heure valide (HH:MM).';
                }
                break;

            case 'in':
                if (is_array($value)) {
                    $value = (string) $value[0];
                }
                if (!in_array($value, $params, true)) {
                    $this->errors[$field] = 'La valeur du champ « ' . $label . ' » est invalide.';
                }
                break;

            case 'min_value':
                $min = (float) ($params[0] ?? 0);
                if (is_numeric($value) && (float) $value < $min) {
                    $this->errors[$field] = 'Le champ « ' . $label . ' » doit être supérieur ou égal à ' . $min . '.';
                }
                break;

            case 'gt_date':
                if (isset($params[0], $this->data[$params[0]]) && (string) $value !== '') {
                    $d1 = strtotime((string) $value);
                    $d2 = strtotime((string) $this->data[$params[0]]);
                    if ($d1 !== false && $d2 !== false && $d1 < $d2) {
                        $this->errors[$field] = 'La date doit être postérieure à la date de début.';
                    }
                }
                break;
        }
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }

    public function data(): array
    {
        return $this->data;
    }
}