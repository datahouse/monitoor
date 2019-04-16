<?php

namespace Datahouse\MON\Tests\User\Pushtoken;

use Datahouse\MON\Tests\AbstractModel;
use Datahouse\MON\Types\PushToken;
use Datahouse\MON\User\Pushtoken\Model;

/**
 * Class ModelTest
 *
 * @package     Test
 * @author      Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ModelTest extends AbstractModel
{

    private $token = 'dklsjfklasjfkljasf';
    private $platform = 0;
    private $userId = 1;

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $model = new Model($this->getPDO());
        $pushtoken = new PushToken();
        $pushtoken->setToken($this->token);
        $pushtoken->setPlatform($this->platform);
        $pushtoken->setDenied(false);
        $pushtoken->setUserId($this->userId);
        $this->assertTrue($model->handlePushToken($pushtoken));
    }
}
