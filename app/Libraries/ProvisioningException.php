<?php

namespace App\Libraries;

use RuntimeException;

/**
 * Thrown by ProvisioningService when a company can't be provisioned
 * (bad slug, taken slug, DB/migration failure, etc). The message is
 * safe to show directly to the superadmin who submitted the form.
 */
class ProvisioningException extends RuntimeException
{
}
