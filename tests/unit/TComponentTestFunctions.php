<?php

/**
 * Global-namespace event handler functions for the TComponent test suites.
 *
 * Tests attach these handlers by bare function name (e.g. `'foo'`), so they
 * stay in the global namespace. Composer does not autoload functions;
 * {@see Prado\Test\Unit\TComponentTestBase} loads this file with `require_once`.
 */

//we add this as a callable
function foo($sender, $param)
{
}

//we add this as a callable
function foopre($sender, $param)
{
}

//we add this as a callable
function foopost($sender, $param)
{
}

//we add this as a callable
function foobar($sender, $param)
{
}

//we add this as a callable
function foobarfoobar($sender, $param)
{
}
