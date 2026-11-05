<?php

namespace App\Catalogue;

use RuntimeException;

/**
 * The product source failed or returned data we can't use. Rendered as a 503 in
 * bootstrap/app.php.
 */
class CatalogueUnavailable extends RuntimeException {}
