<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Pagination;

use WebFramework\Entity\Entity;
use WebFramework\Entity\EntityCollection;

/**
 * @template T of Entity
 */
class Paginator
{
    /**
     * @param EntityCollection<T> $items
     */
    public function __construct(
        private EntityCollection $items,
        private int $total,
        private int $perPage,
        private int $currentPage
    ) {}

    /**
     * @return EntityCollection<T>
     */
    public function getItems(): EntityCollection
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->getLastPage();
    }
}
