<?php

namespace Tests\Support;

/**
 * Entity that extends another entity and lists the parent fields again, in another order.
 */
class TestRelistingEntity extends TestEntity
{
    public static array $baseFields = ['email', 'name', 'age', 'active', 'secret_field', 'created_at', 'nickname'];

    protected string $nickname = '';
}
