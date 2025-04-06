<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Event;

/**
 * Empty interface that can be extended to create an event.
 *
 * Events are supposed to only contain data, not logic.
 *
 * The logic should be implemented in a EventListener class that is
 * registered in the EventService
 */
interface Event {}
