<?php

namespace App\Integrations\Reservations;

use RuntimeException;

/** The integration is disabled or the provider doesn't support the requested operation. */
class IntegrationUnavailable extends RuntimeException {}
