<?php

namespace Datahouse\MON\Tests\Register\Add;

use Datahouse\MON\Exception\VoucherInvalidException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Register\Add\ViewModel;
use Datahouse\MON\Types\UserHash;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $email = 'pem@datahouse.ch';
    private $pwd1 = 'testestT1';
    private $pwd2 = 'testestT1';
    private $firstname = 'user';
    private $lastname = 'name';
    private $company = 'datahouse';
    private $pricingPlanId = 1;
    private $voucherCode = 'dsafasf';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Register\Add\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $userHash = new UserHash();
        $userHash->setUserId(1);
        $userHash->setHash('dasfasf');
        $mockModel->method('createUser')
                  ->willReturn($userHash);
        $mockModel->method('isEmailUnique')
                  ->willReturn('true');
        $mockModel->method('getVoucher')
                  ->will($this->throwException(new VoucherInvalidException()));
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->setPwd1($this->pwd1);
        $viewModel->setPwd2($this->pwd2);
        $viewModel->setFirstname($this->firstname);
        $viewModel->setLastname($this->lastname);
        $viewModel->setCompany($this->company);
        $viewModel->setPricingPlanId($this->pricingPlanId);
        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('createUser')
                  ->will($this->throwException(new \Exception()));
        $mockModel->method('isEmailUnique')
                  ->willReturn('true');
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail($this->email);
        $viewModel->setPwd1($this->pwd1);
        $viewModel->setPwd2($this->pwd2);
        $viewModel->setFirstname($this->firstname);
        $viewModel->setLastname($this->lastname);
        $viewModel->setCompany($this->company);
        $viewModel->setPricingPlanId($this->pricingPlanId);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');
        //validation Exception
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setEmail('datahouse');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
        //voucher validation Exception
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setVoucherCode($this->voucherCode);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
    }
}
