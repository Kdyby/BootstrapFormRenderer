<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\BootstrapFormRenderer\DI;

use Kdyby;
use Nette\DI\Compiler;
use Nette;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RendererExtension extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$engine = $builder->getDefinition('nette.latte');

		$install = 'Kdyby\BootstrapFormRenderer\Latte\FormMacros::install';
		$engine->addSetup($install . '(?->compiler)', array('@self'));
	}



	/**
	 * @param \Nette\Configurator $config
	 */
	public static function register(Nette\Configurator $config)
	{
		$config->onCompile[] = function (Nette\Configurator $config, Compiler $compiler) {
			$compiler->addExtension('twBootstrapRenderer', new RendererExtension());
		};
	}

}
