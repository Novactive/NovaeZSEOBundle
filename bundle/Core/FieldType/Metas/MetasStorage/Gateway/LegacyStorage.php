<?php
/**
 * NovaeZSEOBundle LegacyStorage.
 *
 * @package   Novactive\Bundle\eZSEOBundle
 *
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;

use eZ\Publish\Core\Persistence\Database\DatabaseHandler;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;
use PDO;
use RuntimeException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\ParameterType;

/**
 * Class LegacyStorage.
 */
class LegacyStorage extends Gateway
{
    const TABLE = 'novaseo_meta';

    const COLUMN_ID = 'objectattribute_id';
    const COLUMN_NAME = 'meta_name';
    const COLUMN_CONTENT = 'meta_content';
    const COLUMN_VERSION = 'objectattribute_version';

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Sets the data storage connection to use.
     *
     *
     * @param DatabaseHandler $connection
     *
     * @throws \RuntimeException if $connection is not an instance of
     *                           {@link \eZ\Publish\Core\Persistence\Database\DatabaseHandler}
     */
    public function setConnection($connection): void
    {
        // This obviously violates the Liskov substitution Principle, but with
        // the given class design there is no sane other option. Actually the
        // dbHandler *should* be passed to the constructor, and there should
        // not be the need to post-inject it.
        if (!$connection instanceof DatabaseHandler) {
            throw new RuntimeException('Invalid connection passed');
        }

        $this->connection = $connection;
    }

    /**
     * Returns the active connection.
     */
    protected function getConnection(): DatabaseHandler
    {
        if (null === $this->connection) {
            throw new RuntimeException('Missing database connection.');
        }

        return $this->connection;
    }

    /**
     * Stores the metas in the database based on the given field data.
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field): void
    {
        foreach ($field->value->externalData as $meta) {

            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder
                ->insert(self::TABLE)
                ->setValue(self::COLUMN_ID, '?')
                ->setValue(self::COLUMN_NAME, '?')
                ->setValue(self::COLUMN_CONTENT, '?')
                ->setValue(self::COLUMN_VERSION, '?')
                ->setParameter(0, $field->id)
                ->setParameter(1, $meta['meta_name'])
                ->setParameter(2, $meta['meta_content'])
                ->setParameter(3, $versionInfo->versionNo);

            $queryBuilder->execute();
        }
    }

    /**
     * Gets the metas stored in the field.
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $field->value->externalData = $this->loadFieldData($versionInfo, $field);
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->delete(self::TABLE)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('objectattribute_id', $fieldIds),
                    $queryBuilder->expr()->eq('objectattribute_version', $versionInfo->versionNo)
                )
            );

        $results = $queryBuilder->execute();
    }

    /**
     * Returns the data for the given $field and $version.
     */
    public function loadFieldData(VersionInfo $versionInfo, Field $field): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder
            ->select($this->getSelectColumns())
            ->where('objectattribute_id = ' . $field->id)
            ->where('objectattribute_version = ' . $versionInfo->versionNo)
            ->from(self::TABLE, 'metadata');

        return $queryBuilder->execute()->fetchAll(FetchMode::ASSOCIATIVE);
    }

    private function getSelectColumns(): array
    {
        return [
            sprintf('metadata.%s', self::COLUMN_ID),
            sprintf('metadata.%s', self::COLUMN_NAME),
            sprintf('metadata.%s', self::COLUMN_CONTENT),
            sprintf('metadata.%s', self::COLUMN_VERSION),
        ];
    }

}
