<?php

namespace Datahouse\MON\Tests\Contact\Send;

use Datahouse\MON\Contact\Send\ViewModel;

/**
 * Class ViewModelTest
 *
 * @package Test
 * @author  Flavio Neuenschwnader (fne) <flavio.neuenschwander@datahouse.ch>
 * @license (c) 2014 - 2015 by Datahouse AG (https://datahouse.ch/license.v1.txt)
 */
class ViewModelTest extends \PHPUnit_Framework_TestCase
{

    private $validName = 'Name';
    private $validEmail = 'email@eamil.com';
    private $validMessage = 'Message';


    private $invalidEmail = 'not an valid email';
    private $invalidMessage = '';
    private $invalidName = 'Lorem ipsum dolor sit amet, consetetur sadipscing e';
    /**
     * test
     *
     * @return void
     */
    public function test()
    {
        $mockModel = $this->getMockBuilder('Datahouse\MON\Contact\Send\Model')
                          ->disableOriginalConstructor()
                          ->getMock();
        $viewModel =
            new ViewModel($mockModel);

        // valid input
        $viewModel->setName($this->validName);
        $viewModel->setEmail($this->validEmail);
        $viewModel->setMessage($this->validMessage);
        $this->assertTrue($viewModel->getData());
        $this->assertTrue($viewModel->getStatus() == '200');

        //invalid email
        $viewModel->setEmail($this->invalidEmail);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
        $viewModel->setEmail($this->validEmail);

        //invalid message
        $viewModel->setMessage($this->invalidMessage);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');
        $viewModel->setMessage($this->validMessage);

        //invalid name
        $viewModel->setName($this->invalidName);
        $viewModel->getData();
        $this->assertTrue($viewModel->getStatus() == '400');


    }
}
