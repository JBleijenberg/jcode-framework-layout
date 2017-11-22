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
namespace Jcode\Layout\Block;

use Jcode\Application;
use Jcode\Cache\CacheInterface;
use Jcode\DataObject;
use Jcode\Layout\Layout;
use Jcode\Layout\Model\Request;

class Template extends DataObject
{

    protected $template;

    protected $name;

    protected $output = true;

    protected $children = [];

    protected $useCache = false;

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
        return get_called_class() . md5(
            get_class($this) . get_called_class() . $this->template
        );
    }

    public function useCache($cache = null)
    {
        if ($cache !== null) {
            $this->useCache = (bool) $cache;
        }

        return $this->useCache;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setOutput(Bool $bool)
    {
        $this->output = $bool;

        return $this;
    }

    public function getOutput() :Bool
    {
        return $this->output;
    }

    public function setChildren(array $children)
    {
        $this->children = $children;

        return $this;
    }

    public function beforeRender()
    {

    }

    /**
     * @internal param $blockname
     * @internal param array $vars
     * @internal param null $template
     */
    public function render()
    {
        /** @var CacheInterface $cache */
        if (($cache = Application::getConfig()->getCacheInstance()) && $cache->exists($this->getCacheKey()) && $this->useCache()) {
            echo $cache->get($this->getCacheKey());
        } else {
            if ($this->getTemplate() && $this->getOutput() == true) {
                $this->beforeRender();

                /** @var \Jcode\Application\Config $config */
                $config = Application::getClass('\Jcode\Application\Config');
                list($moduleName, $path) = explode('::', $this->getTemplate());

                /** @var Application\Module $module */
                $module = $config->getModule($moduleName);
                $path = array_map('ucfirst', explode('/', $path));
                $file = sprintf('%s/View/%s/Template/%s', $module->getModulePath(), Application::getConfig('layout'), implode('/', $path));

                if (!file_exists($file)) {
                    $file = sprintf('%s/View/Template/%s', $module->getModulePath(), implode('/', $path));
                }

                if (Application::showTemplateHints()) {
                    echo sprintf('<div style="background-color: red;">%s::%s</div><br/>', get_called_class(), $file);
                }

                ob_start();

                include $file;

                /** @var CacheInterface $cache */
                if (($cache = Application::getConfig()->getCacheInstance()) && !$cache->exists($this->getCacheKey()) && $this->useCache()) {
                    $html = ob_get_clean();

                    $cache->set($this->getCacheKey(), $html);

                    echo $html;
                }
            }
        }
    }

    /**
     * @param $name
     * @return void
     * @internal param $reference
     */
    public function getReferenceHtml($name) :void
    {
        /** @var Request $layout */
        $layout = Application::registry('current_layout');

        $layout->getReference($name)->render();
    }

    /**
     * @param $name
     * @param array $args
     * @return mixed|null
     * @internal param array $args
     */
    public function getChildBlock($name, array $args = [])
    {
        if (array_key_exists($name, $this->children)) {
            return $this->children[$name]->build($args);
        }

        return null;
    }

    public function getChildHtml($name, array $args = [])
    {
        return $this->getChildBlock($name, $args)->render();
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

    public function getUrl($path, $parameters = []) :string
    {
        return $this->getHelper()->getUrl($path, $parameters);
    }

    /**
     * Get helper
     *
     * @return object|\Jcode\Helper
     * @throws \Exception
     */
    public function getHelper()
    {
        return Application::getClass('Jcode\Helper');
    }
}