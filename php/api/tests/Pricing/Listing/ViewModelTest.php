<?php

namespace Datahouse\MON\Tests\Pricing\Listing;

use Datahouse\MON\Pricing\Listing\ViewModel;
use Datahouse\MON\Exception\PermissionException;
use Datahouse\MON\Types\Gen\PricingPlan;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel =
            $this->getMockBuilder('Datahouse\MON\Pricing\Listing\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('readPricingPlans')
                  ->willReturn($this->getList());
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->setLang(1);
        $pricingPlans = $viewModel->getData();
        $this->assertTrue(count($pricingPlans) > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        $pricingPlan = $pricingPlans[0];
        $this->assertTrue($pricingPlan->getId() > 0);
        $this->assertNotNull($pricingPlan->getText());

        // general exception
        $mockModel->method('readPricingPlans')
                  ->will($this->throwException(new \Exception()));
        $permissionMock = $this->getMockBuilder('Datahouse\MON\Permission\PermissionHandler')
                               ->disableOriginalConstructor()
                               ->getMock();
        $permissionMock->method('assertRole')
                       ->willReturn(true);
        $viewModel =
            new ViewModel($mockModel, $permissionMock);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
    }

    /**
     * getList
     *
     *
     * @return array
     */
    private function getList()
    {
        $pricingPlans = array();
        $pricingPlan = new PricingPlan();
        $pricingPlan->setId(1);
        $pricingPlan->setText('Abo1');
        $pricingPlans[] = $pricingPlan;
        $pricingPlan = new PricingPlan();
        $pricingPlan->setId(2);
        $pricingPlan->setText('Abo2');
        $pricingPlans[] = $pricingPlan;
        return $pricingPlans;
    }
}
