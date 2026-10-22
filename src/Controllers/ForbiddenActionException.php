<?php

declare(strict_types=1);

namespace Prox\ProxGallery\Controllers;

use RuntimeException;

/** An authenticated caller lacks permission for the requested object. */
final class ForbiddenActionException extends RuntimeException
{
}
