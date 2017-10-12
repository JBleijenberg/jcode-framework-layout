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
namespace Jcode\Layout;

use Jcode\Application;
use Jcode\DataObject;
use Jcode\DataObject\Collection;
use SimpleXMLElement;

class Layout
{

    protected $isSharedInstance = true;

    protected $paths = [];

    protected $blocks = [];

    /**
     * @param $element
     *
     * @return mixed
     * @throws \Exception
     * @internal param \Jcode\Application\Resource\Template $block
     * @internal param $template
     */
    public function getLayout($element)
    {
        if (!is_string($element)) {
            throw new \Exception('Element must be of type string');
        }

        $this->buildLayout();

        if (array_key_exists($element, $this->paths)) {
            return $this->paths[$element];
        }

        return null;
    }

    /**
     * Collection layout information from XML and parse rewrites.
     */
    protected function buildLayout()
    {
        if (empty($this->paths)) {
            $this->collectLayoutXml();
        }

        foreach ($this->paths as $path) {
            foreach($path->getReferenceCollection() as $reference) {
                if ($reference->getExtends()) {
                    $d = array_filter($this->paths, function ($p) use ($reference) {
                        /** @var Collection $p */
                       if ($p->getReferenceCollection()->getItemByColumnValue('name', $reference->getExtends())) {
                           return true;
                       }
                    });

                    $parent = current($d);

                    $parentReference = $parent->getReferenceCollection()->getItemByColumnValue('name', $reference->getExtends());

                    foreach ($parentReference->getBlockCollection() as $block) {
                        $reference->getBlockCollection()->addItem($block, $block->getName(), false);
                    }

                    $reference->setName($parentReference->getName());
                }

                foreach ($reference->getBlockCollection() as $block) {
                    $this->storeBlocks($block);
                }
            }
        }

        foreach ($this->blocks as $origBlock) {
            if ($origBlock->getExtends()) {
                if (array_key_exists($origBlock->getExtends(), $this->blocks)) {

                } else {
                    throw new \Exception("Cannot extend block '{$origBlock->getExtends()}. Block does not exist.");
                }

                $parentBlock = $this->blocks[$origBlock->getExtends()];

                if (!$origBlock->getTemplate()) {
                    $origBlock->setTemplate($parentBlock->getTemplate());
                }

                if (!$origBlock->getClass()) {
                    $origBlock->setClass($parentBlock->getClass());
                }

                if ($parent->getBlockCollection()) {
                    $blockCollection = ($origBlock->getBlockCollection())
                        ? $origBlock->getBlockCollection()
                        : Application::objectManager()->get('\Jcode\DataObject\Collection');

                    foreach ($parentBlock->getBlockCollection() as $child) {
                        $origCollection->addItem($child, $child->getName());
                    }

                    $origBlock->setBlockCollection($blockCollection);
                }
            }
        }

        return $this;
    }

    /**
     * Store blocks in their own array for easy manipulation.
     *
     * @param DataObject $block
     * @return $this
     * @throws \Exception
     */
    protected function storeBlocks(DataObject $block)
    {
        if (!$block->getName()) {
            throw new \Exception('Block element requires a name to be set');
        }

        $this->blocks[$block->getName()] = $block;

        if ($block->getBlockCollection()) {
            foreach ($block->getBlockCollection() as $child) {
                $this->storeBlocks($child);
            }
        }

        return $this;
    }

    /**
     * Parse reference object
     *
     * @param DataObject $reference
     * @return $this
     */
    public function parseReference(DataObject$reference)
    {
        foreach ($reference->getBlockCollection() as $block) {
            $this->getLayoutBlock($block)->render();
        }

        return $this;
    }

    /**
     * @param DataObject $block
     * @return DataObject
     * @internal param SimpleXMLElement $element
     *
     */
    protected function getLayoutBlock(DataObject $block)
    {
        $class = explode('::', $block->getClass());
        $subs  = array_map('ucfirst', explode('/', $class[1]));
        $class = '\\' . str_replace('_', '\\', $class[0]) . '\Block\\' . implode('\\', $subs);

        /** @var DataObject $blockClass */
        $blockClass = Application::objectManager()->get($class);

        $blockClass->setName($block->getName());

        if ($block->getTemplate()) {
            $blockClass->setTemplate($block->getTemplate());
        }

        if ($block->getMethods()) {
            foreach ($block->getMethods() as $method => $values) {
                foreach ($values as $value) {
                    $blockClass->$method($value);
                }
            }
        }

        return $blockClass;
    }

    protected function collectLayoutXml()
    {
        $files = array_merge(
            glob(BP . DS . 'application' . DS . '*' . DS . '*' . DS . 'View' . DS . 'Layout' . DS . '*.xml'),
            glob(BP . DS . 'application' . DS . '*' . DS . '*' . DS . 'View' . DS . 'Layout' . DS . Application::env()->getConfig('layout') . DS . '*.xml')
        );

        foreach ($files as $file) {
            $xml = simplexml_load_file($file);

            foreach ($xml->request as $request) {
                if (!empty($request['path'])) {
                    /**
                     * @var DataObject $requestObject
                     * @var Collection $references
                     */
                    $requestObject       = Application::objectManager()->get('\Jcode\DataObject');
                    $referenceCollection = Application::objectManager()->get('\Jcode\DataObject\Collection');

                    $requestObject->setPath((string)$request['path']);

                    if ($request->reference) {
                        foreach ($request->reference as $reference) {
                            /** @var DataObject $referenceObject */
                            $referenceObject = Application::objectManager()->get('\Jcode\DataObject');

                            $referenceObject->setName((string)$reference['name']);

                            if (isset($reference['extends'])) {
                                $referenceObject->setExtends((string)$reference['extends']);
                            }


                            if ($reference->block) {
                                $blockCollection = Application::objectManager()->get('\Jcode\DataObject\Collection');

                                foreach ($reference->block as $block) {
                                    $blockCollection->addItem($this->convertBlockXmlToObject($block), (string)$block['name']);
                                }

                                $referenceObject->setBlockCollection($blockCollection);
                            }

                            $referenceCollection->addItem($referenceObject, $referenceObject->getName());
                        }
                    }

                    $requestObject->setReferenceCollection($referenceCollection);

                    $this->paths[$requestObject->getPath()] = $requestObject;
                }
            }
        }
    }

    /**
     * Convert SimpleXMLElement into Jcode DataObject
     *
     * @param SimpleXMLElement $block
     * @return DataObject
     */
    protected function convertBlockXmlToObject(SimpleXMLElement $block)
    {
        /** @var DataObject $blockObject */
        $blockObject = Application::objectManager()->get('\Jcode\DataObject');

        $blockObject->setName((string)$block['name']);

        if ($block['class']) {
            $blockObject->setClass((string)$block['class']);
        }

        if ($block['template']) {
            $blockObject->setTemplate((string)$block['template']);
        }

        if ($block['extends']) {
            $blockObject->setExtends((string)$block['extends']);
        }

        if (isset($block->block)) {
            /** @var Collection $blockCollection */
            $blockCollection = Application::objectManager()->get('\Jcode\DataObject\Collection');

            foreach ($block->block as $child) {
                $blockCollection->addItem($this->convertBlockXmlToObject($child), (string)$child['name']);
            }

            $blockObject->setBlockCollection($blockCollection);
        }

        if (isset($block->method)) {
            $methods = [];

            foreach ($block->method as $method) {
                $methods[(string)$method['name']][] = (string)$method;
            }

            $blockObject->setMethods($methods);
        }

        return $blockObject;
    }
}