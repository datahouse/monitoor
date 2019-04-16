<?php

namespace Datahouse\MON\Tests\Register\Activate;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Register\Activate\Model;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $activationHash = 'datahouse';


    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        try {
            $model->activateUser($this->activationHash);
            $this->assertTrue(false);
        } catch (PermissionException $e) {
            $this->assertTrue(true);
        }
    }
}
