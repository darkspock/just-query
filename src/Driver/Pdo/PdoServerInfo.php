<?php

declare(strict_types=1);

namespace FastPHP\QueryBuilder\Driver\Pdo;

use PDO;
use FastPHP\QueryBuilder\Connection\ServerInfoInterface;
use FastPHP\QueryBuilder\Exception\NotSupportedException;

class PdoServerInfo implements ServerInfoInterface
{
    protected ?string $version = null;

    public function __construct(protected PdoConnectionInterface $db) {}

    public function getTimezone(bool $refresh = false): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            /** @var string $version */
            $version = $this->db->getActivePdo()->getAttribute(PDO::ATTR_SERVER_VERSION) ?? '';
            $this->version = $version;
        }

        return $this->version;
    }
}
