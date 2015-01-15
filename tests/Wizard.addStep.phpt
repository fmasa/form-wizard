<?php

use Tester\Assert;
use Nette\Application\UI\Form;

require_once __DIR__.'/bootstrap.php';

class CallbackTest
{
    public static function staticNewForm($values)
    {
	return new Form;
    }
    
    public function newForm($values)
    {
	return new Form;
    }
}

class AddStepTest extends MainTestCase
{   
    public function getWrongArguments()
    {
	return [
	    [45],
	    [[]],
	    ['string'],
	    [new stdClass],
	    [function($values) { return ':)';}],
	    [['WrongClass', 'method']],
	    [[new stdClass, 'method']]
	];
    }
    
    public function getRightArguments()
    {
	return [
	    [new Form],
	    ['CallbackTest::staticNewForm'],
	    [[new CallbackTest, 'newForm']],
	    [function($values){return new Form;}]
	];
    }
    
    /**
     * @dataProvider getWrongArguments
     * @throws InvalidArgumentException
     */
    public function testInvalidArguments($argument)
    {
	$this->wizard->addStep($argument);
	$this->wizard->create();
    }
    
    /**
     * @dataProvider getRightArguments
     */
    public function testRightArguments($argument)
    {
	$this->wizard->addStep($argument);
	$this->wizard->create();
	Assert::same($this->wizard->getStepsCount(), 1);
    }
    
    /**
     * @throws FormWizard\InvalidStateException
     */
    public function testEmptyWizard()
    {
	$this->wizard->create();
    }
    
    public function testStepsCount()
    {
	$this->wizard->addStep(new Form);
	$this->wizard->addStep(new Form);
	Assert::same($this->wizard->getStepsCount(), 2);
    }
}

$testCase = new AddStepTest();
$testCase->run();