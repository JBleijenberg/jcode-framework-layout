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

class Request
{

    protected $path;

    protected $extends;

    protected $references = [];

    public function addReference(Reference $reference)
    {
        $this->references[$reference->getName()] = $reference;

        return $this;
    }

    public function getReference($name) :?Reference
    {
        if (array_key_exists($name, $this->references)) {
            return $this->references[$name];
        }

        return null;
    }

    public function getReferences()
    {
        return $this->references;
    }

    public function removeReference(Reference $reference)
    {
        if (array_key_exists($reference->getName(), $this->references)) {
            unset($this->references[$reference->getName()]);
        }

        return $this;
    }

    public function referenceExists(Reference $reference)
    {
        return array_key_exists($reference->getName(), $this->references);
    }

    /**
     * @return mixed
     */
    public function getPath() :?String
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     */
    public function setPath(String $path)
    {
        $this->path = $path;
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
}