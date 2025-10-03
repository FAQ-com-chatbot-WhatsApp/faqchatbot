/**
 *  SQL executado na instalacao da extensao
 */

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for #__noboss_faq
-- ----------------------------
DROP TABLE IF EXISTS `#__noboss_faq`;
CREATE TABLE `#__noboss_faq` (
  `id_faq` int(11) NOT NULL AUTO_INCREMENT,
  `id_category` int(11) NOT NULL,
  `id_faqs_group` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `state` tinyint(3) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `ordering` int(11) NOT NULL,
  `created_by` int(10) NOT NULL,
  `modified_by` int(10) DEFAULT NULL,
  `checked_out` int(10) DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id_faq`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8;


SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for #__noboss_faq_group
-- ----------------------------
DROP TABLE IF EXISTS `#__noboss_faq_group`;
CREATE TABLE `#__noboss_faq_group` (
  `id_faqs_group` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_faqs_group` varchar(100) NOT NULL,
  `description_faqs_group` varchar(150) NOT NULL DEFAULT '',
  `id_module_faqs_display` int(10) unsigned NOT NULL DEFAULT '0',
  `config_module_faqs_display` text NOT NULL,
  `content_display_registered` tinyint(3) DEFAULT '1',
  `content_display_articles` tinyint(3) DEFAULT '0', 
  `content_categories_articles` text DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `language` varchar(7) NOT NULL DEFAULT '',
  `created_by` int(10) unsigned NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified_by` int(10) unsigned NOT NULL DEFAULT '0',
  `modified` datetime DEFAULT NULL,
  `checked_out_time` datetime DEFAULT NULL,
  `checked_out` int(10) DEFAULT NULL,
  `ordering` int(10) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (`id_faqs_group`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;


SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for #__noboss_faq_rel
-- ----------------------------
DROP TABLE IF EXISTS `#__noboss_faq_rel`;
CREATE TABLE `#__noboss_faq_rel` (
  `id_faqs_grupo` int(10) unsigned NOT NULL DEFAULT '0',
  `id_modulo` int(10) unsigned NOT NULL DEFAULT '0',
  `id_faq` int(10) unsigned NOT NULL DEFAULT '0',
  `ordering` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
