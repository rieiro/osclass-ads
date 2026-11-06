#!/bin/sh
set -e

DB_USER="osclass"
DB_PASS="OsclassPass2026!"
PROD_DB="osclass"
TEST_DB="osclass_testing"

mariadb -u root -e "DROP DATABASE IF EXISTS ${TEST_DB}; CREATE DATABASE ${TEST_DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON ${TEST_DB}.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

# 1. Clone complete schema (core + all 13 plugin tables, zero user rows)
mariadb-dump -u root --no-data --routines --triggers "$PROD_DB" | sed "s/DEFINER=[^*]*\*/\*/g" | mariadb -u"$DB_USER" -p"$DB_PASS" "$TEST_DB"

# 2. Copy read-only baseline metadata (locales, currencies, categories, countries/regions/cities, preferences, pages)
mariadb-dump -u root --no-create-info "$PROD_DB" \
  oc_t_locale oc_t_currency oc_t_category oc_t_category_description oc_t_category_stats \
  oc_t_country oc_t_region oc_t_city oc_t_preference oc_t_pages oc_t_pages_description oc_t_plugin_category \
  | mariadb -u"$DB_USER" -p"$DB_PASS" "$TEST_DB"

echo "OK: ${TEST_DB} initialized cleanly."
