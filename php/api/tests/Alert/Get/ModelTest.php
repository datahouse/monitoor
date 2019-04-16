<?php

namespace Datahouse\MON\Tests\Alert\Get;

use Datahouse\MON\Alert\Get\Model;
use Datahouse\MON\Exception\KeyNotFoundException;
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
        $model = new Model($this->getPDO(), new I18());

        try {
            $model->readAlert(123, 32323);
        } catch (KeyNotFoundException $ke) {
            $this->assertTrue(true);
        }
        /*        $this->assertTrue($alert->getId() > 0);
                $this->assertNotNull($alert->getTitle());
                $this->assertNotNull($alert->getAlertType()[0]);
                $this->assertNotNull($alert->getAlertType()[0]['id']);
                $this->assertTrue(count($alert->getAlertType()[0]['cycleIds']) > 0);
                $this->assertNotNull($alert->getAlertType()[1]);
                $url = $alert->getUrl();
                $this->assertNotNull($url);
                $this->assertNotNull($url['id']);
                $this->assertNotNull($url['type']);
                $this->assertNotNull($url['title']);
                $this->assertTrue(is_array($alert->getKeywords()));*/
    }
}
