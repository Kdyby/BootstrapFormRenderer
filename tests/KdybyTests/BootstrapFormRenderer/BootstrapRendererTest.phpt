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
use Nette\Caching\Storages\PhpFileStorage;
use Nette\Configurator;
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
		return array_map(function ($f) { return array(basename($f)); }, glob(__DIR__ . '/basic/input/*.latte'));
	}



	/**
	 * @dataProvider dataRenderingBasics
	 *
	 * @param string $latteFile
	 */
	public function testRenderingBasics($latteFile)
	{
		$form = $this->dataCreateRichForm();
		$this->assertFormTemplateOutput(__DIR__ . '/basic/input/' . $latteFile, __DIR__ . '/basic/output/' . basename($latteFile, '.latte') . '.html', $form);
	}



	/**
	 * @return array
	 */
	public function dataRenderingComponents()
	{
		return array_map(function ($f) { return array(basename($f)); }, glob(__DIR__ . '/components/input/*.latte'));
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
		$this->assertFormTemplateOutput(__DIR__ . '/components/input/' . $latteFile, __DIR__ . '/components/output/' . basename($latteFile, '.latte') . '.html', $form);
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
		return array_map(function ($f) { return array(basename($f)); }, glob(__DIR__ . '/individual/input/*.latte'));
	}



	/**
	 * @dataProvider dataRenderingIndividual
	 * @param string $latteFile
	 */
	public function testRenderingIndividual($latteFile)
	{
		$form = $this->dataCreateForm();
		$this->assertFormTemplateOutput(__DIR__ . '/individual/input/' . $latteFile, __DIR__ . '/individual/output/' . basename($latteFile, '.latte') . '.html', $form);
	}



	public function testMultipleFormsInTemplate()
	{
		$control = new Nette\ComponentModel\Container();

		$control->addComponent($a = new Form, 'a');
		$a->addText('nemam', 'Nemam');
		$a->setRenderer(new BootstrapRenderer());

		$control->addComponent($b = new Form, 'b');
		$b->addText('mam', 'Mam');
		$b->setRenderer(new BootstrapRenderer($this->createTemplate()));

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
		$form->setRenderer(new BootstrapRenderer($this->createTemplate()));
		foreach ($form->getControls() as $control) {
			$control->setOption('rendered', FALSE);
		}

		if (property_exists($form, 'httpRequest')) {
			$form->httpRequest = new Nette\Http\Request(new Nette\Http\UrlScript('http://www.kdyby.org'));
		}
		foreach ($form->getComponents(TRUE, 'Nette\Forms\Controls\CsrfProtection') as $control) {
			/** @var \Nette\Forms\Controls\CsrfProtection $control */
			$control->session = new Nette\Http\Session($form->httpRequest, new Nette\Http\Response);
			$control->session->setStorage(new ArraySessionStorage($control->session));
			$control->session->start();
		}

		$control = new ControlMock();
		$control['foo'] = $form;

		$this->assertTemplateOutput(array('form' => $form, '_form' => $form, 'control' => $control, '_control' => $control), $latteFile, $expectedOutput);

		foreach ($form->getComponents(TRUE, 'Nette\Forms\Controls\CsrfProtection') as $control) {
			/** @var \Nette\Forms\Controls\CsrfProtection $control */
			$control->session->close();
		}
	}



	/**
	 * @param array $params
	 * @param string $latteFile
	 * @param string $expectedOutput
	 * @throws \Exception
	 */
	private function assertTemplateOutput(array $params, $latteFile, $expectedOutput)
	{
		$template = $this->createTemplate()->setFile($latteFile)->setParameters($params);

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



	/**
	 * @return Nette\Templating\FileTemplate
	 */
	private function createTemplate()
	{
		$template = $this->container->{$this->container->getMethodName('nette.template', FALSE)}();
		/** @var \Nette\Templating\FileTemplate $template */
		$template->setCacheStorage(new PhpFileStorage($this->container->expand('%tempDir%/cache'), $this->container->getService('nette.cacheJournal')));
		return $template;
	}

}


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ControlMock extends Nette\Application\UI\Control
{

}

/**
 * Třída existuje, aby se vůbec neukládala session, tam kde není potřeba.
 * Například v API, nebo v Cronu se různě sahá na session, i když se reálně mezi requesty nepřenáší.
 *
 * @internal
 */
class ArraySessionStorage extends Nette\Object implements Nette\Http\ISessionStorage
{

	/**
	 * @var array
	 */
	private $session;



	public function __construct(Nette\Http\Session $session = NULL)
	{
		$session->setOptions(array('cookie_disabled' => TRUE));
	}



	public function open($savePath, $sessionName)
	{
		$this->session = array();
	}



	public function close()
	{
		$this->session = array();
	}



	public function read($id)
	{
		return isset($this->session[$id]) ? $this->session[$id] : NULL;
	}



	public function write($id, $data)
	{
		$this->session[$id] = $data;
	}



	public function remove($id)
	{
		unset($this->session[$id]);
	}



	public function clean($maxlifetime)
	{

	}

}


run(new BootstrapRendererTest());
