<?php

namespace Wsmallnews\Support\Enums;

/**
 * Whether a plugin's introduced components consume scopeable fields.
 *
 * Declared on the plugin via the abstract scopePolicy() of IntroducesComponents:
 * Required plugins (e.g. category) must have every introduced component declare
 * an explicit scopeable pair; None plugins (e.g. user) skip the check entirely.
 */
enum ScopePolicy: string
{
    /** Every introduced component must declare an explicit scopeable pair. */
    case Required = 'required';

    /** Components never consume scope: no declaration, no check, no injection. */
    case None = 'none';
}
