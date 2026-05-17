<?php

/**
 * Prado3-style fixture: class defined in global namespace (no PHP namespace declaration).
 * Used by PradoBaseTest to verify that Prado::using() correctly creates the reverse
 * FQN alias ('Application\prado3stubs\GlobalNsComponent' → GlobalNsComponent) so that
 * the returned namespace string is a valid, usable PHP class name.
 */
class GlobalNsComponent extends \Prado\TComponent
{
}
