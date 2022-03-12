<?php

namespace Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Novactive\Bundle\eZSEOBundle\Core\FieldType\Metas\MetasStorage\Gateway;

class DoctrineStorage extends Gateway
{
    public const TABLE = 'novaseo_meta';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeFieldData(VersionInfo $versionInfo, Field $field): void
    {
        foreach ($field->value->externalData as $meta) {
            $insertQuery = $this->connection->createQueryBuilder();
            $insertQuery
                ->insert($this->connection->quoteIdentifier(self::TABLE))
                ->values(
                    [
                        $this->connection->quoteIdentifier('meta_name') => ':meta_name',
                        $this->connection->quoteIdentifier('meta_content') => ':meta_content',
                        $this->connection->quoteIdentifier('objectattribute_id') => ':objectattribute_id',
                        $this->connection->quoteIdentifier('objectattribute_version') => ':objectattribute_version',
                    ]
                )
                ->setParameter(':meta_name', $meta['meta_name'], ParameterType::STRING)
                ->setParameter(':meta_content', $meta['meta_content'], ParameterType::STRING)
                ->setParameter(':objectattribute_id', $field->id, ParameterType::INTEGER)
                ->setParameter(':objectattribute_version', $versionInfo->versionNo, ParameterType::INTEGER);

            $insertQuery->execute();
        }
    }

    public function getFieldData(VersionInfo $versionInfo, Field $field): void
    {
        $field->value->externalData = $this->loadFieldData($versionInfo, $field);
    }

    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds): void
    {
        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::TABLE))
            ->where(
                $deleteQuery->expr()->andX(
                    $deleteQuery->expr()->in(
                        $this->connection->quoteIdentifier('objectattribute_id'),
                        $fieldIds
                    ),
                    $deleteQuery->expr()->eq(
                        $this->connection->quoteIdentifier('objectattribute_version'),
                        ':version'
                    )
                )
            )
            ->setParameter(':version', $versionInfo->versionNo, ParameterType::INTEGER);

        $deleteQuery->execute();
    }

    public function loadFieldData(VersionInfo $versionInfo, Field $field): array
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('*')
            ->distinct()
            ->from($this->connection->quoteIdentifier(self::TABLE))
            ->where(
                $selectQuery->expr()->andX(
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('objectattribute_id'),
                        ':objectattribute_id'
                    ),
                    $selectQuery->expr()->eq(
                        $this->connection->quoteIdentifier('objectattribute_version'),
                        ':objectattribute_version'
                    )
                )
            )
            ->setParameter(':objectattribute_id', $field->id, ParameterType::INTEGER)
            ->setParameter(':objectattribute_version', $versionInfo->versionNo, ParameterType::INTEGER);

        $statement = $selectQuery->execute();

        return $statement->fetchAll(FetchMode::ASSOCIATIVE);
    }
}
