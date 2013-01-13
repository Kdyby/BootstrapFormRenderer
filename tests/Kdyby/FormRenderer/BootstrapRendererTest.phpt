<?php

/**
 * Test: Kdyby\FormRenderer\BootstrapRenderer.
 *
 * @testCase KdybyTests\FormRenderer\BootstrapRendererTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Curl
 */

namespace KdybyTests\FormRenderer;

use Kdyby;
use Kdyby\FormRenderer;
use Kdyby\FormRenderer\BootstrapRenderer;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Strings;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BootstrapRendererTest extends LatteTestCase
{

	/**
	 * @return \Nette\Application\UI\Form
	 */
	private function dataCreateRichForm()
	{
		$form = new Form();
		$form->addError("General failure!");

		$grouped = $form->addContainer('grouped');
		$grouped->currentGroup = $form->addGroup('Skupina', FALSE);
		$grouped->addText('name', 'Jméno')->getLabelPrototype()->addClass('test');
		$grouped->addText('email', 'Email')->setType('email');
		$grouped->addSelect('sex', 'Pohlaví', array(1 => 'Muž', 2 => 'Žena'));
		$grouped->addCheckbox('mailing', 'Zasílat novinky');
		$grouped->addButton('add', 'Přidat');

		$grouped->addSubmit('poke', 'Šťouchnout');
		$grouped->addSubmit('poke2', 'Ještě Šťouchnout')->setAttribute('class', 'btn-success');

		$other = $form->addContainer('other');
		$other->currentGroup = $form->addGroup('Other', FALSE);
		$other->addRadioList('sexy', 'Sexy', array(1 => 'Ano', 2 => 'Ne'));
		$other->addPassword('heslo', 'Heslo')->addError('chybka!');
		$other->addSubmit('pass', "Nastavit heslo")->setAttribute('class', 'btn-warning');

		$form->addUpload('photo', 'Fotka');
		$form->addSubmit('up', 'Nahrát fotku');
		$form->addTextArea('desc', 'Popis');
		$form->addProtection('nemam', 10);
		$form->addSubmit('submit', 'Uložit')->setAttribute('class', 'btn-primary');
		$form->addSubmit('delete', 'Smazat');

		return $form;
	}



	/**
	 * @return array
	 */
	public function dataRenderingBasics()
	{
		return array_map(function ($f) { return array($f); }, glob(__DIR__ . '/basic/input/*.latte'));
	}



	/**
	 * @dataProvider dataRenderingBasics
	 *
	 * @param string $latteFile
	 */
	public function testRenderingBasics($latteFile)
	{
		$form = $this->dataCreateRichForm();
		$this->assertFormTemplateOutput($latteFile, __DIR__ . '/basic/output/' . basename($latteFile, '.latte') . '.html', $form);
	}



	/**
	 * @return array
	 */
	public function dataRenderingComponents()
	{
		return array_map(function ($f) { return array($f); }, glob(__DIR__ . '/components/input/*.latte'));
	}



	/**
	 * @dataProvider dataRenderingComponents
	 *
	 * @param string $latteFile
	 */
	public function testRenderingComponents($latteFile)
	{
		// create form
		$form = $this->dataCreateRichForm();
		$this->assertFormTemplateOutput($latteFile, __DIR__ . '/components/output/' . basename($latteFile, '.latte') . '.html', $form);
	}



	/**
	 * @return \Nette\Application\UI\Form
	 */
	private function dataCreateForm()
	{
		$form = new Form;
		$form->addText('name', 'Name');
		$form->addCheckbox('check', 'Indeed');
		$form->addUpload('image', 'Image');
		$form->addRadioList('sex', 'Sex', array(1 => 'Man', 'Woman'));
		$form->addSelect('day', 'Day', array(1 => 'Monday', 'Tuesday'));
		$form->addTextArea('desc', 'Description');
		$form->addSubmit('send', 'Odeslat');

//		$form['checks'] = new \Kdyby\Forms\Controls\CheckboxList('Regions', array(
//			1 => 'Jihomoravský',
//			2 => 'Severomoravský',
//			3 => 'Slezský',
//		));

		$someGroup = $form->addGroup('Some Group', FALSE)
			->setOption('id', 'nemam')
			->setOption('class', 'beauty')
			->setOption('data-custom', '{"this":"should work too"}');
		$someGroup->add($form->addText('groupedName', 'Name'));

		// the div here and fieldset in template is intentional
		$containerGroup = $form->addGroup('Group with container', FALSE)
			->setOption('container', Html::el('div')->id('mam')->class('yes')->data('magic', 'is real'));
		$containerGroup->add($form->addText('containerGroupedName', 'Name'));

		return $form;
	}



	/**
	 * @return array
	 */
	public function dataRenderingIndividual()
	{
		return array_map(function ($f) { return array($f); }, glob(__DIR__ . '/individual/input/*.latte'));
	}



	/**
	 * @dataProvider dataRenderingIndividual
	 * @param string $latteFile
	 */
	public function testRenderingIndividual($latteFile)
	{
		$form = $this->dataCreateForm();
		$this->assertFormTemplateOutput($latteFile, __DIR__ . '/individual/output/' . basename($latteFile, '.latte') . '.html', $form);
	}



	public function testMultipleFormsInTemplate()
	{
		$control = new Nette\ComponentModel\Container();

		$control->addComponent($a = new Form, 'a');
		$a->addText('nemam', 'Nemam');
		$a->setRenderer(new BootstrapRenderer());

		$control->addComponent($b = new Form, 'b');
		$b->addText('mam', 'Mam');
		$b->setRenderer(new BootstrapRenderer());

		$this->assertTemplateOutput(array(
			'control' => $control, '_control' => $control
		), __DIR__ . '/edge/input/multipleFormsInTemplate.latte',
			__DIR__ . '/edge/output/multipleFormsInTemplate.html');

		$this->assertTemplateOutput(array(
				'control' => $control, '_control' => $control
			), __DIR__ . '/edge/input/multipleFormsInTemplate_parts.latte',
			__DIR__ . '/edge/output/multipleFormsInTemplate_parts.html');
	}



	/**
	 * @param $latteFile
	 * @param $expectedOutput
	 * @param \Nette\Application\UI\Form $form
	 * @throws \Exception
	 */
	protected function assertFormTemplateOutput($latteFile, $expectedOutput, Form $form)
	{
		$form->setRenderer(new BootstrapRenderer());
		foreach ($form->getControls() as $control) {
			$control->setOption('rendered', FALSE);
		}

		$control = new ControlMock();
		$control['foo'] = $form;

		$this->assertTemplateOutput(array('form' => $form, '_form' => $form, 'control' => $control, '_control' => $control), $latteFile, $expectedOutput);
	}

}

run(new BootstrapRendererTest());
