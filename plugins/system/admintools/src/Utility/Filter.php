<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\System\AdminTools\Utility;

defined('_JEXEC') or die;

use Joomla\Utilities\IpHelper;

/**
 * IP Filtering utility class
 */
abstract class Filter
{
	/**
	 * The valid subnet masks for IPv4 networks.
	 *
	 * Remember, we cannot have any arbitrary value as a subnet mask. The subnet mask is an unsigned long integer which,
	 * in binary representation, is a block of 1s followed by a block of 0s. This unsigned long integer converted to
	 * octets expressed in decimal and separated by dots is the subnet mask (‘netmask’). Therefore, it can have only
	 * one of 31 possible values, listed below.
	 *
	 * @var  string[]
	 * @link https://en.wikipedia.org/wiki/Subnet#Determining_the_network_prefix
	 */
	private static $validNetmasks = [
		'255.255.255.255',
		'255.255.255.254',
		'255.255.255.252',
		'255.255.255.248',
		'255.255.255.240',
		'255.255.255.224',
		'255.255.255.192',
		'255.255.255.128',
		'255.255.255.0',
		'255.255.254.0',
		'255.255.252.0',
		'255.255.248.0',
		'255.255.240.0',
		'255.255.224.0',
		'255.255.192.0',
		'255.255.128.0',
		'255.255.0.0',
		'255.254.0.0',
		'255.252.0.0',
		'255.248.0.0',
		'255.240.0.0',
		'255.224.0.0',
		'255.192.0.0',
		'255.128.0.0',
		'255.0.0.0',
		'254.0.0.0',
		'252.0.0.0',
		'248.0.0.0',
		'240.0.0.0',
		'224.0.0.0',
		'192.0.0.0',
		'128.0.0.0',
	];

	/**
	 * Caches the IP arrays which were converted to IP ranges for faster reprocessing.
	 *
	 * @var  array<string[]>
	 */
	private static $cachedRanges = [];

	/** @var   string  The IP address of the current visitor */
	protected static $ip = null;

	/**
	 * Get the current visitor's IP address
	 *
	 * @return string
	 */
	public static function getIp()
	{
		if (is_null(static::$ip))
		{
			$ip = IpHelper::getIp();

			static::setIp($ip);
		}

		return static::$ip;
	}

	/**
	 * Set the IP address of the current visitor (to be used in testing)
	 *
	 * @param   string  $ip
	 *
	 * @return  void
	 */
	public static function setIp($ip)
	{
		static::$ip = $ip;
	}

	/**
	 * Convert an IPv4 or IPv6 expression into a normalised IPv6 binary string representation.
	 *
	 * @param   string|null  $ip  The IP expression to normalise.
	 *
	 * @return  string|null  The normalised binary string; NULL if normalisation failed.
	 */
	public static function ipToNormalisedIPv6(?string $ip): ?string
	{
		$ip        = trim($ip ?? '');
		$binString = inet_pton($ip);

		if (empty($ip) || empty($binString))
		{
			return null;
		}

		// Already an IPv6?
		if (strlen($binString) === 16)
		{
			// PHP evaluates ::1.2.3.4 as ::0102:0304 when it should be ::ffff:0102:0304. Catch and fix that.
			$mightBeIPv4 = substr($ip, 0, 2) === '::' && strpos($ip, '.', 2) !== false;
			$isIPv4In6   = $mightBeIPv4
			               && array_reduce(
				               array_slice(unpack('C*', $binString), 0, 12),
				               function (bool $carry, int $byte) {
					               return $carry && ($byte === 0);
				               },
				               true
			               );

			if ($isIPv4In6)
			{
				return str_repeat(chr(0x00), 10) . str_repeat(chr(0xFF), 2) .
				       substr($binString, -4);
			}

			return $binString;
		}

		/**
		 * IPv4-in-IPv6 is ::ffff:0123:4567 where 0123:4567 is the IPv4 address.
		 *
		 * In practical terms it means that I can prefix the IPv4 expression with ten bytes 0x00 and two bytes 0xFF.
		 */
		return str_repeat(chr(0x00), 10) . str_repeat(chr(0xFF), 2) . $binString;
	}

