<?php

namespace Datahouse\MON\Tests\I18\Trans;

use Datahouse\MON\I18\Trans\Model;
use Datahouse\MON\I18\I18;
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
        $translations = $model->getTranslation('init', 1);
        $this->assertTrue(count($translations) > 0);
    }
}
