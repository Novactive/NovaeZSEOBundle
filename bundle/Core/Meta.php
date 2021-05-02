<?php
/**
 * NovaeZSEOBundle Meta.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core;

use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;

class Meta
{
    /**
     * Meta name.
     *
     * @var string
     */
    protected $name;

    /**
     * Meta content.
     *
     * @var string
     */
    protected $content;

    /**
     * Meta fieldType.
     *
     * @var string
     */
    protected $fieldType;

    /**
     * Constructor.
     *
     * @param string $name
     * @param mixed $content
     * @param string $fieldType
     */
    public function __construct(?string $name = null, $content = null, ?string $fieldType = null)
    {
        $this->name      = $name;
        $this->content   = $content;
        $this->fieldType = $fieldType;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(?string $name): self
    {
        $this->name = $name ?? '';

        return $this;
    }

    public function getFieldType(): string
    {
        return $this->fieldType ?? '';
    }

    public function setFieldType(?string $fieldType): self
    {
        $this->fieldType = $fieldType ?? '';

        return $this;
    }

    public function getContent()
    {
        return $this->content ?? null;
    }

    public function setContent($content): self
    {
        $this->content = $content ?? null;

        return $this;
    }

    /**
     * Returns true if the provided attribute exists.
     */
    public function hasAttribute(string $name): bool
    {
        return \in_array($name, $this->attributes(), true);
    }

    /**
     * Returns an array with attributes that are available.
     */
    public function attributes(): array
    {
        return [
            'name',
            'content',
            'fieldType'
        ];
    }

    /**
     * Returns the specified attribute.
     */
    public function attribute(string $name): ?string
    {
        switch ($name) {
            case 'name':
                return $this->getName();
                break;
            case 'content':
                return $this->getContent();
                break;
            case 'fieldType':
                return $this->getFieldType();
                break;
            default:
                throw new PropertyNotFoundException($name, \get_class($this));
                break;
        }
    }

    public function isEmpty(): bool
    {
        return '' === $this->getContent();
    }
}
