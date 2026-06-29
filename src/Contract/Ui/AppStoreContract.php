<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
 */

namespace App\Contract\Ui;

use App\Dto\Store\AppStorePayload;
use Symfony\Component\HttpFoundation\Request;

/**
 * App-level UI contract for the host store composition.
 *
 * App owns host runtime routes and store composition decisions, but it does not
 * own primary visual rendering. Implementations return neutral store payloads
 * that Viewing normalizes and renders through the shared Interfacing shell.
 */
interface AppStoreContract
{
    public function buildStore(Request $request): AppStorePayload;
}
