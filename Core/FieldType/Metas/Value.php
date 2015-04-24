<?php
/**
 * NovaeZSEOBundle MetasValue
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas;

/**
 * Class Value
 */
use eZ\Publish\Core\FieldType\Value as BaseValue;
use Novactive\Bundle\eZSEOBundle\Core\Meta;

/**
 * Class Value
 */
class Value extends BaseValue
{
    /**
     * Array of Meta
     *
     * @var Meta[]
     */
    public $metas = [];

    /**
     * Constructor
     *
     * @param Meta[] $metas
     */
    public function __construct( $metas = null )
    {
        if ( is_array( $metas ) )
        {
            $this->metas = [];
            foreach ( $metas as $meta )
            {
                /** @var Meta $meta */
                $this->metas[$meta->getName()] = $meta;
            }
        }
    }
    /**
     * Print the instanciated class
     *
     * @return string
     */
    public function __toString()
    {
        $str = "";
        if ( count( $this->metas ) )
        {
            foreach ( $this->metas as $meta )
            {
                /** @var Meta $meta */
                $str .= "{$meta->getName()} = {$meta->getContent()}\n";
            }
        }

        return $str;
    }

    /**
     * Does the name exist
     *
     * @param string $name
     *
     * @return bool
     */
    public function nameExists( $name )
    {
        return array_key_exists( $name, $this->metas );
    }
}
