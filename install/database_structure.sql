-- Create syntax for TABLE 'accounts'
CREATE TABLE `accounts` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'api_errors'
CREATE TABLE `api_errors` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `error` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'metrics_daily'
CREATE TABLE `metrics_daily` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `profile_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `bounce_rate` double DEFAULT NULL,
  `avg_time_on_site` double DEFAULT NULL,
  `avg_page_views` double DEFAULT NULL,
  `percent_new_visits` double DEFAULT NULL,
  `avg_page_load_time` double DEFAULT NULL,
  `avg_server_response_time` double DEFAULT NULL,
  `avg_dom_content_loaded_time` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'metrics_monthly'
CREATE TABLE `metrics_monthly` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `profile_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `visits` int(11) DEFAULT NULL,
  `unique_visits` int(11) DEFAULT NULL,
  `bounces` int(11) DEFAULT NULL,
  `bounce_rate` double DEFAULT NULL,
  `avg_time_on_site` double DEFAULT NULL,
  `avg_page_views` double DEFAULT NULL,
  `percent_new_visits` double DEFAULT NULL,
  `avg_page_load_time` double DEFAULT NULL,
  `avg_server_response_time` double DEFAULT NULL,
  `avg_dom_content_loaded_time` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'options'
CREATE TABLE `options` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- Create syntax for TABLE 'profiles'
CREATE TABLE `profiles` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `web_property_id` varchar(255) NOT NULL DEFAULT '',
  `website_url` text,
  `type` varchar(255) DEFAULT NULL,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;