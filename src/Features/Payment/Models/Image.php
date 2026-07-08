<?php

declare(strict_types=1);

namespace App\Features\Payment\Models;

use App\Core\Model;

final class Image extends Model
{
    protected static string $table = 'images';

    protected static bool $softDeletes = false;
}
