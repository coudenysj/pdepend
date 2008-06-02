<?php
/**
 * This file is part of PHP_Depend.
 * 
 * PHP Version 5
 *
 * Copyright (c) 2008, Manuel Pichler <mapi@pmanuel-pichler.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage TextUI
 * @author     Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright  2008 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.manuel-pichler.de/
 */

require_once 'PHP/Depend.php';
require_once 'PHP/Depend/Log/LoggerFactory.php';
require_once 'PHP/Depend/Util/ExcludePathFilter.php';
require_once 'PHP/Depend/Util/FileExtensionFilter.php';

/**
 * The command line runner starts a PDepend process.
 *
 * @category   QualityAssurance
 * @package    PHP_Depend
 * @subpackage TextUI
 * @author     Manuel Pichler <mapi@manuel-pichler.de>
 * @copyright  2008 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.manuel-pichler.de/
 */
class PHP_Depend_TextUI_Runner
{
    /**
     * Marks the default success exit.
     */
    const SUCCESS_EXIT = 0;
    
    /**
     * Marks an internal exception exit.
     */
    const EXCEPTION_EXIT = 2;
    
    /**
     * List of allowed file extensions. Default file extensions are <b>php</b>
     * and <p>php5</b>.
     *
     * @type array<string>
     * @var array(string) $_extensions
     */
    private $_extensions = array('php', 'php5');
    
    /**
     * List of exclude directories. Default exclude dirs are <b>.svn</b> and
     * <b>CVS</b>.
     *
     * @type array<string>
     * @var array(string) $_excludeDirectories
     */
    private $_excludeDirectories = array('.svn', 'CVS');
    
    /**
     * List of source code directories. 
     *
     * @type array<string>
     * @var array(string) $_sourceDirectories
     */
    private $_sourceDirectories = array();
    
    /**
     * List of log identifiers and log files.
     *
     * @type array<string>
     * @var array(string=>string) $_loggers
     */
    private $_loggerMap = array();
    
    /**
     * Sets a list of allowed file extensions.
     * 
     * NOTE: If you call this method, it will replace the default file extensions. 
     *
     * @param array(string) $extensions List of file extensions.
     * 
     * @return void
     */
    public function setFileExtensions(array $extensions)
    {
        $this->_extensions = $extensions;
    }
    
    /**
     * Sets a list of exclude directories.
     * 
     * NOTE: If this method is called, it will overwrite the default settings.
     *
     * @param array(string) $excludeDirectories
     * 
     * @return void
     */
    public function setExcludeDirectories(array $excludeDirectories)
    {
        $this->_excludeDirectories = $excludeDirectories;
    }
    
    /**
     * Sets a list of source directories.
     *
     * @param array(string) $sourceDirectories The source directories.
     * 
     * @return void
     */
    public function setSourceDirectories(array $sourceDirectories)
    {
        $this->_sourceDirectories = $sourceDirectories;
    }
    
    /**
     * Adds a logger to this runner.
     *
     * @param string $loggerID    The logger identifier.
     * @param string $logFileName The log file name.
     * 
     * @return void
     */
    public function addLogger($loggerID, $logFileName)
    {
        $this->_loggerMap[$loggerID] = $logFileName;
    }
    
    /**
     * Starts the main PDepend process and returns <b>true</b> after a successful
     * execution.
     *
     * @return boolean
     * @throws RuntimeException An exception with a readable error message and
     * an exit code. 
     */
    public function run()
    {
        $pdepend = new PHP_Depend();
        
        if (count($this->_extensions) > 0) {
            $filter = new PHP_Depend_Util_FileExtensionFilter($this->_extensions);
            $pdepend->addFileFilter($filter);
        }
        
        if (count($this->_excludeDirectories) > 0) {
            $filter = new PHP_Depend_Util_ExcludePathFilter($this->_excludeDirectories);
            $pdepend->addFileFilter($filter);
        }
        
        // Try to set all source directories.
        try {
            foreach ($this->_sourceDirectories as $sourceDirectory) {
                $pdepend->addDirectory($sourceDirectory);
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), self::EXCEPTION_EXIT);
        }
        
        $loggerFactory = new PHP_Depend_Log_LoggerFactory();
        
        // To append all registered loggers.
        try {
            foreach ($this->_loggerMap as $loggerID => $logFileName) {
                // Create a new logger
                $logger = $loggerFactory->createLogger($loggerID, $logFileName);
                
                $pdepend->addLogger($logger);
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), self::EXCEPTION_EXIT);
        }
        
        try {
            $pdepend->analyze();
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), self::EXCEPTION_EXIT);
        }
        
        return self::SUCCESS_EXIT;
    }
}