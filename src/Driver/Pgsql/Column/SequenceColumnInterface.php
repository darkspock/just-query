<?php

declare(strict_types=1);

namespace JustQuery\Driver\Pgsql\Column;

use JustQuery\Schema\Column\ColumnInterface;

interface SequenceColumnInterface extends ColumnInterface
{
    /**
     * Returns name of an associated sequence if column is auto incremental.
     *
     * @psalm-mutation-free
     */
    public function getSequenceName(): ?string;

    /**
     * Set the name of an associated sequence if a column is auto incremental.
     */
    public function sequenceName(?string $sequenceName): static;
}
