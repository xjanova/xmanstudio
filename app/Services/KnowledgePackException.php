<?php

namespace App\Services;

use RuntimeException;

/**
 * A knowledge pack zip the GigGok app would refuse to install. The message is Thai and is shown
 * to the admin as it is, on the upload form - it has to say what to fix in the zip.
 */
class KnowledgePackException extends RuntimeException {}
