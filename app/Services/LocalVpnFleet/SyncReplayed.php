<?php

namespace App\Services\LocalVpnFleet;

use RuntimeException;

/** A node sync whose X-Node-Time is not later than the last accepted one. */
final class SyncReplayed extends RuntimeException {}
