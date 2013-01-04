<?php
/**
 * @package     Joomlacli.plugins
 * @subpackage  Core
 * @copyright   Copyright 2012 joomlacli. All rights re-served.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Model for github integration
 * 
 * @package  Joomlacli.plugins
 * @since    0.1
 */
class CommandCoreModelGithub extends JModelBase
{
	/**
	 * Github Tags info
	 * 
	 * @param   string  $owner       String name of github owner
	 * @param   string  $repository  String name of github repository
	 * 
	 * @return  mixed
	 * 
	 * @since   0.1
	 */
	public function tags($owner = 'joomla', $repository = 'joomla-cms')
	{
		$url = sprintf('https://api.github.com/repos/%s/%s/tags', $owner, $repository);

		$tmpConfig = new JRegistry;
		$tmpConfig->set('cache_path', JPATH_ETC . DIRECTORY_SEPARATOR . 'cache');

		// 1 day cache
		$tmpConfig->set('cachetime', 60 * 60 * 24);
		$tmpConfig->set('caching', 1);
		$tmpConfig->set('cache_handler', 'file');

		JFactory::$config = $tmpConfig;

		$cache = JCache::getInstance('output', $tmpConfig);
		$cache->setCaching(true);
		$cache_id = md5($url);
		$cache_group = 'github';

		$tags = $cache->get($cache_id, $cache_group);

		if ($tags == false)
		{
			$options = new JRegistry;
			$options->set('curl.certpath', JPATH_ETC . '/transport/cacert.pem');
			$http = JHttpFactory::getHttp($options);
			$response = $http->get($url);

			if (200 != $response->code)
			{
				Throw new RuntimeException(JText::sprintf('CORE_ERROR_CANT_SERVER_CONNECT',$url));
				return false;
			}

			if (!empty($response->body))
			{
				$logEntry = new JLogEntry(sprintf('request to github %s', $url), JLog::INFO, 'GITHUB');
				JLog::add($logEntry);
				$jsonData = json_decode($response->body);
				$gitTags = array();
				foreach ($jsonData as $gitTag)
				{
					$gitTag->file = $owner . '-' . $repository . '-' . $gitTag->name . '-0-g' . substr($gitTag->commit->sha, 0, 7) . '.zip';
					$gitTags[$gitTag->name] = $gitTag;
				}

				$cache->store(json_encode($gitTags), $cache_id, $cache_group);

				$logEntry = new JLogEntry(sprintf('store github result in cache', $url), JLog::INFO, 'GITHUB');
				JLog::add($logEntry);
			}

			$tags = json_encode($gitTags);
		}
		JFactory::$config = null;
		return json_decode($tags);
	}
}
