<?php

/**
 * MessageSource class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@see mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@see http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 */

namespace Prado\I18N\core;

use Prado\Exceptions\TIOException;

/**
 * TMessageSourceIOException is thrown when a message source cannot write its catalogue.
 *
 * The XLIFF, PHP, and gettext message sources throw it for a catalogue file that is
 * not writable, and for a catalogue directory or template file they cannot create.
 * It extends {@see TIOException}, so code that catches TIOException catches it.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 */
class TMessageSourceIOException extends TIOException
{
}
