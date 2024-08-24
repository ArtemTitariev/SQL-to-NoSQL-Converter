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
        parent::__construct($message, $code, $previous);
        
        $this->dataType = $dataType;
    }

    
    public function getDataType(): string
    {
        return $this->dataType;
    }
}
