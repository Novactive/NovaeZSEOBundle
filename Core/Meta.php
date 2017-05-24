<?php
/**
 * NovaeZSEOBundle Meta
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;

/**
 * Class representing a Meta
 */
class Meta
{
    /**
     * Meta name
     *
     * @var string
     */
    protected $name;

    /**
     * Meta content
     *
     * @var string
     */
    protected $content;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $content
     */
    public function __construct( $name = null, $content = null )
    {
        $this->name    = ( $name !== null ) ? $name : null;
        $this->content = ( $content !== null ) ? $content : null;
    }

    /**
     * Get the Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the Name
     *
     * @param string $name
     *
     * @return Meta
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the Content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the Content
     *
     * @param string $content
     *
     * @return Meta
     */
    public function setContent( $content )
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Returns true if the provided attribute exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute( $name )
    {
        return in_array( $name, $this->attributes() );
    }

    /**
     * Returns an array with attributes that are available
     *
     * @return array
     */
    public function attributes()
    {
        return array(
            'name',
            'content',
        );
    }

    /**
     * Returns the specified attribute
     *
     * @param string $name
     *
     * @return mixed
     */
    public function attribute( $name )
    {
        switch( $name ) {
            case 'name' :
                return $this->getName();
                break;
            case 'content' :
                return $this->getContent();
                break;
            default:
                throw new PropertyNotFoundException($name, get_class($this));
                break;
        }
    }

    /**
     * Check if it's empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getContent() == '';
    }
}
