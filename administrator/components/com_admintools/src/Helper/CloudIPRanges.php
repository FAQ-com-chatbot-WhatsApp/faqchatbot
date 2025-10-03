<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Helper;

use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Factory;
use Joomla\Http\HttpFactory;

final class CloudIPRanges
{
	/**
	 * How many seconds will we cache IP ranges we fetch from the Internet.
	 *
	 * @since   7.8.0
	 */
	private const CACHE_LIFETIME = 3600;

	/**
	 * The Joomla cache controller
	 *
	 * @since   7.8.0
	 */
	private static ?CallbackController $cacheController = null;

	private static ?ServerconfigmakerModel $serverconfigmakerModel = null;

	/**
	 * This constructor is private to prevent direct instantiation of the class.
	 *
	 * @return void
	 * @since   7.8.0
	 */
	private function __construct() {}

	/**
	 * Retrieves the IP ranges based on the specified type and conditions; null on error.
	 *
	 * Depending on the type, it fetches pre-defined IP ranges for supported services or returns null if no type is matched.
	 *
	 * Supported range types:
	 * - 'none' Returns an empty array.
	 * - 'custom' Configured IP ranges.
	 * - 'cloudflare' CloudFlare IP ranges.
	 * - 'sucuri' Sucuri IP ranges.
	 * - 'bunnycdn' BunnyCDN IP ranges.
	 *
	 * @param   string  $type   The type of IP range to retrieve. Supported types: 'none', 'custom', 'cloudflare', 'sucuri', 'bunnycdn'.
	 * @param   bool    $force  Whether to force refresh of the ranges.
	 *
	 * @return  array|null  An array of IP ranges if the type matches a supported service, null on error.
	 */
	public static function getIPRanges(string $type = 'none', bool $force = false): ?array
	{
		switch ($type)
		{
			default:
			case 'none':
				return [];

			case 'custom':
				/** @var ServerconfigmakerModel $model */
				$model = $serverconfigmakerModel ?? Factory::getApplication()->bootComponent('com_admintools')->getMVCFactory()->createModel('HtaccessmakerModel', 'Administrator');
				$config = $model->loadConfiguration();
				$ips = $config->restrictpip_custom ?: [];

				if (empty($ips) || !is_array($ips))
				{
					return [];
				}

				return $ips;

			case 'internal':
				return [
					'127.0.0.0/8',
					'::1',
					'10.0.0.0/8',
					'172.16.0.0/12',
					'192.168.0.0/16',
					'fc00::/7',
				];

			case 'cloudflare':
				return self::getCloudFlareRanges($force) ?: null;

			case 'sucuri':
				return self::getSucuriRanges($force) ?: null;

			case 'bunnycdn':
				return self::getBunnyCDNRanges($force) ?: null;
		}
	}

	/**
	 * Sets the server configuration maker model.
	 *
	 * @param   ServerconfigmakerModel|null  $serverconfigmakerModel  The ServerconfigmakerModel instance to set, or null to unset.
	 *
	 * @return  void
	 * @since   7.8.0
	 */
	public static function setServerconfigmakerModel(?ServerconfigmakerModel $serverconfigmakerModel): void
	{
		self::$serverconfigmakerModel = $serverconfigmakerModel;
	}

	/**
	 * Retrieves a list of IP ranges from Sucuri's documentation, either from the cache or directly from the source.
	 *
	 * This method fetches and parses IP ranges used by Sucuri's firewall, caches the result, and allows optional
	 * cache bypass with the `$force` parameter.
	 *
	 * @param   bool  $force  Whether to bypass the cache and fetch fresh data directly from the source. Default is
	 *                        false.
	 *
	 * @return  array  An array of IP ranges if successfully retrieved and parsed, or an empty array on failure.
	 * @throws \Exception
	 * @since   7.8.0
	 */
	private static function getSucuriRanges(bool $force = false): array
	{
		$cacheId = self::getCacheId('Sucuri');

		if ($force)
		{
			self::getCacheController()->remove($cacheId);
		}

		return self::getCacheController()->get(
			function (): array {
				$text = self::retrieveURL(
					'https://docs.sucuri.net/website-firewall/sucuri-firewall-troubleshooting-guide/'
				);

				if ($text === null || trim($text) === '')
				{
					return [];
				}

				$start = strpos($text, '&lt;FilesMatch &quot;.*&quot;&gt;');

				if ($start === false)
				{
					return [];
				}

				$start += 33;

				$end = strpos($text, '&lt;/FilesMatch&gt;', $start + 1);

				if ($end === false)
				{
					return [];
				}

				$text = substr($text, $start, $end - $start);
				$text = str_replace("\r", "\n", $text);
				$text = trim($text);

				if (empty($text))
				{
					return [];
				}

				$ips = explode("\n", $text);

				if (empty($ips))
				{
					return [];
				}

				$ips = array_filter($ips, fn($x) => str_starts_with((strtolower($x)), 'require ip '));

				if (empty($ips))
				{
					return [];
				}

				return array_filter(array_map(fn($x) => trim(str_replace('require ip ', '', $x)), $ips)) ?: [];
			},
			[],
			$cacheId
		);
	}

