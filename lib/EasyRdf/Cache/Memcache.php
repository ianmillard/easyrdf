<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2010 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2010 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @version    $Id$
 */

/**
 * An EasyRdf_Cache implementation utilising memcached
 *
 * @package    EasyRdf
 * @author     Ian Millard
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Cache_Memcache extends EasyRdf_Cache
{

    /** memcached object */
    private $_memcache = null;

    /**
     * Constructor method. Will create a new EasyRdf_Cache_Memcache. 
     * Accepts optional configuration array specifying key/values 
     * for memcacheHost and memcachePort.
     *
     * @param array $config Configuration key-value pairs.
     * @throws Exception
     */
    public function __construct($uri = null, $config = null)
    {
        $this->_config['memcachehost'] = 'localhost';
        $this->_config['memcacheport'] = 11211;
        if ($config !== null) {
            $this->setConfig($config);
        }

        //  initialise memcache if not already done so in setConfig
        if ($this->_memcache == null) $this->initMemcache();

    }

    /**
     * Set configuration parameters for this Cache instance
     * Accepts configuration array specifying key/values 
     * for memcacheHost and memcachePort.
     *
     * @param  array $config
     * @return EasyRdf_Cache
     * @throws InvalidArgumentException
     */
    public function setConfig($config = array())
    {
        if ($config == null or !is_array($config)) {
            throw new InvalidArgumentException(
                "\$config should be an array and cannot be null"
            );
        }

        foreach ($config as $k => $v) {
            $this->_config[strtolower($k)] = $v;
        }

        //  initialise connection to memcache
        $this->initMemcache();

        return $this;
    }

    /**
     * Initialise connection to memcache
     */
    private function initMemcache() 
    {

        $this->_memcache = new Memcache();
        $success = $this->_memcache->connect(
            $this->_config['memcachehost'], 
            $this->_config['memcacheport']
            );
        if (!$success) throw new Exception("Could not connect to memcache server {$this->_config['memcachehost']}:{$this->_config['memcacheport']}");
    }

    /**
     * Put a $data item into the cache, indexed by the specified $key. If there
     * is an existing $data item associated with $key then it is replaced.
     *
     * @param string $key The key against which the $data item is stored
     * @param mixed $data The data object which is to be stored
     * @return boolean false indicates an error
    */ 
    public function put($key, $data) 
    {
        $this->_memcache->set($key, array(time(), $data));
    }

    /**
     * Get an item identified by the specified $key, if it exists. 
     * If not, return null. 
     * If the optional $maxAge (in seconds) is specified then the cached item
     * shall only be returned if it has been stored more recently (ie is newer)
     * than the requested age. 
     * Set $maxAge = 0 to return an item regardless of age, if it exists.
     *
     * @param string $key The key against which the $data item is stored
     * @return mixed A data item, if it exists and is newer than required
     * $maxAge, or null 
     */ 
    public function get($key, $maxAge = 0)
    {

        if (!$this->contains($key, $maxAge)) return null;
        else {
            list($timestamp, $data) = $this->_memcache->get($key);
            return $data;
        }    
    }

    /**
     * Returns a boolean indicating whether there is an item in the cache
     * associated with the specified $key. 
     * If the optional $maxAge (in seconds) is specified then true is only
     * returned if the data item has been stored more recently (ie is newer)
     * than the requested age. 
     * Set $maxAge = 0 to check for an item regardless of age.
     *
     * @param string $key The key against which the $data item is stored
     * @return boolean Indicating whether an item exists and is newer than required $maxAge
    */ 
    public function contains($key, $maxAge = 0)
    {
    
        $blob = $this->_memcache->get($key);
        if ($blob === false) return false;

        if ($maxAge == 0) return true;
        else {
            list($timestamp, $data) = $blob;
            return (time() - $timestamp < $maxAge);
        }
    }

    /**
     * Delete an item identified by the specified $key, if it exists
     *
     * @param string $key The key against which the $data item is stored
     * @return boolean false indicates an error
    */ 
    public function delete($key)
    { 
        return $this->_memcache->delete($key);
    }

    /**
     * Remove all items from within the cache
     * @return boolean true on success, false on error
     */
    public function flush()
    {
        return $this->_memcache->flush();
    }
}

