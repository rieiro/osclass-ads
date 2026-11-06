<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\instant_messenger\classes;

use InstantMessenger;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\ItemFactory;
use Tests\Support\Factories\UserFactory;

final class InstantMessengerTest extends DatabaseTestCase
{
    public function testCreateThreadSendMessageAndMarkRead(): void
    {
        $seller = UserFactory::create(["s_name" => "Seller IM"]);
        $buyer = UserFactory::create(["s_name" => "Buyer IM"]);
        $item = ItemFactory::create(["fk_i_user_id" => $seller["pk_i_id"]]);

        $im = InstantMessenger::newInstance();
        $threadId = (int) $im->getOrCreateThread(
            (int) $item["pk_i_id"],
            (int) $buyer["pk_i_id"],
            (string) $buyer["s_name"],
            (string) $buyer["s_email"],
            (int) $seller["pk_i_id"],
            (string) $seller["s_name"],
            (string) $seller["s_email"],
            "Inquiry about listing"
        );

        $this->assertGreaterThan(0, $threadId);

        $msgId = $im->sendMessage($threadId, (int) $buyer["pk_i_id"], (string) $buyer["s_name"], (string) $buyer["s_email"], "Is this available?");
        $this->assertNotFalse($msgId);
        $this->assertSame(1, $im->countUserUnread((int) $seller["pk_i_id"]));

        $im->markThreadRead($threadId, (int) $seller["pk_i_id"]);
        $this->assertSame(0, $im->countUserUnread((int) $seller["pk_i_id"]));
    }

    public function testBlockAndUnblockUser(): void
    {
        $u1 = UserFactory::create();
        $u2 = UserFactory::create();
        $im = InstantMessenger::newInstance();

        $this->assertFalse($im->isBlocked((int) $u1["pk_i_id"], (int) $u2["pk_i_id"]));
        $im->blockUser((int) $u1["pk_i_id"], (int) $u2["pk_i_id"]);
        $this->assertTrue($im->isBlocked((int) $u1["pk_i_id"], (int) $u2["pk_i_id"]));
        $im->unblockUser((int) $u1["pk_i_id"], (int) $u2["pk_i_id"]);
        $this->assertFalse($im->isBlocked((int) $u1["pk_i_id"], (int) $u2["pk_i_id"]));
    }

    public function testSendMessageDeduplicatesRapidSubmissions(): void
    {
        $seller = UserFactory::create(["s_name" => "Seller IM2"]);
        $buyer = UserFactory::create(["s_name" => "Buyer IM2"]);
        $item = ItemFactory::create(["fk_i_user_id" => $seller["pk_i_id"]]);

        $im = InstantMessenger::newInstance();
        $threadId = (int) $im->getOrCreateThread(
            (int) $item["pk_i_id"],
            (int) $buyer["pk_i_id"],
            (string) $buyer["s_name"],
            (string) $buyer["s_email"],
            (int) $seller["pk_i_id"],
            (string) $seller["s_name"],
            (string) $seller["s_email"],
            "Duplicate Test"
        );

        $msg1 = $im->sendMessage($threadId, (int) $buyer["pk_i_id"], (string) $buyer["s_name"], (string) $buyer["s_email"], "abc");
        $msg2 = $im->sendMessage($threadId, (int) $buyer["pk_i_id"], (string) $buyer["s_name"], (string) $buyer["s_email"], "abc");

        $this->assertNotFalse($msg1);
        $this->assertSame($msg1, $msg2, "Rapid duplicate message submission must return the original message ID");

        $messages = $im->getMessages($threadId);
        $this->assertSame(1, count($messages), "Rapid duplicate message must not create a duplicate row in the database");
    }
}
