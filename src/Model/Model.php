<?php

declare(strict_types=1);
/**
 * This file is part of HapiBase.
 *
 * @link     https://www.nasus.top
 * @document https://wiki.nasus.top
 * @contact  xupengfei@xupengfei.net
 * @license  https://github.com/nasustop/hapi-base/blob/master/LICENSE
 */
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
