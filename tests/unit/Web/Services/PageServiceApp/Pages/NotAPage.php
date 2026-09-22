<?php

/**
 * A class file under Pages/ whose class does not extend TPage, which
 * TPageService::createPage() refuses.
 */

namespace Application\Pages;

use Prado\TComponent;

class NotAPage extends TComponent
{
}
