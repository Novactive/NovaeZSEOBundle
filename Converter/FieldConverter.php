<?php

namespace Novactive\Bundle\eZSEOBundle\Converter;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface used to implement field data converter.
 *
 * @author Rafał Toborek <rafal.toborek@ez.no>
 */
interface FieldConverter
{
    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $value
     */
    public function setOutput(OutputInterface $value);

    /**
     * @param string $value
     */
    public function setMetasFieldNameIdentifier($value);

    /**
     * Converts content objects in specified portions.
     *
     * @param array $contentTypeIdentifiers
     * @param string $fieldName
     * @param int $limit
     *
     * @return bool
     */
    public function convert($contentTypeIdentifiers = array(), $fieldName, $limit);
}
