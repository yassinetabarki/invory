<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Orders\OrderMessages;
use RuntimeException;

/**
 * An order can't be deleted: units are in cutting or ready to ship, so material taken out of stock would be lost (S-49). The message
 * is written for the user ({@see OrderMessages::deleteRefusal()}) and shown as an error toast.
 */
final class OrderDeleteRefusedException extends RuntimeException {}
