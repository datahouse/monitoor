<?php

namespace Datahouse\MON\Tests;

/**
 * Class PDOMock
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class PDOMock extends \PDO
{
    /**
     *
     */
    public function __construct()
    {
        echo('dsfasfasf');
    }
}
