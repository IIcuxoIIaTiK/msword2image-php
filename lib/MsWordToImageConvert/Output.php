<?php

namespace MsWordToImageConvert;

class Output
{
    private $type;
    private $imageFormat;
    private $value;

    /**
     * @param int|OutputType $type
     * @param string|OutputImageFormat $imageFormat
     * @param string $value
     */
    public function __construct($type, $imageFormat, $value = null)
    {
        $this->type = $type;
        $this->imageFormat = $imageFormat;
        $this->value = $value;
    }

    /**
     * @return int|OutputType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string|OutputImageFormat
     */
    public function getImageFormat()
    {
        return $this->imageFormat;
    }
}