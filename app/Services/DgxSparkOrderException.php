<?php

namespace App\Services;

/**
 * A DGX Spark bundle order that cannot go ahead, with a Thai reason the customer may read
 * (sold out, campaign closed). Anything not safe to show goes to the log instead.
 */
class DgxSparkOrderException extends \RuntimeException {}
