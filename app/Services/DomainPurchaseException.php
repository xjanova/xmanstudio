<?php

namespace App\Services;

/**
 * A purchase that cannot go ahead, with a reason the customer may read.
 *
 * Everything thrown as this type is safe to render: it says what is wrong in
 * Thai, in terms of the customer's own order, and never names the upstream
 * registrar or quotes its error text. Anything that is NOT safe to show goes
 * to the log and to last_error instead, and the customer gets a generic
 * message.
 */
class DomainPurchaseException extends \RuntimeException {}
