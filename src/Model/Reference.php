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

class Reference
{

    protected $name;

    protected $extends;

    protected $blocks = [];

    /**
     * Add block to reference
     *
     * @param Block $block
     * @return $this
     * @throws \Exception
     */
    public function addblock(Block $block)
    {
        if (array_key_exists($block->getName(), $this->blocks)) {
            throw new \Exception("Block '{$block->getName()} already exists");
        }

        $this->blocks[$block->getName()] = $block;

        return $this;
    }

    /**
     * Get block from reference
     *
     * @param $name
     * @return Block|null
     */
    public function getBlock($name) :?Block
    {
        if (array_key_exists($name, $this->blocks)) {
            return $this->blocks[$name];
        }

        return null;
    }

    public function removeBlock(Block $block)
    {
        if (array_key_exists($block->getName(), $this->blocks)) {
            unset($this->blocks[$block->getName()]);
        }

        return $this;
    }

    public function getBlocks()
    {
        return $this->blocks;
    }


    public function blockExists(Block $block)
    {
        return array_key_exists($block->getName(), $this->blocks);
    }

    public function getName() :?String
    {
        return $this->name;
    }

    public function setName(String $name)
    {
        $this->name = $name;
    }

    public function getExtends() :?String
    {
        return $this->extends;
    }

    public function setExtends(String $extends)
    {
        $this->extends = $extends;

        return $this;
    }

    public function __clone()
    {
        foreach ($this->blocks as &$block) {
            $block = clone $block;
        }
    }

    public function render()
    {
        foreach ($this->getBlocks() as $block) {
            $block->render();
        }
    }
}