	/**
	 * Retrieves the BunnyCDN IP address ranges, including both IPv4 and IPv6 ranges.
	 *
	 * This method fetches and merges the IPv4 and IPv6 BunnyCDN IP ranges. Optionally,
	 * it can force updating the ranges instead of using cached data.
	 *
	 * @param   bool  $force  Whether to force the retrieval of fresh IP ranges and bypass cached data.
	 *
	 * @return  array  An array containing both IPv4 and IPv6 BunnyCDN IP ranges.
	 * @since   7.8.0
	 */
	private static function getBunnyCDNRanges(bool $force = false): array
	{
		$ipv4 = self::getBunnyCDNRangesIPv4($force);
		$ipv6 = self::getBunnyCDNRangesIPv6($force);

		$ipv4 = is_array($ipv4) ? $ipv4 : [];
		$ipv6 = is_array($ipv6) ? $ipv6 : [];

		return array_merge($ipv4, $ipv6);
	}

	/**
	 * Retrieves the list of CloudFlare IP ranges (both IPv4 and IPv6).
	 *
	 * @param   bool  $force  Whether to force re-fetching the CloudFlare ranges. Defaults to false.
	 *
	 * @return  array  An array of CloudFlare IP ranges.
	 * @since   7.8.0
	 */
	private static function getCloudFlareRanges(bool $force = false): array
	{
		$ipv4 = self::getCloudFlareRangesIPv4($force);
		$ipv6 = self::getCloudFlareRangesIPv6($force);

		$ipv4 = is_array($ipv4) ? $ipv4 : [];
		$ipv6 = is_array($ipv6) ? $ipv6 : [];

		return array_merge($ipv4, $ipv6);
	}

	/**
	 * Fetches and parses BunnyCDN IP range data from a given URL as an array of IP ranges.
	 *
	 * The method retrieves the content from the specified URL and attempts to decode it as a JSON array.
	 * If the content is invalid, or an error occurs, it returns an empty array.
	 *
	 * @param   string  $url  The URL to fetch the CDN range data from.
	 *
	 * @return  array  An array of IP ranges if successfully retrieved and decoded, or an empty array on failure.
	 * @since   7.8.0
	 */
	private static function internalGetBunnyCDNRangesFromURL(string $url): array
	{
		$raw = self::retrieveURL($url);

		if ($raw === null || trim($raw) === '')
		{
			return [];
		}

		try
		{
			$ips = @json_decode($raw);
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!is_array($ips) || empty($ips))
		{
			return [];
		}

		return $ips;
	}

	/**
	 * Retrieves the BunnyCDN IPv4 ranges, fetching them from the BunnyCDN API if not cached or if force refresh is
	 * specified.
	 *
	 * @param   bool  $force  Whether to force a refresh of the data, bypassing the cache. Defaults to false.
	 *
	 * @return  array  The list of BunnyCDN IPv4 ranges.
	 * @since   7.8.0
	 */
	private static function getBunnyCDNRangesIPv4(bool $force = false): array
	{
		$cacheId = self::getCacheId('BunnyCDNIPV4');

		if ($force)
		{
			self::getCacheController()->remove($cacheId);
		}

		return self::getCacheController()->get(
			fn() => self::internalGetBunnyCDNRangesFromURL('https://bunnycdn.com/api/system/edgeserverlist'),
			[],
			$cacheId
		);
	}

	/**
	 * Retrieves the BunnyCDN IPv6 ranges, fetching them from the BunnyCDN API if not cached or if force refresh is
	 * specified.
	 *
	 * @param   bool  $force  Whether to force a refresh of the data, bypassing the cache. Defaults to false.
	 *
	 * @return  array  The list of BunnyCDN IPv6 ranges.
	 * @since   7.8.0
	 */
	private static function getBunnyCDNRangesIPv6(bool $force = false): array
	{
		$cacheId = self::getCacheId('BunnyCDNIPV6');

		if ($force)
		{
			self::getCacheController()->remove($cacheId);
		}

		return self::getCacheController()->get(
			fn() => self::internalGetBunnyCDNRangesFromURL('https://bunnycdn.com/api/system/edgeserverlist/IPv6'),
			[],
			$cacheId
		);
	}

	/**
	 * Retrieves CloudFlare IPv4 ranges, optionally bypassing the cache.
	 *
	 * This method fetches CloudFlare's edge node IPv4 address ranges from their published URL
	 * and caches the data for future use. If forced, it clears the cache before fetching.
	 *
	 * @param   bool  $force  If true, clears the cache and fetch data directly from the source.
	 *
	 * @return  array         The IPv4 ranges retrieved, either from cache or source.
	 * @throws  \Exception
	 * @since   7.8.0
	 */
	private static function getCloudFlareRangesIPv4(bool $force = false)
	{
		$cacheId = self::getCacheId('CloudFlareIPV4');

		if ($force)
		{
			self::getCacheController()->remove($cacheId);
		}

		return self::getCacheController()->get(
			fn() => self::internalGetCloudFlareRangesFromURL('https://www.cloudflare.com/ips-v4'),
			[],
			$cacheId
		);
	}

