<?php

namespace App\Schema\DataTypes;

class UnsupportedDataTypeException extends \InvalidArgumentException
{

    /**
     * @var string $dataType name of unsupported data type
     */
    protected $dataType;


    public function __construct(
        string $dataType,
        string $message = "Unsupported data type",
        $code = 0,
        \Throwable $previous = null
    ) {
        $this->dataType = $dataType;
        
        parent::__construct($message, $code, $previous);
    }

    
    public function getDataType(): string
    {
        return $this->dataType;
    }
}
