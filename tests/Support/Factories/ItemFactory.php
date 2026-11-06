<?php
declare(strict_types=1);

namespace Tests\Support\Factories;

use DAO;
use Tests\Support\FactoryClock;

final class ItemFactory
{
    private static int $seq = 5000;

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public static function create(array $overrides = []): array
    {
        self::$seq++;
        $now = FactoryClock::format();
        $userId = $overrides["fk_i_user_id"] ?? UserFactory::create()["pk_i_id"];
        $catId = $overrides["fk_i_category_id"] ?? 1;
        $title = (string) ($overrides["s_title"] ?? ("Test Listing " . self::$seq));
        $desc = (string) ($overrides["s_description"] ?? ("Description for listing " . self::$seq));
        $lat = $overrides["d_coord_lat"] ?? -23.550520;
        $lng = $overrides["d_coord_long"] ?? -46.633308;
        $city = (string) ($overrides["s_city"] ?? "Sao Paulo");
        $region = (string) ($overrides["s_region"] ?? "Sao Paulo");

        unset($overrides["s_title"], $overrides["s_description"], $overrides["d_coord_lat"], $overrides["d_coord_long"], $overrides["s_city"], $overrides["s_region"]);

        $itemData = array_merge([
            "fk_i_user_id" => $userId,
            "fk_i_category_id" => $catId,
            "dt_pub_date" => $now,
            "dt_mod_date" => $now,
            "i_price" => 150000000,
            "fk_c_currency_code" => "BRL",
            "s_contact_name" => "Seller " . self::$seq,
            "s_contact_email" => "seller" . self::$seq . "@example.test",
            "s_contact_phone" => "11999998888",
            "s_ip" => "127.0.0.1",
            "b_premium" => 0,
            "b_enabled" => 1,
            "b_active" => 1,
            "b_spam" => 0,
            "s_secret" => "itemsec" . self::$seq,
            "b_show_email" => 0,
            "b_show_phone" => 1,
            "dt_expiration" => "9999-12-31 23:59:59",
        ], $overrides);

        $dao = new DAO();
        $dao->dao->insert(DB_TABLE_PREFIX . "t_item", $itemData);
        $itemId = (int) $dao->dao->insertedId();

        $dao->dao->insert(DB_TABLE_PREFIX . "t_item_description", [
            "fk_i_item_id" => $itemId,
            "fk_c_locale_code" => "en_US",
            "s_title" => $title,
            "s_description" => $desc,
        ]);

        $dao->dao->insert(DB_TABLE_PREFIX . "t_item_location", [
            "fk_i_item_id" => $itemId,
            "fk_c_country_code" => "BR",
            "s_country" => "Brazil",
            "s_region" => $region,
            "s_city" => $city,
            "d_coord_lat" => $lat,
            "d_coord_long" => $lng,
        ]);

        $itemData["pk_i_id"] = $itemId;
        $itemData["s_title"] = $title;
        $itemData["s_description"] = $desc;
        $itemData["d_coord_lat"] = $lat;
        $itemData["d_coord_long"] = $lng;
        $itemData["s_city"] = $city;
        $itemData["s_region"] = $region;
        return $itemData;
    }
}
