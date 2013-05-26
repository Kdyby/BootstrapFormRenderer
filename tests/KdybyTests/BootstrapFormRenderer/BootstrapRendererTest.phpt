<?php

/**
 * Test: Kdyby\BootstrapFormRenderer\BootstrapRenderer.
 *
 * @testCase KdybyTests\BootstrapFormRenderer\BootstrapRendererTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\BootstrapFormRenderer
 */

namespace KdybyTests\FormRenderer;

use Kdyby;
use Kdyby\BootstrapFormRenderer;
use Kdyby\BootstrapFormRenderer\BootstrapRenderer;
use Kdyby\BootstrapFormRenderer\DI\RendererExtension;
use Nette;
use Nette\Application\UI\Form;
use Nette\Config\Configurator;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BootstrapRendererTest extends TestCase
{

	/**
	 * @var \Nette\DI\Container
	 */
	protected $container;



	public function setUp()
	{
		$config = new Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(array('container' => array('class' => 'SystemContainer_' . md5(TEMP_DIR))));
		RendererExtension::register($config);
		$this->container = $config->createContainer();
	}



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
	private function assertFormTemplateOutput($latteFile, $expectedOutput, Form $form)
	{
		$form->setRenderer(new BootstrapRenderer());
		foreach ($form->getControls() as $control) {
			$control->setOption('rendered', FALSE);
		}

		$control = new ControlMock();
		$control['foo'] = $form;

		$this->assertTemplateOutput(array('form' => $form, '_form' => $form, 'control' => $control, '_control' => $control), $latteFile, $expectedOutput);
	}



	/**
	 * @param array $params
	 * @param string $latteFile
	 * @param string $expectedOutput
	 * @throws \Exception
	 */
	private function assertTemplateOutput(array $params, $latteFile, $expectedOutput)
	{
		$template = $this->container->{$this->container->getMethodName('nette.template', FALSE)}();
		/** @var \Nette\Templating\FileTemplate $template */
		$template->setCacheStorage($this->container->getService('nette.templateCacheStorage'));
		$template->setFile($latteFile);
		$template->setParameters($params);

		// render template
		ob_start();
		try {
			$template->render();
		} catch (\Exception $e) {
			ob_end_clean();
			throw $e;
		}

		$strip = function ($s) {
			return Strings::replace($s, '#(</textarea|</pre|</script|^).*?(?=<textarea|<pre|<script|\z)#si', function ($m) {
				return trim(preg_replace('#[ \t\r\n]{2,}#', "\n", str_replace('><', '>  <', $m[0])));
			});
		};

		$output = $strip(Strings::normalize(ob_get_clean()));
		$expected = $strip(Strings::normalize(file_get_contents($expectedOutput)));
		Assert::match($expected, $output);
	}

}


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ControlMock extends Nette\Application\UI\Control
{

}

run(new BootstrapRendererTest());