	/**
	 * Resolve an Admin Tools IPv4 domain expression (e.g. `@example.com`) to its IPv4 address.
	 *
	 * If the domain has multiple A records only the contents of one record will be returned (OS choice!).
	 *
	 * @param   string|null  $expression  The expression to resolve.
	 *
	 * @return  string|null  NULL if it's not an expression, or resolution failed.
	 */
	public static function resolveIPv4Domain(?string $expression): ?string
	{
		$expression = trim($expression ?? '');

		if (empty($expression) || substr($expression, 0, 1) != '@')
		{
			return null;
		}

		/** @see https://secure.php.net/manual/en/function.gethostbyname.php */
		putenv('RES_OPTIONS=retrans:1 retry:1 timeout:3 attempts:1');
		$domain = substr($expression, 1);
		$domain = rtrim($domain, '.') . '.';
		$ip     = gethostbyname($domain);

		if ($ip == $domain)
		{
			return null;
		}

		return $ip;
	}

	/**
	 * Resolve an Admin Tools IPv6 domain expression (e.g. `#example.com`) to its IPv6 address.
	 *
	 * If the domain has multiple AAAA records only the contents of the first record communicated by the DNS server will
	 * be returned.
	 *
	 * @param   string|null  $expression  The expression to resolve.
	 *
	 * @return  string|null  NULL if it's not an expression, or resolution failed.
	 */
	public static function resolveIPv6Domain(?string $expression): ?string
	{
		$expression = trim($expression ?? '');

		if (empty($expression) || substr($expression, 0, 1) != '#')
		{
			return null;
		}

		$domain = substr($expression, 1);
		$dns    = dns_get_record($domain, DNS_AAAA);

		foreach ($dns as $record)
		{
			if ($record['type'] === 'AAAA')
			{
				return $record['ipv6'];
			}
		}

		return null;
	}


