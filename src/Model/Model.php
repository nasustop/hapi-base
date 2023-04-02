<?php

namespace Nasustop\HapiBase\Model;

use Hyperf\HttpMessage\Exception\ServerErrorHttpException;

abstract class Model extends \Hyperf\Database\Model\Model
{
    public function getCols()
    {
        if (! isset($this->cols)) {
            throw new ServerErrorHttpException('当前model必须设置cols');
        }
        return $this->cols;
    }

}