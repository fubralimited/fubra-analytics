-- Create syntax for TABLE 'accounts'
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'analytics_mobile'
CREATE TABLE IF NOT EXISTS `analytics_mobile` (
  `id` int(11) unsigned NOT NULL,
  `date` date NOT NULL,
  `profile_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT NULL,
  `visitors` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `avg_views_per_visit` double DEFAULT NULL,
  `avg_time_on_site` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'analytics_tablet'
CREATE TABLE IF NOT EXISTS `analytics_tablet` (
  `id` int(11) unsigned NOT NULL,
  `date` date NOT NULL,
  `profile_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT NULL,
  `visitors` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `avg_views_per_visit` double DEFAULT NULL,
  `avg_time_on_site` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'analytics_total'
CREATE TABLE IF NOT EXISTS `analytics_total` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `profile_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT NULL,
  `visitors` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `avg_views_per_visit` double DEFAULT NULL,
  `avg_time_on_site` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'api_errors'
CREATE TABLE IF NOT EXISTS `api_errors` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `error` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'groups'
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'options'
CREATE TABLE IF NOT EXISTS `options` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'profiles'
CREATE TABLE IF NOT EXISTS `profiles` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `web_property_id` varchar(255) NOT NULL DEFAULT '',
  `website_url` text,
  `type` varchar(255) DEFAULT NULL,
  `ignored` tinyint(1) NOT NULL DEFAULT '0',
  `group` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;