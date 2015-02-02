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

use eZDebug;

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
                eZDebug::writeError( "Attribute '$name' does not exist", "NovactiveSEOBundle" );

                return null;
                break;
        }
    }
}
