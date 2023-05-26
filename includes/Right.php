<?php

namespace WebFramework\Core;

class Right extends DataCore
{
    protected static string $tableName = 'rights';
    protected static array $baseFields = ['short_name', 'name'];
}
