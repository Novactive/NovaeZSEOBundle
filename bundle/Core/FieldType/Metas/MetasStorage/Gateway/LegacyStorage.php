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
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class LegacyStorage.
 */
class LegacyStorage extends Gateway
{
    const TABLE = 'novaseo_meta';

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
        /*

        $connection = $this->connection;
        foreach ($field->value->externalData as $meta) {
            $insertQuery = $connection->createInsertQuery();
            $insertQuery
                ->insertInto($connection->quoteTable(self::TABLE))
                ->set(
                    $connection->quoteColumn('meta_name'),
                    $insertQuery->bindValue($meta['meta_name'], null, PDO::PARAM_STR)
                )->set(
                    $connection->quoteColumn('meta_content'),
                    $insertQuery->bindValue($meta['meta_content'], null, PDO::PARAM_STR)
                )->set(
                    $connection->quoteColumn('objectattribute_id'),
                    $insertQuery->bindValue($field->id, null, PDO::PARAM_INT)
                )->set(
                    $connection->quoteColumn('objectattribute_version'),
                    $insertQuery->bindValue($versionInfo->versionNo, null, PDO::PARAM_INT)
                );
            $insertQuery->prepare()->execute();
        }
        */

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

/*
        $connection = $this->connection;

        $query = $connection->createDeleteQuery();
        $query
            ->deleteFrom($connection->quoteTable(self::TABLE))
            ->where(
                $query->expr->lAnd(
                    $query->expr->in(
                        $connection->quoteColumn('objectattribute_id'),
                        $fieldIds
                    ),
                    $query->expr->eq(
                        $connection->quoteColumn('objectattribute_version'),
                        $query->bindValue($versionInfo->versionNo, null, PDO::PARAM_INT)
                    )
                )
            );

        $query->prepare()->execute();
*/

    }



    /**
     * Returns the data for the given $field and $version.
     */
    public function loadFieldData(VersionInfo $versionInfo, Field $field): array
    {

        /*
        $connection = $this->connection;

        $query = $connection->createSelectQuery();
        $query
            ->selectDistinct('*')
            ->from($connection->quoteTable(self::TABLE))
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $connection->quoteColumn('objectattribute_id'),
                        $query->bindValue($field->id, null, PDO::PARAM_INT)
                    ),
                    $query->expr->eq(
                        $connection->quoteColumn('objectattribute_version'),
                        $query->bindValue($versionInfo->versionNo, null, PDO::PARAM_INT)
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);

        */

        return [];
    }

    /**
     * Creates a Url find query.
     */
    protected function createSelectQuery(): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select($this->getSelectColumns())
            ->from(self::TABLE, 'url');
    }

    private function createSelectDistinctQuery(): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select(sprintf('DISTINCT %s', implode(', ', $this->getSelectColumns())))
            ->from(self::TABLE, 'url');
    }

    private function getSelectColumns(): array
    {
        return [
            sprintf('url.%s', self::COLUMN_ID),
            sprintf('url.%s', self::COLUMN_URL),
            sprintf('url.%s', self::COLUMN_ORIGINAL_URL_MD5),
            sprintf('url.%s', self::COLUMN_IS_VALID),
            sprintf('url.%s', self::COLUMN_LAST_CHECKED),
            sprintf('url.%s', self::COLUMN_CREATED),
            sprintf('url.%s', self::COLUMN_MODIFIED),
        ];
    }

}