	/**
	 * Checks if the user's IP is contained in a list of IPs or IP expressions
	 *
	 * This code has been copied from FOF to lower the amount of dependencies required
	 *
	 * @param   array|string  $ipTable  The list of IP expressions
	 * @param   string        $ip       The user's IP address, leave empty / null to get the current IP address
	 *
	 * @return  null|bool  True if it's in the list, null if the filtering can't proceed
	 */
	public static function IPinList($ipTable = [], $ip = null)
	{
		// Sanity check
		if (!function_exists('inet_pton'))
		{
			return false;
		}

		// No point proceeding with an empty IP list. DO NOT REMOVE. This checks the raw input.
		if (empty($ipTable))
		{
			return false;
		}

		// Get our IP address
		if (empty($ip))
		{
			$ip = static::getIp();
		}

		// If no IP address can be found, return false
		if ($ip == '0.0.0.0' || empty($ip))
		{
			return false;
		}

		// Normalise the IP
		$ip = self::ipToNormalisedIPv6($ip);

		// Do not continue with an invalid IP address.
		if ($ip === null)
		{
			return null;
		}

		// If the IP list is not an array, convert it to an array.
		if (!is_array($ipTable))
		{
			if (strpos($ipTable, ',') !== false)
			{
				$ipTable = explode(',', $ipTable);
				$ipTable = array_map(function ($x) {
					return trim($x);
				}, $ipTable);
			}
			else
			{
				$ipTable = trim($ipTable);
				$ipTable = [$ipTable];
			}
		}

		// Process the IP table and cache it.
		$key = md5(serialize($ipTable));

		self::$cachedRanges[$key] ??= array_filter(
			array_map([self::class, 'expressionToRange'], $ipTable)
		);

		// No point proceeding with a now-empty IP list. DO NOT REMOVE. This checks the result of the conversion.
		if (empty($ipTable))
		{
			return false;
		}

		foreach (self::$cachedRanges[$key] as $range)
		{
			[$from, $to] = $range;

			if ($from > $to)
			{
				[$from, $to] = [$to, $from];
			}

			if (($ip >= $from) && ($ip <= $to))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Converts an IP or IP range expression into an array of starting and ending addresses in in_addr format.
	 *
	 * The expressions supported are:
	 * * `@example.com` Resolve a domain into a singular IPv4 address for exact IP matching.
	 * * `#example.com` Resolve a domain into a singular IPv6 address for exact IP matching.
	 * * `1.2.3.4` A single IPv4 address for exact IP matching.
	 * * `1.2.3.4/255.255.255.0` An IPv4 with a subnet mask (netmask).
	 * * `1.2.3.4/24` An IPv4 with a CIDR.
	 * * `1.2.3.` A partial IPv4. Existing octets become 255 and missing octets become 0 in the netmask.
	 * * `1.2.3.4-2.3.4.5` Any pair of IPv4 addresses separated by a dash is parsed as a range.
	 * * `::1` Any validly formatted IPv6 address for exact IP matching.
	 * * `2001::cafe:1-2001::fefe:ffff` Any pair of IPv6 addresses separated by a dash is parsed as a range.
	 * * `2001::cafe:1/64` Any validly formatted IPv6 address with a CIDR.
	 *
	 * Expressions are normalised to IPv6. Matching takes place in the IPv6 address space. This allows IPv4-in-IPv6
	 * encapsulation (i.e. ::1.2.3.4 or ::ffff:1.2.3.4 or ::ffff:0102:0304) to be processed correctly, e.g. on IPv6-only
	 * servers receiving tunneled IPv4 traffic.
	 *
	 * @param   string|null  $expression  The expression to convert.
	 *
	 * @return  string[]|null  The [$from, $to] array, NULL if the expression is invalid.
	 */
	private static function expressionToRange(?string $expression): ?array
	{
		$expression = trim($expression ?? '');

		if (empty($expression))
		{
			return null;
		}

		// Resolve IPv4 / IPv6 domain lookup expressions, if necessary.
		$expression = self::resolveIPv4Domain($expression) ?? self::resolveIPv6Domain($expression) ?? $expression;

		// IP Range
		if (strpos($expression, '-') !== false)
		{
			[$from, $to] = explode('-', $expression, 2);

			$from = self::ipToNormalisedIPv6($from);
			$to   = self::ipToNormalisedIPv6($to);

			if ($from === null || $to === null)
			{
				return null;
			}

			return [$from, $to];
		}

		// Dangling dot (IPv4 only) – converted to a netmask, so it can be processed in the next block.
		if (!self::isIPv6($expression) && substr($expression, -1) === '.')
		{
			$countChars = count_chars($expression, 1);
			$dots       = $countChars[ord('.')];

			switch ($dots)
			{
				case 1:
					$expression = "{$expression}0.0.0/255.0.0.0";
					break;

				case 2:
					$expression = "{$expression}0.0/255.255.0.0";
					break;

				case 3:
					$expression = "{$expression}0/255.255.255.0";
					break;

				default:
					$expression .= '/255.255.255.255';
			}
		}

		// If there's no slash, it's a single IP address.
		if (strpos($expression, '/') === false)
		{
			$binaryIP = self::ipToNormalisedIPv6($expression);

			if ($binaryIP === null)
			{
				return null;
			}

			return [$binaryIP, $binaryIP];
		}

		[$ip, $netmaskOrCidr] = explode('/', $expression, 2);
		$notNormalisedIp = inet_pton($ip);
		$ip = self::ipToNormalisedIPv6($ip);

		if ($ip === null)
		{
			return null;
		}

		// IPv4 addresses are exactly 32 bit (4 bytes) long.
		$isIPv4 = strlen($notNormalisedIp) === 4;

		// Netmask expressions are non-integers. We will try to convert that into a CIDR.
		if (!is_numeric($netmaskOrCidr))
		{
			// Netmasks are only defined for IPv4 addresses, which are 4 bytes long. Do we actually have one?
			if (!$isIPv4)
			{
				return null;
			}

			// Is the string we have an actual netmask?
			// -- normalise the netmask e.g. 255.00.000.0 => 255.0.0.0
			$temp = self::ipToNormalisedIPv6($netmaskOrCidr);

			if ($temp === null)
			{
				return null;
			}

			$normalisedNetmask = inet_ntop(substr($temp, -4));

			// -- Check if the netmask is a valid one. Remember, we cannot have arbitrary values there.
			/** @link https://en.wikipedia.org/wiki/Subnet#Subnet_host_count */
			if (!in_array($normalisedNetmask, self::$validNetmasks))
			{
				return null;
			}

			// Convert the netmask into a long integer
			$long = ip2long($netmaskOrCidr);

			if ($long === false)
			{
				// Well, the netmask was invalid. Sorry!
				return null;
			}

			// Convert the netmask to a CIDR and fall through.
			$base          = ip2long('255.255.255.255');
			$netmaskOrCidr = 32 - (int) log(($long ^ $base) + 1, 2);
		}

		/**
		 * We have a CIDR.
		 *
		 * CIDR is an integer which effectively tells us how many bits to keep from the IP address.
		 *
		 * The way we handle it is to convert the IP to bits, keep the bits specified by the CIDR and then stuff
		 * the rest of the bits with zeroes to get the starting address, and then with ones to get the ending
		 * address in the address range.
		 */
		$bits = @intval($netmaskOrCidr);

		// CIDR ranges are between 1 and 128 bits.
		if ($bits < 1 || $bits > 128)
		{
			return null;
		}

		// We can't have CIDR wider than 32 bits on an IPv4 address!
		if ($isIPv4 && $bits > 32)
		{
			return null;
		}

		// IPv4 needs to be padded by the 96 constant bits (The 0000:0000:0000:0000:0000:ffff: IPv4-in-Ipv6 prefix)
		if ($isIPv4)
		{
			$bits += 96;
		}

		// Do all the fun bit math.
		$keepBits = substr(self::inet_to_bits($ip), 0, $bits);
		$fromBin  = str_pad($keepBits, 128, '0', STR_PAD_RIGHT);
		$toBin    = str_pad($keepBits, 128, '1', STR_PAD_RIGHT);

		return [self::bits_to_inet($fromBin), self::bits_to_inet($toBin)];
	}

	/**
	 * Is it an IPv6 IP address?
	 *
	 * @param   string  $ip  An IPv4 or IPv6 address
	 *
	 * @return  boolean  True if it's IPv6
	 * @link    https://ihateregex.io/expr/ipv6/
	 */
	private static function isIPv6($ip)
	{
		return preg_match('/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|(::)?([0-9a-fA-F]{1,4}:){1,4}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/', $ip);
	}

	/**
	 * Converts an in_addr-format string representing an IPv4 or IPv6 address to a bits string.
	 *
	 * @param   string  $inet  The in_addr representation of an IPv4 or IPv6 address (4 or 16 characters long).
	 *
	 * @return  string  The bits string (32 or 128 characters long).
	 */
	private static function inet_to_bits($inet)
	{
		if (strlen($inet) == 4)
		{
			$unpacked = unpack('C4', $inet);
		}
		else
		{
			$unpacked = unpack('C16', $inet);
		}

		$binaryip = '';

		foreach ($unpacked as $byte)
		{
			$binaryip .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
		}

		return $binaryip;
	}

	/**
	 * Converts a bits string into an in_addr-format string which represents an IPv4 or IPv6 address.
	 *
	 * @param   string  $bits  The bits string (32 or 128 characters long).
	 *
	 * @return  string  The in_addr representation of an IPv4 or IPv6 address (4 or 16 characters long).
	 */
	private static function bits_to_inet(string $bits): string
	{
		$ret   = '';
		$bytes = str_split($bits, 8);

		foreach ($bytes as $byte)
		{
			$ret .= pack('C', bindec($byte));
		}

		return $ret;
	}
}