<?php

namespace Datahouse\MON\Tests\I18\Trans;

use Datahouse\MON\I18\Trans\ViewModel;

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
            $this->getMockBuilder('Datahouse\MON\I18\Trans\Model')
                 ->disableOriginalConstructor()
                 ->getMock();
        $mockModel->method('getTranslation')
                  ->willReturn($this->getList());
        $viewModel =
            new ViewModel($mockModel);
        $viewModel->setLang(1);
        $translations = $viewModel->getData();
        $this->assertTrue(count($translations) > 0);
        $this->assertTrue($viewModel->getStatus() == '200');

        // general exception
        $mockModel->method('getTranslation')
                  ->will($this->throwException(new \Exception()));
        $viewModel =
            new ViewModel($mockModel);
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
        $translations = array();
        $translations[] = 'fasf';
        return $translations;
    }
}
