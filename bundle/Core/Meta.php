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
     * Constructor.
     */
    public function __construct(
        protected ?string $name = null,
        protected ?string $content = null,
        protected ?string $fieldType = null,
        protected ?bool $required = null,
        protected ?string $minLength = null,
        protected ?string $maxLength = null
    ) {
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
        $this->content = $content;

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
            'fieldType',
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
        return match ($name) {
            'name' => $this->getName(),
            'content' => $this->getContent(),
            'fieldType' => $this->getFieldType(),
            'required' => $this->getRequired(),
            'minLength' => $this->getMinLength(),
            'maxLength' => $this->getMaxLength(),
            default => throw new PropertyNotFoundException($name, static::class),
        };
    }

    public function isEmpty(): bool
    {
        return '' === $this->getContent();
    }
}
