<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Thrown by CpanelApiService when a UAPI call fails or the response
 * can't be parsed. Caught by ProvisioningService, which falls back to
 * treating a manually pre-created database as valid before giving up.
 */
class CpanelApiException extends RuntimeException
{
}
