<?php

namespace FormWizard;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Http\SessionSection;

/**
 * Component for multiple connected forms
 * @author František Maša <frantisekmasa1@gmail.com>
 */
class Wizard extends Control
{
    /** @var SessionSection */
    private $session;
    
    /** @var array */
    private $forms = [];
    
    /** @var array */
    public $onSuccess;
    
    /**
     * @param SessionSection $session
     */
    public function __construct(SessionSection $session)
    {
	parent::__construct();
	$this->session = $session;
	$this->session->setExpiration('20 minutes');
	if(!isset($this->session->values)) {
	    $this->session->values = [];
	}
	
	$this->template->setFile(dirname(__FILE__).'/FormWizard.latte');
    }
	
    /**
     * Add form/form-factory to wizard
     * 
     * @param Form|callable $form
     * 
     * @throws InvalidArgumentException
     */
    public function addStep($form)
    {
	if(!($form instanceof Form) && !is_callable($form)) {
	    $formType = is_object($form) ? get_class($form) : gettype($form);
	    throw new InvalidArgumentException("Expected instance of Nette\Application\UI\Form, $formType passed instead");
	}
	if(count($this->forms) == 0 && !isset($this->session->currentStep)) {
	    $this->session->currentStep = 1;
	}
	$this->forms[] = $form;
    }
    
    /**
     * @return void
     */
    public function fireEvents()
    {
	if($this->isComplete()) {
	    $this->onSuccess($this->session->values);
	}
    }
    
    /**
     * Initialization of Wizard
     * 
     * @throws WizardException
     * @throws InvalidArgumentException
     */
    public function create()
    {
	if(count($this->forms) == 0) {
	    throw new InvalidStateException('There are no forms in wizard.');
	}
	$currentStep = $this->session->currentStep;
	$form = $this->forms[$currentStep - 1];
	$values = $this->session->values ?: [];
	
	if(is_callable($form)) {
	    $form = call_user_func($form, [$values]);
	    if(!($form instanceof Form)) {
		$formType = is_object($form) ? get_class($form) : gettype($form);
		throw new InvalidArgumentException("[STEP $currentStep] Returned value of factory is not instance of Nette\Application\UI\Form, $formType passed instead.");
	    }
	}
	
	if(array_key_exists($currentStep, $this->session->values)) {
	    $form->setDefaults($this->session->values[$currentStep], TRUE);
	}
	
	$form->onSuccess[] = function(Form $form, array $values) use ($currentStep) {
	    $this->stepSuccessful($values, $currentStep);
	};
	
	
	if(isset($this['form'])) {
	    unset($this['form']);
	}
	$this->addComponent($form, 'form');
    }
    
    /**
     * @param array $values
     * @param int $step
     * @return void
     */
    public function stepSuccessful($values, $step)
    {
	$storedValues = $this->session->values ?: array();
	
	if($step != $this->session->currentStep) {
	    // This form shouldn't be submitted right now
	} else {
	    $storedValues[$step] = $values;
	    $this->session->values = $storedValues;
	    
	    if($step == count($this->forms)) {
		// Last step
		$this->setComplete();
		$this->fireEvents();
	    } else {
		$this->session->currentStep++;
		$this->redirect('this');
	    }
	}
    }
    
    /**
     * @param bool $complete
     * @return void
     */
    private function setComplete($complete = TRUE)
    {
	$this->session->isComplete = (bool)$complete;
    }
    
    /**
     * @return bool
     */
    public function isComplete()
    {
	return isset($this->session->isComplete) && $this->session->isComplete;
    }
    
    public function getStepsCount()
    {
	return count($this->forms);
    }
    
    /**
     * "Back" button signal receiver
     * @return void
     */
    public function handlePreviousStep()
    {
	if($this->isComplete()) {
	    $this->setComplete(FALSE);
	} else {
	    if($this->session->currentStep != 1) {
		$this->session->currentStep--;
	    }
	}
	$this->redirect('this');
    }
    
    /**
     * @return void
     */
    public function render()
    {	$currentStep = $this->getCurrentStep();
    
	if(!$currentStep || !$this->getComponent('form')) {
	    throw new InvalidStateException('Wizard not initalized. Use Wizard::create().');
	}
	
	$this->template->stepsCount = count($this->forms);
	$this->template->currentStep = $currentStep;
	$this->template->render();
    }
    
    /**
     * Clears state and form data from wizard
     * @return void
     */
    public function clear()
    {
	$this->session->values = array();
	$this->session->currentStep = 1;
	$this->session->isComplete = FALSE;
    }
    
    /**
     * @return array
     */
    public function getValues()
    {
	return $this->session->values;
    }
    
    /**
     * @return int|FALSE
     */
    public function getCurrentStep()
    {
	return isset($this->session->currentStep)
		? $this->session->currentStep
		: FALSE;
    }
}

class InvalidStateException extends \Exception {}
class InvalidArgumentException extends \InvalidArgumentException {}
