<?php
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../src/FormWizard/Wizard.php';

use FormWizard\Wizard;
use Nette\Application\UI\Presenter;

class MockSession extends Nette\Http\Session
{
    public function __construct()
    {}
    
    public function start()
    {}
    
    public function exists()
    {
	return TRUE;
    }
}

class MockPresenter extends Presenter
{}


class MainTestCase extends Tester\TestCase
{
    /** @var Wizard */
    protected $wizard;
    
    protected $presenter;
    
    public function setUp()
    {
	$this->presenter = new MockPresenter();
	$this->wizard = new Wizard((new MockSession)->getSection('test'));
	$this->presenter->addComponent($this->wizard, 'wizard');
    }
}

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');