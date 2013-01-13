<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace KdybyTests\FormRenderer;

use Kdyby\FormRenderer\DI\RendererExtension;
use Nette\Config\Configurator;
use Nette\Templating\Helpers;
use Nette\Utils\Strings;
use Tester;
use Tester\TestCase;

if (@!include __DIR__ . '/../../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

// configure environment
Tester\Helpers::setup();
class_alias('Tester\Assert', 'Assert');
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/../tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
Tester\Helpers::purge(TEMP_DIR);


$_SERVER = array_intersect_key($_SERVER, array_flip(array('PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv')));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = array();


if (extension_loaded('xdebug')) {
	xdebug_disable();
	Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
}

function id($val) {
	return $val;
}

function run(TestCase $testCase) {
	$testCase->run(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL);
}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LatteTestCase extends TestCase
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
	 * @param array $params
	 * @param string $latteFile
	 * @param string $expectedOutput
	 * @throws \Exception
	 */
	protected function assertTemplateOutput(array $params, $latteFile, $expectedOutput)
	{
		$template = $this->container->createNette__Template();
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

		// assert
		Tester\Assert::match($expected, $output);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ControlMock extends \Nette\Application\UI\Control
{

}

