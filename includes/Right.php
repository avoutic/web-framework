<?php

namespace WebFramework\Core;

class Right extends DataCore
{
    protected static string $table_name = 'rights';
    protected static array $base_fields = ['short_name', 'name'];
}
