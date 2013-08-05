<?php

namespace Xmf\Module\Helper;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Cache.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Cache extends AbstractHelper
{
    /**
     * @var string
     */
    private $_prefix;

    /**
     * @var XoopsCache
     */
    private $_cache;

    public function init()
    {
        XoopsLoad::load('xoopscache');
        $this->_prefix = $this->module->getVar('dirname') . '_';
        $this->_cache = XoopsCache::getInstance();
    }

    /**
     * @param  string $value
     * @return string
     */
    private function _prefix($value)
    {
        return $this->_prefix . $value;
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @param  mixed  $duration
     * @return bool
     */
    public function write($key, $value, $duration = null)
    {
        return $this->_cache->write($this->_prefix($key), $value, $duration);
    }

    /**
     * @param  string $key
     * @return mixed
     */
    public function read($key)
    {
        return $this->_cache->read($this->_prefix($key));
    }

    /**
     * @param  string $key
     * @return void
     */
    public function delete($key)
    {
        $this->_cache->delete($this->_prefix($key));
    }

}
