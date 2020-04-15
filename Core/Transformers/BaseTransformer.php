<?php

namespace EasySales\Integrari\Core\Transformers;

abstract class BaseTransformer
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * @return mixed
     */
    public function toArray()
    {
        return $this->data;
    }
}
