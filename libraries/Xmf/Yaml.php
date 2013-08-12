<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * Yaml dump and parse methods
 *
 * YAML is a serialization format most useful when human readability
 * is a consideration. It can be useful for configuration files, as
 * well as import and export functions.
 *
 * This file is a front end for a separate YAML package present in the
 * vendor directory. The intent is to provide a consistent interface
 * no mater what underlying library is actually used.
 *
 * At present, this class expects the mustangostang/spyc package
 *
 * @category  Xmf\Module\Yaml
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @see       http://www.yaml.org/
 * @since     1.0
 */
class Yaml
{

    /**
     * Dump an PHP array as a YAML string
     *
     * @param array $var variable which will be dumped
     *
     * @return mixed|string
     */
    public static function dump($var)
    {
        return \Spyc::YAMLDump($var);
    }

    /**
     * Load a YAML string into a PHP array
     *
     * @param string $yamlString YAML dump string
     *
     * @return mixed|string
     */
    public static function load($yamlString)
    {
        return \Spyc::YAMLLoadString($yamlString);
    }

    /**
     * Read a file containing YAML into a PHP array
     *
     * @param string $yamlFile filename of YAML file
     *
     * @return mixed|string
     */
    public static function read($yamlFile)
    {
        return \Spyc::YAMLLoad($yamlFile);
    }

    /**
     * Save a PHP array as a YAML file
     *
     * @param array  $var      variable which will be dumped
     * @param string $yamlFile filename of YAML file
     *
     * @return mixed|string
     */
    public static function save($var, $yamlFile)
    {
        $yamlString = \Spyc::YAMLDump($var);
        return file_put_contents($yamlFile, $yamlString);
    }

}