	/**
	 * Retrieves CloudFlare IPv6 ranges, optionally bypassing the cache.
	 *
	 * This method fetches CloudFlare's edge node IPv6 address ranges from their published URL
	 * and caches the data for future use. If forced, it clears the cache before fetching.
	 *
	 * @param   bool  $force  If true, clears the cache and fetch data directly from the source.
	 *
	 * @return  array         The IPv6 ranges retrieved, either from cache or source.
	 * @throws  \Exception
	 * @since   7.8.0
	 */
	private static function getCloudFlareRangesIPv6(bool $force = false): array
	{
		$cacheId = self::getCacheId('CloudFlareIPV6');

		if ($force)
		{
			self::getCacheController()->remove($cacheId);
		}

		return self::getCacheController()->get(
			fn() => self::internalGetCloudFlareRangesFromURL('https://www.cloudflare.com/ips-v4'),
			[],
			$cacheId
		);
	}

	/**
	 * Fetches and processes CloudFlare IP ranges from a given URL.
	 *
	 * @param   string  $url  The URL to fetch the CloudFlare IP ranges from.
	 *
	 * @internal
	 * @return  array  An array of trimmed and filtered IP ranges. Returns an empty array if no valid data is retrieved.
	 * @since   7.8.0
	 */
	private static function internalGetCloudFlareRangesFromURL(string $url): array
	{
		$ips = self::retrieveURL($url);

		if ($ips === null || trim($ips) === '')
		{
			return [];
		}

		$list = explode("\n", trim($ips));

		if (empty($list))
		{
			return [];
		}

		return array_filter(array_map('trim', $list)) ?: [];
	}

	/**
	 * Retrieves the content of a given URL, and returns its body if successful.
	 *
	 * This goes through Joomla's HTTP API.
	 *
	 * @param   string  $url  The URL to retrieve content from.
	 *
	 * @return  string|null  The body of the HTTP response if successful, or null on failure.
	 * @since   7.8.0
	 */
	private static function retrieveURL(string $url): ?string
	{
		$response = (new HttpFactory())
			->getHttp(
				[
					'follow_location' => true,
					'userAgent'       => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Safari/605.1.15',
					'timeout'         => 5,
				]
			)
			->get($url);

		if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300)
		{
			return null;
		}

		return $response->body ?: null;
	}

	/**
	 * Returns a Joomla callback cache controller.
	 *
	 * Used to cache the fetched toot information.
	 *
	 * @return  CallbackController
	 * @throws  \Exception
	 * @since   7.8.0
	 */
	private static function getCacheController(): CallbackController
	{
		if (self::$cacheController instanceof CallbackController)
		{
			return self::$cacheController;
		}

		$app = Factory::getApplication();

		$options = [
			'defaultgroup' => 'com_admintools',
			'cachebase'    => $app->get('cache_path', JPATH_CACHE),
			'lifetime'     => self::CACHE_LIFETIME,
			'language'     => 'en-GB',
			'storage'      => $app->get('cache_handler', 'file'),
			'locking'      => true,
			'locktime'     => 15,
			'checkTime'    => true,
			'caching'      => true,
		];

		return self::$cacheController = Factory::getContainer()
			->get(CacheControllerFactoryInterface::class)
			->createCacheController('callback', $options);
	}

	/**
	 * Generates a unique cache identifier for a given item.
	 *
	 * Used to create a unique identifier string for caching purposes.
	 *
	 * @param   string  $item  The item for which the cache ID is being generated.
	 *
	 * @return  string  A unique cache identifier.
	 * @since   7.8.0
	 */
	private static function getCacheId(string $item): string
	{
		return hash('md5', sprintf('com_admintools:%s:cloudipranges:%s', self::getCacheSalt(), $item));
	}

	/**
	 * Generates and returns a unique cache salt.
	 *
	 * The salt is derived from the directory, version, release date, and core/pro type of the component.
	 *
	 * @return  string  An MD5 hashed string to be used as a cache salt.
	 * @since   7.8.0
	 */
	private static function getCacheSalt(): string
	{
		$version = defined('ADMINTOOLS_VERSION') ? ADMINTOOLS_VERSION : '0.0.0';
		$date    = defined('ADMINTOOLS_DATE') ? ADMINTOOLS_DATE : '0000-00-00';
		$pro     = (defined('ADMINTOOLS_PRO') ? ADMINTOOLS_PRO : false) ? 'pro' : 'core';

		return hash('md5', __DIR__ . ':' . $version . ':' . $date . ':' . $pro);
	}

}