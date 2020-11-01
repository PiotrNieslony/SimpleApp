<?php


namespace App\Exception;


class NotValidFormFieldException extends \Exception
{
    private $fieldsErrors = [];

    /**
     * @return array
     */
    public function getFieldsErrors(): array
    {
        return $this->fieldsErrors;
    }

    /**
     * @param array $fieldsErrors
     */
    public function setFieldsErrors(array $fieldsErrors): void
    {
        $this->fieldsErrors = $fieldsErrors;
    }

}