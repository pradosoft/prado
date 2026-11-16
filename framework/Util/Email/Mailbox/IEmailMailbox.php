<?php

/**
 * IEmailMailbox interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Mailbox;

/**
 * IEmailMailbox interface.
 *
 * IEmailMailbox reads unseen messages from a mailbox, such as the mailbox that
 * receives bounces.  {@see TImapMailbox} implements it over IMAP.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEmailMailbox
{
	/**
	 * Connects and opens the mailbox.
	 */
	public function open(): void;

	/**
	 * @return string[] the IDs of the unseen messages.
	 */
	public function getUnseenMessageIds(): array;

	/**
	 * Fetches a message without marking it seen.
	 * @param string $id the message ID.
	 * @return string the raw RFC 5322 message.
	 */
	public function fetchMessage(string $id): string;

	/**
	 * Marks a message seen.
	 * @param string $id the message ID.
	 */
	public function markSeen(string $id): void;

	/**
	 * Closes the mailbox and the connection.
	 */
	public function close(): void;
}
