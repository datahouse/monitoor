<?php

namespace Datahouse\MON\Tests\Change\Rating;

use Datahouse\MON\Change\Rating\Model;
use Datahouse\MON\Tests\AbstractModel;

/**
 * Class ModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        try {
            $this->assertTrue($model->insertRating(584, 1, 1));
        } catch (\Exception $e) {
            $this->assertTrue(
                (strpos($e->getMessage(), 'duplicate key value') !== false)
            );
        }
    }
}
