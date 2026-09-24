<?php

namespace App\Domain\GameAssets\Exceptions;

use RuntimeException;

/** A pack failed integrity checks. Publishing aborts so a partial pack is never activated (specs/10 §11.2). */
final class AssetPackException extends RuntimeException {}
