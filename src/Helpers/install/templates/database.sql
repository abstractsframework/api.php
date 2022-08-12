SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abstracts`
--

-- --------------------------------------------------------

--
-- Table structure for table `abstracts`
--

CREATE TABLE `abstracts` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `key` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `component_module` text DEFAULT NULL,
  `component_group` text DEFAULT NULL,
  `component_user` text DEFAULT NULL,
  `component_language` text DEFAULT NULL,
  `component_page` text DEFAULT NULL,
  `component_media` text DEFAULT NULL,
  `database_engine` text NOT NULL,
  `database_collation` text NOT NULL,
  `data_sortable` text DEFAULT NULL,
  `template` text DEFAULT NULL,
  `icon` text DEFAULT NULL,
  `category` text DEFAULT NULL,
  `subject` text DEFAULT NULL,
  `subject_icon` text DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module module';

-- --------------------------------------------------------

--
-- Table structure for table `reference`
--

CREATE TABLE `reference` (
  `id` int(11) NOT NULL,
  `label` varchar(50) NOT NULL,
  `key` varchar(50) DEFAULT NULL,
  `type` text NOT NULL,
  `module` text NOT NULL,
  `reference` text NOT NULL,
  `placeholder` varchar(100) DEFAULT NULL,
  `help` varchar(200) DEFAULT NULL,
  `require` text DEFAULT NULL,
  `readonly` text DEFAULT NULL,
  `disable` text DEFAULT NULL,
  `hidden` text DEFAULT NULL,
  `date_format` text DEFAULT NULL,
  `color_format` text DEFAULT NULL,
  `input_multiple_format` text DEFAULT NULL,
  `default_value` text DEFAULT NULL,
  `default_switch` text DEFAULT NULL,
  `input_option` text DEFAULT NULL,
  `input_option_static_value` text DEFAULT NULL,
  `input_option_dynamic_module` text DEFAULT NULL,
  `input_option_dynamic_value_key` varchar(200) DEFAULT NULL,
  `input_option_dynamic_label_key` varchar(200) DEFAULT NULL,
  `input_multiple_type` text DEFAULT NULL,
  `image_width` int(11) DEFAULT NULL,
  `image_height` int(11) DEFAULT NULL,
  `image_width_ratio` int(11) DEFAULT NULL,
  `image_height_ratio` int(11) DEFAULT NULL,
  `image_quality` int(11) DEFAULT 75,
  `image_thumbnail` tinyint(1) DEFAULT NULL,
  `image_thumbnail_aspectratio` tinyint(1) DEFAULT 1,
  `image_thumbnail_quality` int(11) DEFAULT 75,
  `image_thumbnail_width` int(11) DEFAULT 200,
  `image_thumbnail_height` int(11) DEFAULT 200,
  `image_large` tinyint(1) DEFAULT NULL,
  `image_large_aspectratio` tinyint(1) DEFAULT 1,
  `image_large_quality` int(11) DEFAULT 75,
  `image_large_width` int(11) DEFAULT 400,
  `image_large_height` int(11) DEFAULT 400,
  `upload_folder` varchar(200) DEFAULT NULL,
  `file_type` text DEFAULT NULL,
  `file_lock` tinyint(1) DEFAULT NULL,
  `validate_number` text DEFAULT NULL,
  `validate_number_min` int(11) DEFAULT NULL,
  `validate_number_max` int(11) DEFAULT NULL,
  `validate_decimal` text DEFAULT NULL,
  `validate_decimal_min` decimal(10,0) DEFAULT NULL,
  `validate_decimal_max` decimal(10,0) DEFAULT NULL,
  `validate_datetime` text DEFAULT NULL,
  `validate_datetime_min` datetime DEFAULT NULL,
  `validate_datetime_max` datetime DEFAULT NULL,
  `validate_string_min` int(11) DEFAULT NULL,
  `validate_string_max` int(11) DEFAULT NULL,
  `validate_email` text DEFAULT NULL,
  `validate_password` text DEFAULT NULL,
  `validate_password_equal_to` text DEFAULT NULL,
  `validate_url` text DEFAULT NULL,
  `validate_unique` text DEFAULT NULL,
  `validate_no_spaces` text DEFAULT NULL,
  `validate_no_special_characters` text DEFAULT NULL,
  `validate_no_digit` text DEFAULT NULL,
  `validate_uppercase_only` text DEFAULT NULL,
  `validate_lowercase_only` text DEFAULT NULL,
  `validate_key` text DEFAULT NULL,
  `grid_width` varchar(200) DEFAULT NULL,
  `alignment` text DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module References';

-- --------------------------------------------------------

--
-- Table structure for table `api`
--

CREATE TABLE `api` (
  `id` int(11) NOT NULL,
  `title` varchar(240) NOT NULL,
  `key` varchar(240) DEFAULT NULL,
  `secret` varchar(240) DEFAULT NULL,
  `scope` text DEFAULT NULL,
  `type` enum('public','private') NOT NULL DEFAULT 'private',
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module API';

--
-- Dumping data for table `api`
--

INSERT INTO `api` (`id`, `title`, `key`, `secret`, `scope`, `type`, `create_at`, `active`, `user_id`) VALUES
(1, 'Default', '{{api_key}}', '{{api_secret}}', 'a:23:{i:0;s:1:\"1\";i:1;s:1:\"2\";i:2;s:1:\"3\";i:3;s:1:\"5\";i:4;s:1:\"6\";i:5;s:1:\"7\";i:6;s:1:\"8\";i:7;s:1:\"9\";i:8;s:2:\"10\";i:9;s:2:\"11\";i:10;s:2:\"12\";i:11;s:2:\"13\";i:12;s:2:\"14\";i:13;s:2:\"15\";i:14;s:2:\"16\";i:15;s:2:\"17\";i:16;s:2:\"18\";i:17;s:2:\"19\";i:18;s:2:\"20\";i:19;s:2:\"61\";i:20;s:2:\"62\";i:21;s:2:\"63\";i:22;s:2:\"71\";}', 'private', NOW(), 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `audience`
--

CREATE TABLE `audience` (
  `id` int(11) NOT NULL,
  `action` text DEFAULT NULL,
  `url` text DEFAULT NULL,
  `platform` text DEFAULT NULL,
  `browser` text DEFAULT NULL,
  `os` text DEFAULT NULL,
  `os_version` text DEFAULT NULL,
  `model` text DEFAULT NULL,
  `manufacturer` text DEFAULT NULL,
  `uuid` varchar(1024) DEFAULT NULL,
  `user_agent` varchar(1024) DEFAULT NULL,
  `is_mobile` text DEFAULT NULL,
  `is_native` text DEFAULT NULL,
  `is_virtual` text DEFAULT NULL,
  `app_name` text DEFAULT NULL,
  `app_id` text DEFAULT NULL,
  `app_version` text DEFAULT NULL,
  `app_build` text DEFAULT NULL,
  `ip` varchar(320) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `module_id` varchar(100) NOT NULL,
  `module_key` varchar(240) DEFAULT NULL,
  `module_value` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Audience';

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `value` text NOT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Config';

-- --------------------------------------------------------

--
-- Table structure for table `connect`
--

CREATE TABLE `connect` (
  `id` int(11) NOT NULL,
  `app` varchar(128) NOT NULL,
  `external_id` varchar(1024) DEFAULT NULL,
  `email` varchar(320) DEFAULT NULL,
  `name` text DEFAULT NULL,
  `first_name` text DEFAULT NULL,
  `last_name` text DEFAULT NULL,
  `picture` text DEFAULT NULL,
  `refresh_token` varchar(1024) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Connect';

-- --------------------------------------------------------

--
-- Table structure for table `control`
--

CREATE TABLE `control` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `rules` text DEFAULT NULL,
  `behaviors` varchar(25) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `module_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Control';

