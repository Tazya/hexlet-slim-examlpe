<?php

namespace App;

class Validator implements ValidatorInterface
{
    const OPTIONS = [
        'name' => 'required',
        'email' => 'required',
        'password' => 'required',
        'passwordConfirmation' => 'required'
    ];
    private $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_merge(self::OPTIONS, $options);
    }

    public function validate(array $course)
    {
        $errors = [];
        foreach ($course as $key => $value) {
            if (empty($value) && $this->options[$key] === 'required') {
                $errors[$key] = "{$key} can't be blank";
            }
        }
        return $errors;
    }
}