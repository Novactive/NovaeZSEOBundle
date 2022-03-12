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

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;

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
     * @var bool|null
     */
    protected $required;

    /**
     * @var string|null
     */
    protected $minLength;

    /**
     * @var string|null
     */
    protected $maxLength;

    /**
     * Constructor.
     */
    public function __construct(
        ?string $name = null,
        ?string $content = null,
        ?bool $required = null,
        ?string $minLength = null,
        ?string $maxLength = null
    ) {
        $this->name = $name;
        $this->content = $content;
        $this->required = $required;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
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

    public function getContent(): string
    {
        return $this->content ?? '';
    }

    public function setContent(?string $content): self
    {
        $this->content = $content ?? '';

        return $this;
    }

    public function getRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(?bool $required): void
    {
        $this->required = $required;
    }

    public function getMinLength(): ?string
    {
        return $this->minLength;
    }

    public function setMinLength(?string $minLength): void
    {
        $this->minLength = $minLength;
    }

    public function getMaxLength(): ?string
    {
        return $this->maxLength;
    }

    public function setMaxLength(?string $maxLength): void
    {
        $this->maxLength = $maxLength;
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
            'required',
            'minLength',
            'maxLength',
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
            case 'content':
                return $this->getContent();
            case 'required':
                return $this->getRequired();
            case 'minLength':
                return $this->getMinLength();
            case 'maxLength':
                return $this->getMaxLength();
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
