<?php
/**
 * A CDI version implementing the array interface.
 */

namespace Anax\DI;

class CDIArray extends CDI implements \ArrayAccess
{
    
    /**
     * Properties
     *
     */
    public $data = [];        // Store all configuration options here



    /**
     * Construct.
     *
     * @param array $options to configure options.
     */
    public function __construct($options = [])
    {
        parent::__construct();
    }


    /**
     * Construct.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }


    /**
     * Construct.
     *
     * @param array $offset to configure options.
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }



    /**
     * Construct.
     *
     * @param array $offset to configure options.
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }


    /**
     * Construct.
     *
     * @param array $offset to configure options.
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset])
            ? $this->container[$offset]
            : null;
    }
}
