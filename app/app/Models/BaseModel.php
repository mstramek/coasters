<?php

namespace App\Models;

use CodeIgniter\Validation\ValidationInterface;
use Config\Services;

abstract class BaseModel
{
    protected ValidationInterface $validation;
    protected array $rules = [];
    protected array $commonErrors = [];

    public function __construct()
    {
        $this->validation = Services::validation();
    }

    public function validate(array $data): bool
    {
        $schema = array_keys($this->rules);

        if (count(array_diff_key($data, array_flip($schema))) > 0) {
            $this->commonErrors[] = sprintf('Invalid schema. Allowed fields are: :%s', implode(', ', $schema));
            $result = false;
        } else {
            $this->validation->setRules($this->rules);
            $result = $this->validation->run($data);
        }

         return $result;
    }

    public function getValidationErrors(): array
    {
        return array_merge($this->commonErrors, $this->validation->getErrors());
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setRules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }
}