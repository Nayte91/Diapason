<?php

declare(strict_types=1);

namespace Diapason\Exception;

use InvalidArgumentException;

final class InputException extends InvalidArgumentException implements DiapasonException {}
