/*M!999999\- enable the sandbox mode */
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `additional_service_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `additional_service_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `additional_service_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `charge_type` enum('flat','percentage') NOT NULL DEFAULT 'flat',
  `reverse_service_type_id` bigint(20) unsigned DEFAULT NULL,
  `reverse_weight_kg` decimal(10,2) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `is_vatable` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `additional_service_options_additional_service_id_foreign` (`additional_service_id`),
  KEY `additional_service_options_reverse_service_type_id_foreign` (`reverse_service_type_id`),
  CONSTRAINT `additional_service_options_additional_service_id_foreign` FOREIGN KEY (`additional_service_id`) REFERENCES `additional_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `additional_service_options_reverse_service_type_id_foreign` FOREIGN KEY (`reverse_service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `additional_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `additional_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kind` enum('custom','packaging','acknowledgement') NOT NULL DEFAULT 'custom',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_access_denials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_access_denials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_client_id` bigint(20) unsigned DEFAULT NULL,
  `attempted_ip` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_access_denials_api_client_id_foreign` (`api_client_id`),
  CONSTRAINT `api_access_denials_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `api_secret_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `ip_whitelist_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `rate_limit_per_minute` int(10) unsigned NOT NULL DEFAULT 60,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_clients_api_key_unique` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state_id` bigint(20) unsigned NOT NULL,
  `operational_hub_id` bigint(20) unsigned DEFAULT NULL,
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `onforwarding_classification_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `short_code` varchar(10) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_foreign` (`state_id`),
  KEY `cities_operational_hub_id_foreign` (`operational_hub_id`),
  KEY `cities_onforwarding_classification_id_foreign` (`onforwarding_classification_id`),
  KEY `cities_route_id_foreign` (`route_id`),
  CONSTRAINT `cities_onforwarding_classification_id_foreign` FOREIGN KEY (`onforwarding_classification_id`) REFERENCES `onforwarding_classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cities_operational_hub_id_foreign` FOREIGN KEY (`operational_hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cities_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_billing_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_billing_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_user_id` bigint(20) unsigned DEFAULT NULL,
  `api_client_id` bigint(20) unsigned DEFAULT NULL,
  `billing_type` enum('standard','special') NOT NULL DEFAULT 'standard',
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_billing_profiles_client_user_id_unique` (`client_user_id`),
  UNIQUE KEY `client_billing_profiles_api_client_id_unique` (`api_client_id`),
  CONSTRAINT `client_billing_profiles_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_billing_profiles_client_user_id_foreign` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_wallets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `api_client_id` bigint(20) unsigned DEFAULT NULL,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'NGN',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_wallets_user_id_unique` (`user_id`),
  UNIQUE KEY `client_wallets_api_client_id_unique` (`api_client_id`),
  CONSTRAINT `client_wallets_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `continent` varchar(255) DEFAULT NULL,
  `country_region_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_code_unique` (`code`),
  KEY `countries_country_region_id_foreign` (`country_region_id`),
  CONSTRAINT `countries_country_region_id_foreign` FOREIGN KEY (`country_region_id`) REFERENCES `country_regions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `country_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `country_regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_regions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` bigint(20) unsigned NOT NULL,
  `route_id` bigint(20) unsigned DEFAULT NULL,
  `onforwarding_classification_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `short_code` varchar(10) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `districts_city_id_foreign` (`city_id`),
  KEY `districts_onforwarding_classification_id_foreign` (`onforwarding_classification_id`),
  KEY `districts_route_id_foreign` (`route_id`),
  CONSTRAINT `districts_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `districts_onforwarding_classification_id_foreign` FOREIGN KEY (`onforwarding_classification_id`) REFERENCES `onforwarding_classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `districts_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hub_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hub_state` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hub_id` bigint(20) unsigned NOT NULL,
  `state_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hub_state_hub_id_state_id_unique` (`hub_id`,`state_id`),
  KEY `hub_state_state_id_foreign` (`state_id`),
  CONSTRAINT `hub_state_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hub_state_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hubs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `region_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hubs_code_unique` (`code`),
  KEY `hubs_region_id_foreign` (`region_id`),
  KEY `hubs_city_id_foreign` (`city_id`),
  CONSTRAINT `hubs_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `hubs_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ip_whitelists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_whitelists` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_client_id` bigint(20) unsigned NOT NULL,
  `ip_or_cidr` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_whitelists_api_client_id_ip_or_cidr_unique` (`api_client_id`,`ip_or_cidr`),
  CONSTRAINT `ip_whitelists_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `onforwarding_classifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onforwarding_classifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `surcharge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `origin_destination_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `origin_destination_tariffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_type_id` bigint(20) unsigned NOT NULL,
  `origin_state_id` bigint(20) unsigned NOT NULL,
  `origin_city_id` bigint(20) unsigned DEFAULT NULL,
  `destination_state_id` bigint(20) unsigned NOT NULL,
  `destination_city_id` bigint(20) unsigned DEFAULT NULL,
  `min_weight` decimal(10,2) NOT NULL,
  `max_weight` decimal(10,2) NOT NULL,
  `max_weight_limit` decimal(10,2) NOT NULL,
  `base_charge` decimal(12,2) NOT NULL,
  `additional_weight` decimal(10,2) NOT NULL DEFAULT 1.00,
  `additional_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transit_days` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `origin_destination_tariffs_service_type_id_foreign` (`service_type_id`),
  KEY `origin_destination_tariffs_origin_state_id_foreign` (`origin_state_id`),
  KEY `origin_destination_tariffs_origin_city_id_foreign` (`origin_city_id`),
  KEY `origin_destination_tariffs_destination_state_id_foreign` (`destination_state_id`),
  KEY `origin_destination_tariffs_destination_city_id_foreign` (`destination_city_id`),
  CONSTRAINT `origin_destination_tariffs_destination_city_id_foreign` FOREIGN KEY (`destination_city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `origin_destination_tariffs_destination_state_id_foreign` FOREIGN KEY (`destination_state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE,
  CONSTRAINT `origin_destination_tariffs_origin_city_id_foreign` FOREIGN KEY (`origin_city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `origin_destination_tariffs_origin_state_id_foreign` FOREIGN KEY (`origin_state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE,
  CONSTRAINT `origin_destination_tariffs_service_type_id_foreign` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `outlets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `outlets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hub_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outlets_code_unique` (`code`),
  KEY `outlets_hub_id_foreign` (`hub_id`),
  CONSTRAINT `outlets_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rider_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rider_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rider_id` bigint(20) unsigned NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `recorded_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rider_locations_rider_id_unique` (`rider_id`),
  CONSTRAINT `rider_locations_rider_id_foreign` FOREIGN KEY (`rider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `routes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `hub_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `routes_code_unique` (`code`),
  KEY `routes_hub_id_foreign` (`hub_id`),
  CONSTRAINT `routes_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scan_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scan_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `handled_by` bigint(20) unsigned DEFAULT NULL,
  `hub_id` bigint(20) unsigned DEFAULT NULL,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `scanned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_events_shipment_id_foreign` (`shipment_id`),
  KEY `scan_events_handled_by_foreign` (`handled_by`),
  KEY `scan_events_hub_id_foreign` (`hub_id`),
  KEY `scan_events_outlet_id_foreign` (`outlet_id`),
  CONSTRAINT `scan_events_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scan_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scan_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scan_statuses_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `billing_model` varchar(255) DEFAULT NULL,
  `route_type` enum('domestic','international') NOT NULL DEFAULT 'domestic',
  `trade_direction` enum('import','export','cross_trade') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_types_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `invoice_header` text DEFAULT NULL,
  `invoice_footer` text DEFAULT NULL,
  `color_primary` varchar(255) DEFAULT NULL,
  `color_secondary` varchar(255) DEFAULT NULL,
  `login_design` varchar(255) NOT NULL DEFAULT 'route',
  `supported_billing_models` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_billing_models`)),
  `vat_percentage` decimal(5,2) DEFAULT NULL,
  `volumetric_divisor` int(10) unsigned NOT NULL DEFAULT 5000,
  `currency` varchar(3) DEFAULT NULL,
  `waybill_thermal_size` varchar(255) DEFAULT NULL,
  `waybill_show_qr` tinyint(1) NOT NULL DEFAULT 1,
  `operating_regions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operating_regions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_number` varchar(255) NOT NULL,
  `client_user_id` bigint(20) unsigned DEFAULT NULL,
  `api_client_id` bigint(20) unsigned DEFAULT NULL,
  `service_type_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_type` enum('domestic','international','third_party') DEFAULT NULL,
  `origin_address` varchar(255) NOT NULL,
  `origin_zone_id` bigint(20) unsigned DEFAULT NULL,
  `origin_city_id` bigint(20) unsigned DEFAULT NULL,
  `origin_district_id` bigint(20) unsigned DEFAULT NULL,
  `destination_address` varchar(255) NOT NULL,
  `destination_zone_id` bigint(20) unsigned DEFAULT NULL,
  `destination_city_id` bigint(20) unsigned DEFAULT NULL,
  `destination_district_id` bigint(20) unsigned DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `weight_kg` decimal(8,2) DEFAULT NULL,
  `length_cm` decimal(8,2) DEFAULT NULL,
  `width_cm` decimal(8,2) DEFAULT NULL,
  `height_cm` decimal(8,2) DEFAULT NULL,
  `chargeable_weight_kg` decimal(8,2) DEFAULT NULL,
  `quantity` int(10) unsigned DEFAULT NULL,
  `carton_size` enum('small','medium','large') DEFAULT NULL,
  `base_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `surcharge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `onforwarding_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `insurance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_cod` tinyint(1) NOT NULL DEFAULT 0,
  `cod_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cod_remitted_at` timestamp NULL DEFAULT NULL,
  `current_status` varchar(255) NOT NULL DEFAULT 'booked',
  `assigned_rider_id` bigint(20) unsigned DEFAULT NULL,
  `current_hub_id` bigint(20) unsigned DEFAULT NULL,
  `current_outlet_id` bigint(20) unsigned DEFAULT NULL,
  `origin_hub_id` bigint(20) unsigned DEFAULT NULL,
  `destination_hub_id` bigint(20) unsigned DEFAULT NULL,
  `sla_breached` tinyint(1) NOT NULL DEFAULT 0,
  `promised_delivery_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipments_tracking_number_unique` (`tracking_number`),
  KEY `shipments_client_user_id_foreign` (`client_user_id`),
  KEY `shipments_api_client_id_foreign` (`api_client_id`),
  KEY `shipments_origin_zone_id_foreign` (`origin_zone_id`),
  KEY `shipments_destination_zone_id_foreign` (`destination_zone_id`),
  KEY `shipments_assigned_rider_id_foreign` (`assigned_rider_id`),
  KEY `shipments_current_hub_id_foreign` (`current_hub_id`),
  KEY `shipments_current_outlet_id_foreign` (`current_outlet_id`),
  KEY `shipments_origin_city_id_foreign` (`origin_city_id`),
  KEY `shipments_destination_city_id_foreign` (`destination_city_id`),
  KEY `shipments_origin_hub_id_foreign` (`origin_hub_id`),
  KEY `shipments_destination_hub_id_foreign` (`destination_hub_id`),
  KEY `shipments_origin_district_id_foreign` (`origin_district_id`),
  KEY `shipments_destination_district_id_foreign` (`destination_district_id`),
  KEY `shipments_service_type_id_foreign` (`service_type_id`),
  CONSTRAINT `shipments_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_assigned_rider_id_foreign` FOREIGN KEY (`assigned_rider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_client_user_id_foreign` FOREIGN KEY (`client_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_current_hub_id_foreign` FOREIGN KEY (`current_hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_current_outlet_id_foreign` FOREIGN KEY (`current_outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_destination_city_id_foreign` FOREIGN KEY (`destination_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_destination_district_id_foreign` FOREIGN KEY (`destination_district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_destination_hub_id_foreign` FOREIGN KEY (`destination_hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_destination_zone_id_foreign` FOREIGN KEY (`destination_zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_origin_city_id_foreign` FOREIGN KEY (`origin_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_origin_district_id_foreign` FOREIGN KEY (`origin_district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_origin_hub_id_foreign` FOREIGN KEY (`origin_hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_origin_zone_id_foreign` FOREIGN KEY (`origin_zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_service_type_id_foreign` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `standard_billing_tariffs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `standard_billing_tariffs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_type_id` bigint(20) unsigned NOT NULL,
  `min_weight` decimal(10,2) NOT NULL,
  `max_weight` decimal(10,2) NOT NULL,
  `max_weight_limit` decimal(10,2) DEFAULT NULL,
  `additional_weight` decimal(10,2) NOT NULL DEFAULT 1.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `standard_billing_tariffs_service_type_id_foreign` (`service_type_id`),
  CONSTRAINT `standard_billing_tariffs_service_type_id_foreign` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint(20) unsigned NOT NULL,
  `territory_id` bigint(20) unsigned DEFAULT NULL,
  `has_airport` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `short_code` varchar(10) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `states_country_id_foreign` (`country_id`),
  KEY `states_territory_id_foreign` (`territory_id`),
  CONSTRAINT `states_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `states_territory_id_foreign` FOREIGN KEY (`territory_id`) REFERENCES `territories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tariff_zone_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tariff_zone_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tariff_id` bigint(20) unsigned NOT NULL,
  `zone_id` bigint(20) unsigned NOT NULL,
  `charge` decimal(12,2) NOT NULL,
  `additional_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `transit_days` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tariff_zone_prices_tariff_id_zone_id_unique` (`tariff_id`,`zone_id`),
  KEY `tariff_zone_prices_zone_id_foreign` (`zone_id`),
  CONSTRAINT `tariff_zone_prices_tariff_id_foreign` FOREIGN KEY (`tariff_id`) REFERENCES `standard_billing_tariffs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tariff_zone_prices_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `territories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `territories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `territories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `third_party_country_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `third_party_country_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_a_id` bigint(20) unsigned NOT NULL,
  `country_b_id` bigint(20) unsigned NOT NULL,
  `zone_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `third_party_country_mappings_country_a_id_country_b_id_unique` (`country_a_id`,`country_b_id`),
  KEY `third_party_country_mappings_country_b_id_foreign` (`country_b_id`),
  KEY `third_party_country_mappings_zone_id_foreign` (`zone_id`),
  CONSTRAINT `third_party_country_mappings_country_a_id_foreign` FOREIGN KEY (`country_a_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `third_party_country_mappings_country_b_id_foreign` FOREIGN KEY (`country_b_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `third_party_country_mappings_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hub_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_code_unique` (`code`),
  KEY `units_hub_id_foreign` (`hub_id`),
  CONSTRAINT `units_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_status_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_status_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `from_status` varchar(255) NOT NULL,
  `to_status` varchar(255) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_status_audits_user_id_foreign` (`user_id`),
  KEY `user_status_audits_changed_by_foreign` (`changed_by`),
  CONSTRAINT `user_status_audits_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_status_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `user_type` enum('staff','rider','client') NOT NULL DEFAULT 'staff',
  `region_id` bigint(20) unsigned DEFAULT NULL,
  `hub_id` bigint(20) unsigned DEFAULT NULL,
  `outlet_id` bigint(20) unsigned DEFAULT NULL,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `account_status` enum('active','suspended','locked','terminated') NOT NULL DEFAULT 'active',
  `status_reason` varchar(255) DEFAULT NULL,
  `status_changed_at` timestamp NULL DEFAULT NULL,
  `status_changed_by` bigint(20) unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `date_joined` date DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','intern') DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_staff_id_unique` (`staff_id`),
  KEY `users_hub_id_foreign` (`hub_id`),
  KEY `users_status_changed_by_foreign` (`status_changed_by`),
  KEY `users_region_id_foreign` (`region_id`),
  KEY `users_outlet_id_foreign` (`outlet_id`),
  KEY `users_unit_id_foreign` (`unit_id`),
  CONSTRAINT `users_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_status_changed_by_foreign` FOREIGN KEY (`status_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_wallet_id` bigint(20) unsigned NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_transactions_client_wallet_id_foreign` (`client_wallet_id`),
  CONSTRAINT `wallet_transactions_client_wallet_id_foreign` FOREIGN KEY (`client_wallet_id`) REFERENCES `client_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `api_client_id` bigint(20) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_subscriptions_api_client_id_foreign` (`api_client_id`),
  CONSTRAINT `webhook_subscriptions_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zone_country_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `zone_country_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_a_id` bigint(20) unsigned DEFAULT NULL,
  `country_b_id` bigint(20) unsigned NOT NULL,
  `zone_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zone_country_mappings_country_id_unique` (`country_b_id`),
  KEY `zone_country_mappings_zone_id_foreign` (`zone_id`),
  KEY `zone_country_mappings_country_a_id_foreign` (`country_a_id`),
  CONSTRAINT `zone_country_mappings_country_a_id_foreign` FOREIGN KEY (`country_a_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zone_country_mappings_country_id_foreign` FOREIGN KEY (`country_b_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zone_country_mappings_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zone_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `zone_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `state_a_id` bigint(20) unsigned NOT NULL,
  `state_b_id` bigint(20) unsigned NOT NULL,
  `zone_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zone_mappings_state_a_id_state_b_id_unique` (`state_a_id`,`state_b_id`),
  KEY `zone_mappings_state_b_id_foreign` (`state_b_id`),
  KEY `zone_mappings_zone_id_foreign` (`zone_id`),
  CONSTRAINT `zone_mappings_state_a_id_foreign` FOREIGN KEY (`state_a_id`) REFERENCES `states` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zone_mappings_state_b_id_foreign` FOREIGN KEY (`state_b_id`) REFERENCES `states` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zone_mappings_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `applies_domestic` tinyint(1) NOT NULL DEFAULT 0,
  `applies_international` tinyint(1) NOT NULL DEFAULT 0,
  `tier` enum('A','B','C','D','E','F','international') DEFAULT NULL,
  `coverage_description` varchar(255) DEFAULT NULL,
  `geofence` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`geofence`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_01_01_000001_create_api_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_01_01_000002_create_ip_whitelists_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_01_01_000003_add_user_type_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_01_01_000004_create_api_access_denials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_01_02_000001_create_hubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_01_02_000002_create_zones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_01_02_000003_create_rate_cards_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_01_02_000004_create_zone_rate_matrix_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_01_02_000005_create_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_01_02_000006_create_scan_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_03_000001_add_cod_fields_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_03_000002_create_rider_locations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_01_03_000003_create_wallet_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_01_03_000004_create_webhook_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_01_04_000001_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_01_04_000002_add_invoice_fields_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_01_04_000003_create_scan_statuses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_01_05_000001_create_client_billing_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_01_05_000002_add_discount_amount_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_01_06_000001_add_access_scope_and_account_status_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_01_06_000002_create_user_status_audits_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_01_07_000001_create_regions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_01_07_000002_add_region_id_to_hubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_01_07_000003_add_region_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_01_08_000001_create_outlets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_01_08_000002_add_outlet_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_01_09_000001_add_current_outlet_id_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_01_09_000002_add_outlet_id_to_scan_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_01_09_000003_create_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_01_09_000004_add_unit_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_01_10_000001_add_staff_profile_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_01_11_000001_create_countries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_01_11_000002_create_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_01_11_000003_create_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_01_11_000004_add_city_id_to_hubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_01_12_000001_add_city_ids_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_01_13_000001_add_short_code_to_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_01_13_000002_add_codes_to_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_01_13_000003_create_districts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_01_14_000001_add_title_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_01_15_000001_create_hub_state_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_01_15_000002_add_origin_hub_id_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_01_16_000001_add_destination_hub_id_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_01_17_000001_add_operational_hub_id_to_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_01_18_000001_create_onforwarding_classifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_01_18_000002_add_onforwarding_classification_to_cities_and_districts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_01_18_000003_add_district_and_onforwarding_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_01_19_000001_add_postal_code_to_states_cities_districts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_01_20_000001_add_tier_to_zones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_01_21_000001_add_type_to_zones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_01_22_000001_convert_rate_cards_billing_model_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_01_22_000002_create_zone_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_01_22_000003_create_zone_weight_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_01_22_000004_add_quantity_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_01_23_000001_recreate_zone_mappings_as_state_pairs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_01_23_000002_convert_zone_weight_rates_to_single_zone',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_01_24_000001_make_zone_mappings_zone_id_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_01_24_000002_create_zone_country_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_01_25_000001_add_login_design_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_01_26_000001_create_territories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_01_26_000002_add_territory_and_airport_to_states_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_01_26_000003_create_routes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_01_27_000001_create_carton_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_01_28_000001_drop_billing_model_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_01_28_000002_add_supported_billing_models_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_01_29_000001_create_service_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_01_29_000002_convert_shipments_service_type_to_fk',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_01_30_000001_add_billing_model_to_service_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_01_30_000002_create_standard_billing_tariff_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_01_30_000003_add_shipping_type_to_shipments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_01_31_000001_add_route_type_to_service_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_02_01_000001_create_additional_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_02_02_000001_restructure_additional_services_with_options',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_02_03_000001_add_continent_and_country_region_to_countries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_02_04_000001_add_country_a_id_to_zone_country_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_02_05_000001_replace_zone_type_with_applies_flags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_02_06_000001_drop_hub_id_from_zones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_02_07_000001_add_charge_type_to_additional_service_options_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_02_08_000001_add_reverse_shipment_fields_to_additional_service_options_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_02_09_000001_make_service_types_route_type_required',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_02_10_000001_add_trade_direction_to_service_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_02_11_000001_create_third_party_country_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_02_11_000002_add_third_party_to_shipments_shipping_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_02_11_000003_add_third_party_to_service_types_route_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_02_12_000001_move_cross_trade_under_trade_direction',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_02_13_000001_add_volumetric_divisor_to_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_02_14_000001_add_kind_to_additional_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_02_14_000002_add_is_vatable_to_additional_service_options_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_02_15_000001_add_max_weight_limit_to_standard_billing_tariffs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_02_16_000001_create_origin_destination_tariffs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_02_17_000001_swap_max_weight_and_max_weight_limit_meaning',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_07_15_224915_create_permission_tables',1);
