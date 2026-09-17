<?php

/**
 * Global class whose short name matches T[A-Z]\w+ so that
 * getErrorClassNameSpace() can resolve it via ReflectionClass without a namespace.
 *
 * This file has no namespace declaration, so the class is not autoloaded;
 * TErrorHandlerTest loads it with require_once.
 */
class TErrorHandlerTestGlobalClass
{
}
