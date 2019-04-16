<?php

namespace Datahouse\MON\Tests\Url\Free;

use Datahouse\MON\Exception\UrlsExceededException;
use Datahouse\MON\I18\I18;
use Datahouse\MON\Types\UserHash;
use Datahouse\MON\Url\Free\ViewModel;
/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Peter Müller (pem) <peter.mueller@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $url = 'http://www.datahouse.ch/';
    private $email = 'pem@datahouse.ch';
    private $hash = 'skldfjasifjoaisfj';

    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $userHash = new UserHash();
        $userHash->setHash($this->hash);
        $mockModel = $this->getMockBuilder('Datahouse\MON\Url\Free\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->method('addFreeUrl')
                  ->willReturn($userHash);
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setLang(1);
        $viewModel->setUrl($this->url);
        $viewModel->setEmail($this->email);

        $result = $viewModel->getData();
        $this->assertTrue($result);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('addFreeUrl')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel,  new I18());
        $viewModel->setUrl($this->url);
        $viewModel->setEmail($this->email);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '500');

        //validation Exception
        $mockModel->method('addFreeUrl')
                  ->willReturn($userHash);
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setUrl('www');
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');

        //validation Exception
        $mockModel->method('addFreeUrl')
            ->will($this->throwException(new UrlsExceededException()));
        $viewModel =
            new ViewModel($mockModel, new I18());
        $viewModel->setUrl($this->url);
        $viewModel->setEmail($this->email);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '403');
    }
}
