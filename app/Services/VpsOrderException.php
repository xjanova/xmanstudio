<?php

namespace App\Services;

/**
 * A VPS order or renewal that cannot go ahead.
 *
 * The message is written for the customer — in Thai, with nothing in it that
 * names the supplier — so controllers can flash it as it is.
 */
class VpsOrderException extends \RuntimeException {}
