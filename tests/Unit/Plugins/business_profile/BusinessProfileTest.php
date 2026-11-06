<?php
declare(strict_types=1);

namespace Tests\Unit\Plugins\business_profile;

use BusinessProfile;
use Tests\Support\DatabaseTestCase;
use Tests\Support\Factories\UserFactory;
use Tests\Support\FrozenClock;

final class BusinessProfileTest extends DatabaseTestCase
{
    public function testCreateAndRetrieveBusinessProfile(): void
    {
        $user = UserFactory::create(["s_name" => "Store Owner"]);
        $bp = BusinessProfile::newInstance();

        $profileId = $bp->saveProfile((int) $user["pk_i_id"], [
            "s_slug" => "acme-motors",
            "s_company_name" => "Acme Motors LTDA",
            "s_tagline" => "The Best Cars in Town",
            "s_bio" => "Authorized dealer since 2010.",
            "s_phone" => "1133334444",
            "s_whatsapp" => "11999998888",
            "s_website" => "https://acmemotors.test",
            "s_email" => "contact@acmemotors.test",
            "s_address" => "Av Paulista, 1000 - Bela Vista",
            "d_coord_lat" => -23.561414,
            "d_coord_long" => -46.655881,
            "s_opening_hours" => json_encode([
                "mon" => ["open" => "09:00", "close" => "18:00"],
                "tue" => ["open" => "09:00", "close" => "18:00"],
                "wed" => ["open" => "09:00", "close" => "18:00"],
                "thu" => ["open" => "09:00", "close" => "18:00"],
                "fri" => ["open" => "09:00", "close" => "18:00"],
                "sat" => ["open" => "09:00", "close" => "13:00"],
                "sun" => ["closed" => true],
            ]),
            "s_social_links" => json_encode([
                "instagram" => "https://instagram.com/acmemotors",
                "facebook" => "https://facebook.com/acmemotors",
            ]),
            "s_attributes" => json_encode(["wifi" => true, "parking" => true, "pix" => true]),
        ]);

        $this->assertGreaterThan(0, $profileId);

        $profileByUserId = $bp->findByUserId((int) $user["pk_i_id"]);
        $this->assertNotEmpty($profileByUserId);
        $this->assertSame("Acme Motors LTDA", $profileByUserId["s_company_name"]);
        $this->assertSame("acme-motors", $profileByUserId["s_slug"]);

        $profileBySlug = $bp->findBySlug("acme-motors");
        $this->assertNotEmpty($profileBySlug);
        $this->assertSame((int) $user["pk_i_id"], (int) $profileBySlug["fk_i_user_id"]);
    }

    public function testVerifyAndUnverifyBusinessProfile(): void
    {
        $user = UserFactory::create();
        $bp = BusinessProfile::newInstance();
        $bp->saveProfile((int) $user["pk_i_id"], [
            "s_slug" => "verify-store",
            "s_company_name" => "Verify Store",
        ]);

        $this->assertFalse($bp->isVerified((int) $user["pk_i_id"]));
        $bp->setVerified((int) $user["pk_i_id"], true);
        $this->assertTrue($bp->isVerified((int) $user["pk_i_id"]));
        $bp->setVerified((int) $user["pk_i_id"], false);
        $this->assertFalse($bp->isVerified((int) $user["pk_i_id"]));
    }

    public function testIsOpenNowCalculatesCorrectlyWithClock(): void
    {
        $hours = [
            "mon" => ["open" => "09:00", "close" => "18:00"],
            "sun" => ["closed" => true],
        ];

        // Monday 11:30 AM UTC -> Open
        $mondayOpenClock = new FrozenClock("2026-11-02 11:30:00");
        $this->assertTrue(BusinessProfile::calculateIsOpenNow($hours, $mondayOpenClock));

        // Monday 20:00 PM UTC -> Closed
        $mondayClosedClock = new FrozenClock("2026-11-02 20:00:00");
        $this->assertFalse(BusinessProfile::calculateIsOpenNow($hours, $mondayClosedClock));

        // Sunday -> Closed
        $sundayClock = new FrozenClock("2026-11-08 12:00:00");
        $this->assertFalse(BusinessProfile::calculateIsOpenNow($hours, $sundayClock));
    }
}
