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

    /**
     * @var \Jcode\DataObject\Collection
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
            $element = (string)$element;
        }

        if (!$this->layout) {
            $this->layout = $this->collectLayoutXml();
        }

        if ($layout = $this->layout->getData($element)) {
            return $this->parseLayoutElement($layout);
        }

        return null;
    }

    protected function parseLayoutElement(SimpleXMLElement $element)
    {
        $object = Application::objectManager()->get('Jcode\DataObject');

        if (isset($element['extends'])) {
            $child = $this->getLayout((string)$element['extends']);

            foreach ($child as $childName => $childElement) {
                $object->setData($childName, $childElement);
            }
        }

        foreach ($element->reference as $reference) {
            $object->setData((string)$reference['name'], $this->parseReference($reference));
        }

        return $object;
    }

    public function parseReference(SimpleXMLElement $reference)
    {
        if (isset($reference['extends'])) {
            $referenceObject = $this->getLayout((string)$reference['extends'])->getData((string)$reference['name']);
        } else {
            $referenceObject = Application::objectManager()->get('Jcode\DataObject\Collection');
        }

        if (!$referenceObject->getItemById('child_html') instanceof Collection) {
            $referenceObject->addItem(Application::objectManager()->get('Jcode\DataObject\Collection'), 'child_html');
        }

        foreach ($reference->block as $block) {
            /* @var \Jcode\DataObject\Collection $childHtml */
            $childHtml = $referenceObject->getItemById('child_html');

            if ($childHtml->getItemById((string)$block['name'])) {
                $childBlock = $childHtml->getItemById((string)$block['name']);

                foreach ($this->getLayoutBlock($block)->getData() as $key => $val) {
                    $childBlock->setData($key, $val);
                }
            } else {
                $childHtml->addItem($this->getLayoutBlock($block), (string)$block['name']);
            }
        }

        return $referenceObject;
    }

    /**
     * @param \SimpleXMLElement $element
     *
     * @return DataObject
     * @throws \Exception
     */
    protected function getLayoutBlock(SimpleXMLElement $element)
    {
        $class = explode('::', (string)$element['class']);
        $subs  = array_map('ucfirst', explode('/', $class[1]));
        $class = '\\' . str_replace('_', '\\', $class[0]) . '\Block\\' . implode('\\', $subs);

        /** @var DataObject $blockObject */
        $blockObject = Application::objectManager()->get($class);

        $blockObject->setName((string)$element['name']);

        if (isset($element['template'])) {
            $blockObject->setTemplate((string)$element['template']);
        }

        if ($element->method) {
            $methodCollection = Application::objectManager()->get('Jcode\DataObject\Collection');

            foreach ($element->method as $method) {
                $args[(string)$method['name']] = (string)$method;
                $func                          = (string)$method['name'];

                $blockObject->$func(current($args));

                unset($args);
            }
        }

        if ($element->block) {
            $collection = Application::objectManager()->get('Jcode\DataObject\Collection');

            foreach ($element->block as $block) {
                $collection->addItem($this->getLayoutBlock($block), (string)$block['name']);
            }

            $blockObject->setChildHtml($collection);
        }



        return $blockObject;
    }

    protected function collectLayoutXml()
    {
        $files = array_merge(
            glob(BP . DS . 'application' . DS . '*' . DS . '*' . DS . 'View' . DS . 'Layout' . DS . '*.xml'),
            glob(BP . DS . 'application' . DS . '*' . DS . '*' . DS . 'View' . DS . 'Layout' . DS . Application::env()->getConfig('layout') . DS . '*.xml')
        );

        $layoutArray = Application::objectManager()->get('Jcode\DataObject');

        foreach ($files as $file) {
            $xml = simplexml_load_file($file);

            foreach ($xml->request as $request) {
                if (!empty($request['path'])) {
                    $layoutArray->setData((string)$request['path'], $request);
                }
            }
        }

        return $layoutArray;
    }
}