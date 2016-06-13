<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\BootstrapFormRenderer\Latte;
use Kdyby;
use Kdyby\BootstrapFormRenderer\BootstrapRenderer;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use Latte\Token;
use Nette\Forms\Form;
use Nette\InvalidStateException;
use Nette\Reflection\ClassType;


if (!class_exists('Nette\Bridges\FormsLatte\FormMacros')) {
	class_alias('Nette\Latte\Macros\FormMacros', 'Nette\Bridges\FormsLatte\FormMacros');
}


/**
 * Standard macros:
 * <code>
 * {form name} as {$form->render('begin')}
 * {form errors} as {$form->render('errors')}
 * {form body} as {$form->render('body')}
 * {form controls} as {$form->render('controls')}
 * {form actions} as {$form->render('actions')}
 * {/form} as {$form->render('end')}
 * </code>
 *
 * or shortcut
 *
 * <code>
 * {form name /} as {$form->render()}
 * </code>
 *
 * Old macros `input` & `label` are working the same.
 * <code>
 * {input name}
 * {label name /} or {label name}... {/label}
 * </code>
 *
 * Individual rendering:
 * <code>
 * {pair name} as {$form->render($form['name'])}
 * {group name} as {$form->render($form->getGroup('name'))}
 * {container name} as {$form->render($form['name'])}
 * </code>
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class FormMacros extends MacroSet
{

	/**
	 * @param Compiler $compiler
	 * @return MacroSet|void
	 */
	public static function install(Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('form', array($me, 'macroFormBegin'), array($me, 'macroFormEnd'));
		$me->addMacro('pair', array($me, 'macroPair'));
		$me->addMacro('group', array($me, 'macroGroup'));
		$me->addMacro('container', array($me, 'macroContainer'));
		return $me;
	}



	/**
	 * @return Token
	 */
	private function findCurrentToken()
	{
		static $positionRef, $tokensRef;

		if (!property_exists('Nette\Latte\Token', 'empty')) {
			return NULL;
		}

		if (empty($positionRef)) {
			$compilerRef = ClassType::from($this->getCompiler());
			$positionRef = $compilerRef->getProperty('position');
			$positionRef->setAccessible(TRUE);
			$tokensRef = $compilerRef->getProperty('tokens');
			$tokensRef->setAccessible(TRUE);
		}

		$tokens = $tokensRef->getValue($this->getCompiler());
		return $tokens[$positionRef->getValue($this->getCompiler())];
	}



	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroFormBegin(MacroNode $node, PhpWriter $writer)
	{
		if ($node->isEmpty = (substr($node->args, -1) === '/')) {
			$node->setArgs(substr($node->args, 0, -1));

			return $writer->write('$form = $__form = $_form = (is_object(%node.word) ? %node.word : $_control->getComponent(%node.word)); $__form->render(NULL, %node.array);');

		} elseif (($token = $this->findCurrentToken()) && $token->empty) {
			// $node->isEmpty = TRUE;
			return $writer->write('$form = $__form = $_form = (is_object(%node.word) ? %node.word : $_control->getComponent(%node.word)); $__form->render(NULL, %node.array);');
		}

		$word = $node->tokenizer->fetchWord();
		$node->isEmpty = in_array($word, array('errors', 'body', 'controls', 'buttons'));
		$node->tokenizer->reset();

		return $writer->write('$form = $__form = $_form = ' . get_called_class() . '::renderFormPart(%node.word, %node.array, get_defined_vars())');
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroFormEnd(MacroNode $node, PhpWriter $writer)
	{
		if (($token = $this->findCurrentToken()) && $token->empty) {
			return '';
		}

		return $writer->write('Nette\Bridges\FormsLatte\FormMacros::renderFormEnd($__form)');
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroPair(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$__form->render($__form[%node.word], %node.array)');
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroGroup(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$__form->render(is_object(%node.word) ? %node.word : $__form->getGroup(%node.word))');
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 */
	public function macroContainer(MacroNode $node, PhpWriter $writer)
	{
		return $writer->write('$__form->render($__form[%node.word], %node.array)');
	}

	/**
	 * @param string $mode
	 * @param array  $args
	 * @param array  $scope
	 * @return Form
	 * @throws InvalidStateException
	 */
	public static function renderFormPart($mode, array $args, array $scope)
	{
		if ($mode instanceof Form) {
			self::renderFormBegin($mode, $args);
			return $mode;

		} elseif (($control = self::scopeVar($scope, 'control')) && ($form = $control->getComponent($mode, FALSE)) instanceof Form) {
			self::renderFormBegin($form, $args);
			return $form;

		} elseif (($form = self::scopeVar($scope, 'form')) instanceof Form) {
			$form->render($mode, $args);

		} else {
			throw new InvalidStateException('No instanceof Nette\Forms\Form found in local scope.');
		}

		return $form;
	}



	/**
	 * @param Form $form
	 * @param array $args
	 */
	private static function renderFormBegin(Form $form, array $args)
	{
		if ($form->getRenderer() instanceof BootstrapRenderer) {
			$form->render('begin', $args);

		} else {
			\Nette\Bridges\FormsLatte\FormMacros::renderFormBegin($form, $args);
		}
	}



	/**
	 * @param array $scope
	 * @param string $var
	 * @return mixed|NULL
	 */
	private static function scopeVar(array $scope, $var)
	{
		return isset($scope['__' . $var])
			? $scope['__' . $var]
			: (isset($scope['_' . $var])
				? $scope['_' . $var]
				: (isset($scope[$var]) ? $scope[$var] : NULL));
	}

}
