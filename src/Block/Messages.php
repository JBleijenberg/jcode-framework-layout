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

class Messages extends Template
{

    /**
     * @inject \Jcode\Resource\Session
     * @var \Jcode\Resource\Session
     */
    protected $session;

    /**
     * Retrieve messages from all registered sessions
     *
     * @param bool $purge
     * @return array
     */
    public function getMessages($purge = true)
    {
        $messages = [];

        foreach ($this->session->getRegisteredNamespaces() as $sessionClass) {
            $session = Application::objectManager()->get($sessionClass);

            foreach ($session->getMessages($purge) as $message) {
                $messages[] = $message;
            };
        }

        return $messages;
    }
}