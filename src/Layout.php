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
use Jcode\DataObject\Collection;
use Jcode\Layout\Model\Block;
use Jcode\Layout\Model\Reference;
use Jcode\Layout\Model\Request;
use SimpleXMLElement;
use Symfony\Component\Finder\Finder;

class Layout
{

    protected $isSharedInstance = true;

    /**
     * @var Collection
     */
    protected $layout;

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

        return $this->layout->getItemById($element);
    }

    /**
     * Collection layout information from XML and parse rewrites.
     */
    protected function buildLayout()
    {
        if (empty($this->layout)) {
            /** @var Collection $layout */
            $layout   = Application::getClass('\Jcode\DataObject\Collection');
            $requests = $this->collectLayoutXml();

            foreach ($this->collectLayoutXml() as $request) {
                /** @var Request $request */
                if ($request->getExtends()) {
                    /** @var Request $parentRequest */
                    $parentRequest = $requests[$request->getExtends()];

                    /** @var Reference $reference */
                    foreach ($parentRequest->getReferences() as $reference) {
                        $request->addReference(clone $reference);
                    }
                }

                /** @var Reference $reference */
                foreach ($request->getReferences() as $reference) {
                    if (!$request->referenceExists($reference)) {
                        continue;
                    }

                    if ($reference->getExtends()) {
                        $newReference = clone $request->getReference($reference->getExtends());

                        foreach ($reference->getBlocks() as $block) {
                            $newReference->addBlock($block);
                        }

                        $request->removeReference($request->getReference($reference->getExtends()));
                        $request->removeReference($reference);
                        $request->addReference($newReference);
                    }

                    if ($reference->getExtends()) {
                        $request->removeReference($reference);
                    }
                }

                foreach ($request->getReferences() as $reference) {
                    /** @var Block $block */
                    foreach ($reference->getBlocks() as $block) {
                        if (!$reference->blockExists($block)) {
                            continue;
                        }

                        if ($block->getExtends()) {
                            $newBlock = $reference->getBlock($block->getExtends());

                            if ($block->getClass()) {
                                $newBlock->setClass($block->getClass());
                            }

                            if ($block->getTemplate()) {
                                $newBlock->setTemplate($block->getTemplate());
                            }

                            foreach ($block->getMethods() as $method => $values) {
                                foreach ($values as $value) {
                                    $newBlock->addMethod($method, $value);
                                }
                            }

                            $reference->removeBlock($block);
                            $reference->removeBlock($reference->getBlock($block->getExtends()));
                            $reference->addblock($newBlock);
                        }
                    }
                }

                $layout->addItem($request, $request->getPath());
            }

            $this->layout = $layout;
        }

        return $this;
    }

    protected function collectLayoutXml()
    {
        $requests = [];
        $finder   = new Finder();

        $finder
            ->files()
            ->ignoreUnreadableDirs()
            ->followLinks()
            ->name('*.xml')
            ->depth('> 2')
            ->in(BP . DS . 'application');

        foreach ($finder as $file) {
            $xml = simplexml_load_file($file->getPathname());

            foreach ($xml->request as $request) {
                if (!empty($request['path'])) {
                    /**
                     * @var Request $requestObject
                     * @var Collection $references
                     */
                    $requestObject       = Application::getClass('\Jcode\Layout\Model\Request');

                    $requestObject->setPath((string)$request['path']);

                    if (isset($request['extends'])) {
                        $requestObject->setExtends((string)$request['extends']);
                    }

                    if ($request->reference) {
                        foreach ($request->reference as $reference) {
                            /** @var Reference $referenceObject */
                            $referenceObject = Application::getClass('\Jcode\Layout\Model\Reference');

                            $referenceObject->setName((string)$reference['name']);

                            if (isset($reference['extends'])) {
                                $referenceObject->setExtends((string)$reference['extends']);
                            }

                            if ($reference->block) {
                                foreach ($reference->block as $block) {
                                    $referenceObject->addblock($this->convertBlockXmlToObject($block));
                                }
                            }

                            $requestObject->addReference($referenceObject);
                        }
                    }

                    $requests[$requestObject->getPath()] = $requestObject;
                }
            }
        }

        return $requests;
    }

    /**
     * Convert SimpleXMLElement into Jcode DataObject
     *
     * @param SimpleXMLElement $block
     * @return Block
     */
    protected function convertBlockXmlToObject(SimpleXMLElement $block)
    {
        /** @var Block $blockObject */
        $blockObject = Application::getClass('\Jcode\Layout\Model\Block');

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
            foreach ($block->block as $child) {
                $blockObject->addChild($this->convertBlockXmlToObject($child));
            }
        }

        if (isset($block->method)) {
            foreach ($block->method as $method) {
                $blockObject->addMethod((string)$method['name'], (string)$method);
            }
        }

        return $blockObject;
    }
}