--
-- Dumping data for table `control`
--

INSERT INTO `control` (`id`, `user`, `rules`, `behaviors`, `create_at`, `active`, `module_id`, `group_id`, `user_id`) VALUES
(1, 1, '', 'view,create,update,delete', NOW(), 1, 1, 1, 1),
(2, 1, '', 'view,create,update,delete', NOW(), 1, 2, 1, 1),
(3, 1, '', 'view,create,update,delete', NOW(), 1, 3, 1, 1),
(4, 1, '', 'view,create,update,delete', NOW(), 1, 4, 1, 1),
(5, 1, '', 'view,create,update,delete', NOW(), 1, 5, 1, 1),
(6, 1, '', 'view,create,update,delete', NOW(), 1, 6, 1, 1),
(7, 1, '', 'view,create,update,delete', NOW(), 1, 7, 1, 1),
(8, 1, '', 'view,create,update,delete', NOW(), 1, 8, 1, 1),
(9, 1, '', 'view,create,update,delete', NOW(), 1, 9, 1, 1),
(10, 1, '', 'view,create,update,delete', NOW(), 1, 10, 1, 1),
(11, 1, '', 'view,create,update,delete', NOW(), 1, 11, 1, 1),
(12, 1, '', 'view,create,update,delete', NOW(), 1, 12, 1, 1),
(13, 1, '', 'view,create,update,delete', NOW(), 1, 13, 1, 1),
(14, 1, '', 'view,create,update,delete', NOW(), 1, 14, 1, 1),
(15, 1, '', 'view,create,update,delete', NOW(), 1, 15, 1, 1),
(16, 1, '', 'view,create,update,delete', NOW(), 1, 16, 1, 1),
(17, 1, '', 'view,create,update,delete', NOW(), 1, 17, 1, 1),
(18, 1, '', 'view,create,update,delete', NOW(), 1, 18, 1, 1),
(19, 1, '', 'view,create,update,delete', NOW(), 1, 19, 1, 1),
(20, 1, '', 'view,create,update,delete', NOW(), 1, 20, 1, 1),
(21, 1, '', 'view,create,update,delete', NOW(), 1, 61, 1, 1),
(22, 1, '', 'view,create,update,delete', NOW(), 1, 62, 1, 1),
(23, 1, '', 'view,create,update,delete', NOW(), 1, 63, 1, 1),
(24, 1, '', 'view,create,update,delete', NOW(), 1, 71, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `device`
--

CREATE TABLE `device` (
  `id` int(11) NOT NULL,
  `name` text DEFAULT NULL,
  `session` varchar(256) DEFAULT NULL,
  `token` varchar(1024) DEFAULT NULL,
  `platform` text DEFAULT NULL,
  `browser` text DEFAULT NULL,
  `os` text DEFAULT NULL,
  `os_version` text DEFAULT NULL,
  `model` text DEFAULT NULL,
  `manufacturer` text DEFAULT NULL,
  `uuid` varchar(1024) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_mobile` tinyint(1) DEFAULT 0,
  `is_native` tinyint(1) DEFAULT 0,
  `is_virtual` tinyint(1) DEFAULT 0,
  `app_name` varchar(250) DEFAULT NULL,
  `app_id` text DEFAULT NULL,
  `app_version` text DEFAULT NULL,
  `app_build` text DEFAULT NULL,
  `ip_recent` varchar(320) DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `expire_at` datetime DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Device';

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `controls` text NOT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`id`, `name`, `controls`, `order`, `create_at`, `active`, `user_id`) VALUES
(1, 'Creator', 'a:24:{i:0;a:3:{s:6:"module";s:1:"1";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:1;a:3:{s:6:"module";s:1:"2";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:2;a:3:{s:6:"module";s:1:"3";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:3;a:3:{s:6:"module";s:1:"4";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:4;a:3:{s:6:"module";s:1:"5";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:5;a:3:{s:6:"module";s:1:"6";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:6;a:3:{s:6:"module";s:1:"7";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:7;a:3:{s:6:"module";s:1:"8";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:8;a:3:{s:6:"module";s:1:"9";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:9;a:3:{s:6:"module";s:2:"10";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:10;a:3:{s:6:"module";s:2:"11";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:11;a:3:{s:6:"module";s:2:"12";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:12;a:3:{s:6:"module";s:2:"13";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:13;a:3:{s:6:"module";s:2:"14";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:14;a:3:{s:6:"module";s:2:"15";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:15;a:3:{s:6:"module";s:2:"16";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:16;a:3:{s:6:"module";s:2:"17";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:17;a:3:{s:6:"module";s:2:"18";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:18;a:3:{s:6:"module";s:2:"19";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:19;a:3:{s:6:"module";s:2:"20";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:20;a:3:{s:6:"module";s:2:"61";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:21;a:3:{s:6:"module";s:2:"62";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:22;a:3:{s:6:"module";s:2:"63";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}i:23;a:3:{s:6:"module";s:2:"71";s:5:"rules";s:0:"";s:9:"behaviors";s:25:"view,create,update,delete";}}', 1, NOW(), 1, 1),
(2, 'Super Administrator', '', 2, NOW(), 1, 1),
(3, 'Administrator', '', 2, NOW(), 1, 1),
(4, 'User', '', 2, NOW(), 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `inad`
--

CREATE TABLE `inad` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `unit_name` text NOT NULL,
  `redirect_url` text NOT NULL,
  `type` text NOT NULL,
  `text` text DEFAULT NULL,
  `image` text DEFAULT NULL,
  `video` text DEFAULT NULL,
  `sound` text DEFAULT NULL,
  `html` text DEFAULT NULL,
  `code` text DEFAULT NULL,
  `duration_type` text NOT NULL,
  `duration_start` datetime DEFAULT NULL,
  `duration_finish` datetime DEFAULT NULL,
  `quota_audiences` int(11) DEFAULT 0,
  `scope` text DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module In-ad';

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `short_name` varchar(5) NOT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `language`
--

INSERT INTO `language` (`id`, `name`, `short_name`, `order`, `create_at`, `active`, `user_id`) VALUES
(1, 'English', 'EN', 1, NOW(), 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lock`
--

CREATE TABLE `lock` (
  `id` int(50) NOT NULL,
  `hash` text NOT NULL,
  `content` text DEFAULT NULL,
  `ip` varchar(320) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Lock';

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `function` varchar(150) NOT NULL,
  `violation` enum('low','normal','risk','danger') NOT NULL,
  `content_hash` text NOT NULL,
  `link` text NOT NULL,
  `type` enum('frontend','backend') NOT NULL DEFAULT 'frontend',
  `ip` varchar(15) NOT NULL,
  `user_agent` text NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `module_id` varchar(100) NOT NULL,
  `module_key` varchar(240) NOT NULL,
  `module_value` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='	';

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `file` varchar(300) NOT NULL,
  `type` varchar(10) NOT NULL,
  `size` int(11) NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `module_id` varchar(100) NOT NULL,
  `module_key` varchar(240) NOT NULL,
  `module_value` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`id`, `user`, `group_id`, `create_at`, `active`, `user_id`) VALUES
(1, 1, 1, NOW(), 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `link` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `subject_icon` varchar(100) NOT NULL,
  `service` varchar(250) NOT NULL,
  `key` varchar(50) NOT NULL,
  `database_table` varchar(100) NOT NULL,
  `table_columns` text NOT NULL,
  `default_controls` text NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `page_template` text NOT NULL,
  `page_template_settings` text NOT NULL,
  `page_parent_link_key` text NOT NULL,
  `individual_page_parent_link` int(11) DEFAULT 0,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`id`, `name`, `link`, `description`, `icon`, `category`, `subject`, `subject_icon`, `key`, `database_table`, `service`, `page_template`, `individual_page_parent_link`, `page_template_settings`, `page_parent_link_key`, `default_controls`, `table_columns`, `order`, `create_at`, `authentication`, `active`, `page_id`, `user_id`) VALUES
(1, 'Abstracts', 'abstracts', '', 'si si-puzzle', 'System', 'Modules', 'si si-layers', 'abstracts', 'abstracts', 'abstracts', '', '', '', 0, '', '', 73, NOW(), 0, NULL, 1),
(2, 'Reference', 'reference', '', 'fa fa-database', 'System', 'Modules', 'si si-layers', 'reference', 'reference', 'reference', '', '', '', 0, '', '', 74, NOW(), 0, NULL, 1),
(3, 'Template', 'template', '', 'fa fa-list-ol', 'System', 'Modules', 'si si-layers', 'template', 'template', 'template', '', '', '', 0, '', 0, 75, NOW(), 0, NULL, 1),
(5, 'Module', 'module', '', 'si si-grid', 'System', 'Modules', 'si si-layers', 'module', 'module', 'module', '', '', '', 0, '', '', 72, NOW(), 0, NULL, 1),
(6, 'User', 'user', '', 'si si-user', 'Users', '', '', 'user', 'user', 'user', '', '', '', 0, 'view', 'N;', 59, NOW(), 0, NULL, 1),
(7, 'Group', 'group', '', 'si si-user', 'Users', '', '', 'group', 'group', 'group', '', '', '', 0, '', '', 60, NOW(), 0, NULL, 1),
(8, 'Language', 'language', '', 'si si-speech', 'System', '', '', 'language', 'language', 'language', '', '', '', 0, '', '', 69, NOW(), 0, NULL, 1),
(9, 'Page', 'page', '', 'si si-docs', 'Files', '', '', 'page', 'page', 'page', '', '', '', 0, 'view', '', 65, NOW(), 0, NULL, 1),
(10, 'Media', 'media', '', 'si si-picture', 'Files', '', '', 'media', 'media', 'media', '', '', '', 0, '', '', 66, NOW(), 0, NULL, 1),
(11, 'Log', 'log', '', 'si si-clock', 'Maintenance', '', '', 'log', 'log', 'log', '', '', '', 0, '', '', 68, NOW(), 0, NULL, 1),
(12, 'API', 'api', '', 'si si-share', 'System', '', '', 'api', 'api', 'api', '', '', '', 0, '', '', 70, NOW(), 0, NULL, 1),
(13, 'Lock', 'lock', '', 'si si-docs', 'Maintenance', '', '', 'lock', 'lock', 'lock', '', '', '', 0, '', '', 67, NOW(), 0, NULL, 1),
(14, 'Control', 'control', '', 'si si-shield', 'Users', '', '', 'control', 'control', 'control', '', '', '', 0, '', '', 61, NOW(), 0, NULL, 1),
(16, 'Connect', 'connect', '', 'fa fa-circle-thin', 'Users', '', '', 'connect', 'connect', 'connect', '', '', '', 0, '', '', 62, NOW(), 0, NULL, 1),
(15, 'Process', 'process', '', 'si si-bulb', 'System', '', '', 'process', 'process', 'process', '', '', '', 0, '', '', 71, NOW(), 0, NULL, 1),
(17, 'Notification', 'notification', '', 'si si-bell', 'Communication', '', '', 'notification', 'notification', 'notification', '', '', '', 0, '', '', 57, NOW(), 0, NULL, 1),
(18, 'Mail', 'mail', '', 'si si-envelope', 'Communication', '', '', 'mail', 'mail', 'mail', '', '', '', 0, '', '', 58, NOW(), 0, NULL, 1),
(19, 'Device', 'device', '', 'si si-screen-smartphone', 'Users', '', '', 'device', 'device', 'device', '', '', '', 0, 'view,create', 'N;', 63, NOW(), 0, NULL, 1),
(20, 'Audience', 'audience', '', 'si si-eye', 'Users', '', '', 'audience', 'audience', 'audience', '', '', '', 0, 'create', 'N;', 64, NOW(), 0, NULL, 1),
(71, 'In-ad', 'inad', '', 'si si-feed', 'Commerce', '', '', 'inad', 'inad', 'inad', '', '', '', 0, 'view,create', '', 56, NOW(), 0, NULL, 1),
(4, 'Config', 'config', '', 'si si-settings', 'System', '', '', 'config', 'config', 'config', '', '', '', 0, '', '', 76, NOW(), 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

CREATE TABLE `page` (
  `id` int(11) NOT NULL,
  `title` varchar(240) NOT NULL,
  `link` varchar(250) NOT NULL,
  `meta_title` varchar(240) NOT NULL,
  `meta_description` varchar(400) NOT NULL,
  `meta_keyword` text NOT NULL,
  `template` varchar(250) NOT NULL,
  `translate` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `module_id` varchar(100) NOT NULL,
  `module_key` varchar(100) NOT NULL,
  `module_value` varchar(240) NOT NULL,
  `language_id` int(11) DEFAULT 0,
  `language_short_name_parent_link` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `page`
--

INSERT INTO `page` (`id`, `title`, `link`, `meta_title`, `meta_description`, `meta_keyword`, `template`, `translate`, `module_key`, `module_value`, `create_at`, `active`, `module_id`, `user_id`, `language_id`, `language_short_name_parent_link`) VALUES
(1, 'Home', 'index', '', '', '', 'index.php', 0, '', '', NOW(), 1, '', 1, 1, 0),
(2, 'Connect', 'connect', '', '', '', 'connect.php', 0, '', '', NOW(), 1, '', 1, 1, 0),
(3, 'Redirect', 'redirect', '', '', '', 'redirect.php', 0, '', '', NOW(), 1, '', 1, 1, 0),
(4, 'Verify', 'verify', '', '', '', 'verify.php', 0, '', '', NOW(), 1, '', 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `gateway` text NOT NULL,
  `gateway_version` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `commision_method` text DEFAULT NULL,
  `commision` int(11) DEFAULT 0,
  `commision_vat` int(11) DEFAULT 0,
  `shipping_fee_currency` text DEFAULT NULL,
  `commision_fixed_currency` text DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Payment';

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `id` int(11) NOT NULL,
  `title` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `items` text DEFAULT NULL,
  `purchaser_user` text DEFAULT NULL,
  `purchaser_organization` text DEFAULT NULL,
  `purchaser_contact` text DEFAULT NULL,
  `operator_user` text DEFAULT NULL,
  `operator_organization` text DEFAULT NULL,
  `operator_contact` text DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Purchase';

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id` int(11) NOT NULL,
  `type` text NOT NULL,
  `payment_id` text NOT NULL,
  `method` text DEFAULT NULL,
  `purchase_id` text DEFAULT NULL,
  `charge_id` text DEFAULT NULL,
  `amount` float DEFAULT 0,
  `vat_amount` float DEFAULT 0,
  `amount_total` float DEFAULT 0,
  `currency` text NOT NULL,
  `status` text DEFAULT NULL,
  `document` text DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='table for Module Transaction';

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(200) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `nick_name` varchar(50) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `passcode` text DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `ndid_verified` tinyint(1) DEFAULT 0,
  `face_verified` tinyint(1) DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `user_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `email`, `name`, `last_name`, `nick_name`, `image`, `phone`, `passcode`, `email_verified`, `phone_verified`, `ndid_verified`, `face_verified`, `create_at`, `active`, `user_id`) VALUES
(1, '{{username}}', '{{password}}', '', '{{name}}', '', '', '', '', '', 1, 0, 0, 0, NOW(), 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abstracts`
--
ALTER TABLE `abstracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order` (`order`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reference`
--
ALTER TABLE `reference`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `order` (`order`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `api`
--
ALTER TABLE `api`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `audience`
--
ALTER TABLE `audience`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `connect`
--
ALTER TABLE `connect`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app` (`app`),
  ADD KEY `external_id` (`external_id`),
  ADD KEY `email` (`email`),
  ADD KEY `refresh_token` (`refresh_token`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `control`
--
ALTER TABLE `control`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `behaviors` (`behaviors`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `device`
--
ALTER TABLE `device`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session` (`session`),
  ADD KEY `token` (`token`),
  ADD KEY `uuid` (`uuid`),
  ADD KEY `app_name` (`app_name`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order` (`order`);

--
-- Indexes for table `inad`
--
ALTER TABLE `inad`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`id`),
  ADD KEY `short_name` (`short_name`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `order` (`order`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `lock`
--
ALTER TABLE `lock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `function` (`function`),
  ADD KEY `violation` (`violation`),
  ADD KEY `ip` (`ip`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `module_key` (`module_key`),
  ADD KEY `module_value` (`module_value`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file` (`file`(255)),
  ADD KEY `type` (`type`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `module_key` (`module_key`),
  ADD KEY `module_value` (`module_value`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `active` (`active`),
  ADD KEY `order` (`order`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `page_id` (`page_id`);

--
-- Indexes for table `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `link_2` (`link`),
  ADD KEY `link` (`link`),
  ADD KEY `meta_title` (`meta_title`),
  ADD KEY `meta_description` (`meta_description`(333)),
  ADD KEY `section` (`template`),
  ADD KEY `title` (`title`),
  ADD KEY `translate` (`translate`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `language_id` (`language_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `module_key` (`module_key`),
  ADD KEY `module_value` (`module_value`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `order` (`order`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_2` (`username`),
  ADD KEY `username` (`username`),
  ADD KEY `email` (`email`),
  ADD KEY `name` (`name`),
  ADD KEY `nick_name` (`nick_name`),
  ADD KEY `active` (`active`),
  ADD KEY `create_at` (`create_at`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abstracts`
--
ALTER TABLE `abstracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `reference`
--
ALTER TABLE `reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api`
--
ALTER TABLE `api`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audience`
--
ALTER TABLE `audience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `connect`
--
ALTER TABLE `connect`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `control`
--
ALTER TABLE `control`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `device`
--
ALTER TABLE `device`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inad`
--
ALTER TABLE `inad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lock`
--
ALTER TABLE `lock`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `page`
--
ALTER TABLE `page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1001;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
