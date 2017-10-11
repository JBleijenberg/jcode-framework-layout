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
 * @category    docroot
 * @package     docroot
 * @author      Jeroen Bleijenberg <jeroen@jcode.nl>
 *
 * @copyright   Copyright (c) 2017 J!Code (http://www.jcode.nl)
 * @license     http://opensource.org/licenses/GPL-3.0 General Public License (GPL 3.0)
 */
namespace Jcode\Layout\Resource;

use Jcode\Application;
use Jcode\DataObject;

class Template extends DataObject
{

    protected $template;

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getCacheKey()
    {
        return md5(
            get_class($this) . get_called_class() . $this->template
        );
    }

    public function useCache()
    {
        return true;
    }

    /**
     * @internal param $blockname
     * @internal param array $vars
     * @internal param null $template
     */
    public function render()
    {
        if ($this->getTemplate()) {
            $config = Application::objectManager()->get('Jcode\Application\Config');

            $templateArgs = explode('::', $this->getTemplate());

            $module = $config->getModule(current($templateArgs));

            next($templateArgs);

            if (file_exists($module->getModulePath() . DS . 'View' . DS . 'Template' . DS . Application::env()->getConfig('layout/name') . DS . current($templateArgs))) {
                $file = $module->getModulePath() . DS . 'View' . DS . 'Template' . DS . Application::env()->getConfig('layout/name') . DS . current($templateArgs);
            } else {
                $file = $module->getModulePath() . DS . 'View' . DS . 'Template' . DS . current($templateArgs);
            }

            include $file;
        }
    }

    /**
     * @param $reference
     * @return mixed
     */
    public function getReferenceHtml($reference)
    {
        $layout = Application::registry('current_layout');

        if ($element = $layout->getData($reference)) {
            foreach ($element->getItemById('child_html') as $childHtml) {
                $this->renderBlock($childHtml, ['reference' => $reference]);
            }
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getChildHtml($name)
    {
        $layout =  Application::registry('current_layout')->getData($this->getReference());

        foreach ($layout->getItemById('child_html') as $block) {
            if ($block->getName() == $this->getName()) {
                return $this->renderBlock($block->getData('child_html')->getItemById($name));
            }
        }

        return null;
    }

    /**
     * @param $block
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    protected function renderBlock($block, array $args = [])
    {
        foreach ($args as $key => $val) {
            $block->setData($key, $val);
        }

        $block->render();
    }

    /**
     * Sanitize given string
     *
     * @param $string
     * @return string
     */
    public function sanitize($string)
    {
        return $this->getHelper()->sanitize($string);
    }

    public function translate()
    {
        return $this->getHelper()->translate(func_get_args());
    }

    /**
     * Get helper
     *
     * @return object|\Jcode\Resource\Helper
     * @throws \Exception
     */
    public function getHelper()
    {
        return Application::objectManager()->get('Jcode\Resource\Helper');
    }
}