<?php

namespace Datahouse\MON\Tests\Permission;

use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Permission\PermissionHandler;
use Datahouse\MON\Tests\AbstractModel;

/**
 * Class PermissionHandlerTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class PermissionHandlerTest extends AbstractModel
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $permission = new PermissionHandler($this->getPDO());
        $this->assertTrue($permission->assertRole(1, 1));
        try {
            $permission->assertRole(null, null);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasUrlReadAccess(0, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasUrlWriteAccess(0, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasUrlGroupWriteAccess(0, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasUrlGroupReadAccess(0, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasAlertAccess(0, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasAlertAccess(0, null);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
        try {
            $permission->hasAlertAccess(null, 0);
        } catch (PermissionException $pe) {
            $this->assertTrue(true);
        }
    }
}
