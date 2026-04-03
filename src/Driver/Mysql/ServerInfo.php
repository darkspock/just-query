<?php

declare(strict_types=1);

namespace JustQuery\Driver\Mysql;

use JustQuery\Driver\Pdo\PdoServerInfo;

final class ServerInfo extends PdoServerInfo
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private string $timezone;

    public function getTimezone(bool $refresh = false): string
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if (!isset($this->timezone) || $refresh) {
            /** @var string $timezone */
            $timezone = $this->db->createCommand(
                "SELECT LPAD(TIME_FORMAT(TIMEDIFF(NOW(), UTC_TIMESTAMP), '%H:%i'), 6, '+')",
            )->queryScalar();
            $this->timezone = $timezone;
        }

        return $this->timezone;
    }
}
