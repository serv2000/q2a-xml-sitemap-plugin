<?php
/*
	Plugin Name:IMPROVED XML Sitemap
	Plugin URI:
	Plugin Description: Generates sitemap.xml file for submission to search engines
	Plugin Version: 1.0
	Plugin Date: 19/feb/2018
	Plugin Author: abdullah shalaan
	Plugin Author URI: https://github.com/serv2000/q2a-xml-sitemap-plugin
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.5
	Plugin Update Check URI:https://github.com/serv2000/q2a-xml-sitemap-plugin
*/

 
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('page', 'qa-xml-sitemap.php', 'qa_xml_sitemap', 'XML Sitemap');
