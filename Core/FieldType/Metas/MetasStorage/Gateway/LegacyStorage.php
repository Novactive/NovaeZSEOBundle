<?php
/**
 * NovaeZSEOBundle LegacyStorage
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;

/**
 * Class LegacyStorage
 */
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use eZ\Publish\Core\Persistence\Database\DatabaseHandler;
use RuntimeException;
use PDO;

/**
 * Class LegacyStorage
 */
class LegacyStorage extends Gateway
{

    /**
     * Table
     */
    const TABLE = "novaseo_meta";

    /**
     * Connection
     *
     * @var DatabaseHandler
     */
    protected $connection;

    /**
     * Sets the data storage connection to use
     *
     * @throws \RuntimeException if $connection is not an instance of
     *         {@link \eZ\Publish\Core\Persistence\Database\DatabaseHandler}
     *
     * @param DatabaseHandler $connection
     */
    public function setConnection( $connection )
    {
        // This obviously violates the Liskov substitution Principle, but with
        // the given class design there is no sane other option. Actually the
        // dbHandler *should* be passed to the constructor, and there should
        // not be the need to post-inject it.
        if ( !$connection instanceof DatabaseHandler )
        {
            throw new RuntimeException( "Invalid connection passed" );
        }

        $this->connection = $connection;
    }

    /**
     * Returns the active connection
     *
     * @throws RuntimeException if no connection has been set, yet.
     *
     * @return DatabaseHandler
     */
    protected function getConnection()
    {
        if ( $this->connection === null )
        {
            throw new RuntimeException( "Missing database connection." );
        }

        return $this->connection;
    }

    /**
     * Stores the metas in the database based on the given field data
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     */
    public function storeFieldData( VersionInfo $versionInfo, Field $field )
    {
        $connection = $this->getConnection();
        foreach ( $field->value->externalData as $meta )
        {
            $insertQuery = $connection->createInsertQuery();
            $insertQuery
                ->insertInto( $connection->quoteTable( self::TABLE ) )
                ->set(
                    $connection->quoteColumn( "meta_name" ),
                    $insertQuery->bindValue( $meta["meta_name"], null, PDO::PARAM_STR )
                )->set(
                    $connection->quoteColumn( "meta_content" ),
                    $insertQuery->bindValue( $meta["meta_content"], null, PDO::PARAM_STR )
                )->set(
                    $connection->quoteColumn( "objectattribute_id" ),
                    $insertQuery->bindValue( $field->id, null, PDO::PARAM_INT )
                )->set(
                    $connection->quoteColumn( "objectattribute_version" ),
                    $insertQuery->bindValue( $versionInfo->versionNo, null, PDO::PARAM_INT )
                );
            $insertQuery->prepare()->execute();
        }
    }

    /**
     * Gets the metas stored in the field
     *
     * @param VersionInfo $versionInfo
     * @param Field       $field
     */
    public function getFieldData( VersionInfo $versionInfo, Field $field )
    {
        $field->value->externalData = $this->loadFieldData( $field->id, $versionInfo->versionNo );
    }

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     *
     * @param VersionInfo $versionInfo
     * @param array       $fieldIds
     */
    public function deleteFieldData( VersionInfo $versionInfo, array $fieldIds )
    {
        $connection = $this->getConnection();

        $query = $connection->createDeleteQuery();
        $query
            ->deleteFrom( $connection->quoteTable( self::TABLE ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->in(
                        $connection->quoteColumn( "objectattribute_id" ),
                        $fieldIds
                    ),
                    $query->expr->eq(
                        $connection->quoteColumn( "objectattribute_version" ),
                        $query->bindValue( $versionInfo->versionNo, null, PDO::PARAM_INT )
                    )
                )
            );

        $query->prepare()->execute();
    }

    /**
     * Returns the data for the given $fieldId and $versionNo
     *
     * @param mixed $fieldId
     * @param mixed $versionNo
     *
     * @return array
     */
    protected function loadFieldData( $fieldId, $versionNo )
    {
        $connection = $this->getConnection();

        $query = $connection->createSelectQuery();
        $query
            ->selectDistinct( "*" )
            ->from( $connection->quoteTable( self::TABLE ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq(
                        $connection->quoteColumn( "objectattribute_id" ),
                        $query->bindValue( $fieldId, null, PDO::PARAM_INT )
                    ),
                    $query->expr->eq(
                        $connection->quoteColumn( "objectattribute_version" ),
                        $query->bindValue( $versionNo, null, PDO::PARAM_INT )
                    )
                )
            );
        $statement = $query->prepare();
        $statement->execute();

        return $statement->fetchAll( PDO::FETCH_ASSOC );
    }
}
