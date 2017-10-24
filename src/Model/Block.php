<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 3.0)
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-3.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @author      Jeroen Bleijenberg
 *
 * @copyright   Copyright (c) 2017
 * @license     http://opensource.org/licenses/GPL-3.0 General Public License (GPL 3.0)
 */
namespace Jcode\Layout\Model;

use Jcode\Application;
use Jcode\Layout\Block\Template;

class Block
{

    protected $name;

    protected $class;

    protected $extends;

    protected $template;

    protected $output = true;

    protected $children = [];

    protected $methods = [];

    /**
     * @return mixed
     */
    public function getName() :?String
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName(String $name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getClass() :?String
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass(String $class)
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getExtends() :?String
    {
        return $this->extends;
    }

    /**
     * @param mixed $extends
     */
    public function setExtends(String $extends)
    {
        $this->extends = $extends;
    }

    /**
     * @return mixed
     */
    public function getTemplate() :?String
    {
        return $this->template;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate(String $template)
    {
        $this->template = $template;
    }

    public function addChild(Block $block)
    {
        $this->children[$block->getName()] = $block;

        return $this;
    }

    public function getChild($name) :?Block
    {
        if (array_key_exists($name, $this->children)) {
            return $this->children[$name];
        }

        return null;
    }

    public function getChildren() :?array
    {
        return $this->children;
    }

    public function addMethod($method, $value)
    {
        $this->methods[$method][] = $value;

        return $this;
    }

    public function getMethod($name)
    {
        if (array_key_exists($name, $this->methods)) {
            return $this->methods[$name];
        }

        return null;
    }

    public function getMethods() :?array
    {
        return $this->methods;
    }

    public function __clone()
    {
        foreach ($this->children as &$child) {
            $child = clone $child;
        }
    }

    public function setOutput(Bool $bool)
    {
        $this->output = $bool;
    }

    public function getOutput() :Bool
    {
        return $this->output;
    }

    public function render(array $args = [])
    {
        $class = explode('::', $this->getClass());
        $subs  = array_map('ucfirst', explode('/', $class[1]));
        $class = '\\' . str_replace('_', '\\', $class[0]) . '\Block\\' . implode('\\', $subs);

        /** @var Template $blockClass */
        $blockClass = Application::objectManager()->get($class);

        $blockClass->setName($this->getName());
        $blockClass->setOutput($this->getOutput());

        if ($this->getTemplate()) {
            $blockClass->setTemplate($this->getTemplate());
        }

        foreach ($this->getMethods() as $method => $values) {
            foreach ($values as $value) {
                $blockClass->$method($value);
            }
        }

        foreach ($args as $key => $value) {
            $blockClass->setData($key, $value);
        }

        $blockClass->setChildren($this->getChildren());

        return $blockClass->render();
    }
}