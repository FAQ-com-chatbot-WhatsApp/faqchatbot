<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\CloudIPRanges;
use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use DateTimeZone;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

#[\AllowDynamicProperties]
class HtaccessmakerModel extends ServerconfigmakerModel
{
	use ApacheVersionTrait;

	/**
	 * The current configuration of this feature
	 *
	 * @var  object
	 */
	protected $configKey = 'htconfig';

	/**
	 * The base name of the configuration file being saved by this feature, e.g. ".htaccess". The file is always saved
	 * in the site's root. Any old files under that name are renamed with a .admintools suffix.
	 *
	 * @var string
	 */
	protected $configFileName = '.htaccess';

	/** @inheritdoc */
	public function isSupported(): int
	{
		return ServerTechnology::isHtaccessSupported();
	}

    /** @inheritdoc  */
    public function hasPhpHandlers(): bool
    {
        $htaccess_file = JPATH_ROOT.'/.htaccess';

        if (!file_exists($htaccess_file))
        {
            return false;
        }

        $contents = file_get_contents($htaccess_file);

	    if ($contents === false)
	    {
		    return false;
	    }

        return !is_null($this->extractHandler($contents));
    }

    /** @inheritdoc  */
	public function getPhpHandlers(): ?string
	{
		$htaccess_file = JPATH_ROOT.'/.htaccess';

		if (!file_exists($htaccess_file))
		{
			return null;
		}

		$contents = @file_get_contents($htaccess_file);

		if ($contents === false)
		{
			return null;
		}

		return $this->extractHandler($contents);
	}

    /**
     * This function will copy the handlers from the main .htaccess file and it will save it inside the custom rules field
     *
     * @return void
     * @throws \Exception
     */
    public function includePhpHandlers()
    {
        $htaccess_file = JPATH_ROOT.'/.htaccess';

        if (!file_exists($htaccess_file))
        {
            throw new \RuntimeException(Text::_('COM_ADMINTOOLS_HTACCESSMAKER_LBL_PHPHANDLERS_ERR_NO_HTACCESS'));
        }

        $contents = file_get_contents($htaccess_file);
        $handlers = $this->extractHandler(is_string($contents) ? $contents : '');

        if (!$handlers)
        {
            throw new \RuntimeException(Text::_('COM_ADMINTOOLS_HTACCESSMAKER_LBL_PHPHANDLERS_ERR_NO_HANDLERS'));
        }

        // Be sure we do have a loaded configuration
        $data = $this->loadConfiguration();

        // Double check that we do not already have some handlers in the custom footer field
        if ($data['custfoot'])
        {
            $handlers_footer = $this->extractHandler($data['custfoot']);

            if ($handlers_footer)
            {
                throw new \RuntimeException(Text::_('COM_ADMINTOOLS_HTACCESSMAKER_LBL_PHPHANDLERS_ERR_ALREADY_SAVED'));
            }
        }

        $data['custfoot'] .= "\n".$handlers;

        $this->saveConfiguration($data);
    }

    /** @inheritdoc  */
    public function extractHandler(string $server_config): ?string
    {
        // Normalize the .htaccess
        $server_config = $this->normalizeHtaccess($server_config);

        // Look for SetHandler and AddHandler in Files and FilesMatch containers
        foreach (['Files', 'FilesMatch'] as $container)
        {
            $result = $this->extractContainer($container, $server_config);

            if (!is_null($result))
            {
                return $result;
            }
        }

        // Fallback: extract an AddHandler line
        $found = preg_match('#^AddHandler\s?.*\.php.*$#mi', $server_config, $matches);

        if ($found >= 1)
        {
            return $matches[0];
        }

        return null;
    }

    /**
     * Normalize the .htaccess file content, making it suitable for handler extraction
     *
     * @param string $htaccess  The original file
     *
     * @return  string  The normalized file
     */
    private function normalizeHtaccess(string $htaccess): string
    {
        // Convert all newlines into UNIX style
        $htaccess = str_replace("\r\n", "\n", $htaccess);
        $htaccess = str_replace("\r", "\n", $htaccess);

		// Squash whitespace
	    $htaccess = preg_replace('/[\040\011]{1,}/m', ' ', $htaccess);
	    $htaccess = preg_replace('/^[\040\011]{1,}/m', '', $htaccess);

        // Return only non-comment, non-empty lines
        $isNonEmptyNonComment = function ($line) {
            $line = trim($line);

            return !empty($line) && (substr($line, 0, 1) !== '#');
        };

        $lines = array_map('trim', explode("\n", $htaccess));

        return implode("\n", array_filter($lines, $isNonEmptyNonComment));
    }

    /**
     * Extracts a Files or FilesMatch container with an AddHandler or SetHandler line
     *
     * @param string $container  "Files" or "FilesMatch"
     * @param string $htaccess   The .htaccess file content
     *
     * @return  string|null  NULL when not found
     */
    protected function extractContainer(string $container, string $htaccess): ?string
    {
        // Try to find the opening container tag e.g. <Files....>
        $pattern = sprintf('#<%s\s*.*\.php.*>#m', $container);
        $found   = preg_match_all($pattern, $htaccess, $matches, PREG_OFFSET_CAPTURE);

        if (!$found)
        {
            return null;
        }

		foreach ($matches[0] as $thisMatch)
		{
			// Get the rest of the .htaccess sample
			$openContainer  = $thisMatch[0];
			$subsetHtaccess = trim(substr($htaccess, $thisMatch[1] + strlen($thisMatch[0])));

			// Try to find the closing container tag
			$pattern    = sprintf('#<\s*/%s.*>#m', $container);
			$innerFound = preg_match($pattern, $subsetHtaccess, $innerMatches, PREG_OFFSET_CAPTURE);

			if (!$innerFound)
			{
				continue;
			}

			// Get the rest of the .htaccess sample
			$subsetHtaccess = trim(substr($subsetHtaccess, 0, $innerMatches[$innerFound - 1][1]));
			$closeContainer = $innerMatches[$innerFound - 1][0];

			if (empty($subsetHtaccess))
			{
				continue;
			}

			// Now we'll explode remaining lines and find the first SetHandler or AddHandler line
			$lines = array_map('trim', explode("\n", $subsetHtaccess));
			$lines = array_filter(
				$lines,
				function ($line) {
					$lowercaseLine = strtolower($line);

					return (strpos($lowercaseLine, 'addhandler') === 0)
						|| (strpos($lowercaseLine, 'sethandler') === 0)
						|| (strpos($lowercaseLine, 'fcgid') === 0);
				}
			);

			if (empty($lines))
			{
				continue;
			}

			return $openContainer . "\n" . array_shift($lines) . "\n" . $closeContainer;
		}

	    return null;
    }

	/**
	 * Compile and return the contents of the .htaccess configuration file
	 *
	 * @return  string
	 */
	public function makeConfigFile()
	{
		// Make sure we are called by an expected caller
		ServerTechnology::checkCaller($this->allowedCallersForMake);

		$app = Factory::getApplication();

		// Guess Apache features
		$apacheVersion = $this->apacheVersion();
		$serverCaps    = (object) [
			'customCodes' => version_compare($apacheVersion, '2.2', 'ge'), // Custom redirections, e.g. R=301
			'deflate'     => version_compare($apacheVersion, '2.0', 'ge') // mod_deflate support
		];
		$redirCode     = $serverCaps->customCodes ? '[R=301,L]' : '[R,L]';

		$timezone = 'UTC';

		// Fetch the timezone from the user only if we're not in CLI
		if (!$app->isClient('cli'))
		{
			$timezone = $app->getIdentity()->getParam('timezone', $app->get('offset', 'UTC'));
		}

		$date = clone Factory::getDate();
		$tz   = new DateTimeZone($timezone);
		$date->setTimezone($tz);
		$d        = $date->format('Y-m-d H:i:s T', true);
		$version  = ADMINTOOLS_VERSION;
		$htaccess = <<<END
### ===========================================================================
### Security Enhanced & Highly Optimized .htaccess File for Joomla!
### automatically generated by Admin Tools $version on $d
### Auto-detected Apache version: $apacheVersion (best guess)
### ===========================================================================
###
### The contents of this file are based on the same author's work "Master
### .htaccess".
###
### Admin Tools is Free Software, distributed under the terms of the GNU
### General Public License version 3 or, at your option, any later version
### published by the Free Software Foundation.
###
### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! IMPORTANT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
### !!                                                                       !!
### !!  If you get an Internal Server Error 500 or a blank page when trying  !!
### !!  to access your site, remove this file and try tweaking its settings  !!
### !!  in the back-end of the Admin Tools component.                        !!
### !!                                                                       !!
### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
###


END;

		$config = (object) $this->loadConfiguration();

		// Let's start with IP restriction
		$restrictIP = $config->restrictip ?? 'none';

		if ($restrictIP !== 'none')
		{
			$restrictIPList = CloudIPRanges::getIPRanges($restrictIP);

			if ($restrictIPList === null)
			{
				throw new \RuntimeException(Text::_('COM_ADMINTOOLS_ERR_INVALID_RESTRICTIP'));
			}

			$isApache24 = version_compare($apacheVersion, '2.4', 'ge');

			$htaccess .= <<< END
##### Restricted access by IP address -- BEGIN

END;

			if (!$isApache24)
			{
				$htaccess .= <<<END
Order Deny,Allow
Deny from all
END;
			}

			foreach ($restrictIPList as $ip)
			{
				$htaccess .= ($isApache24 ? 'Require ip ' : 'Allow from ').$ip."\n";
			}

			$htaccess .= <<< END
##### Restricted access by IP address -- END


END;
		}

		// Is HSTS enabled?
		$hasHSTS = $config->hstsheader != 0;

		$htaccess .= <<< HTACCESS
##### RewriteEngine enabled - BEGIN
RewriteEngine On
##### RewriteEngine enabled - END

# PHP FastCGI fix for HTTP Authorization
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

HTACCESS;

		$rewritebase = $config->rewritebase;

		if (!empty($rewritebase))
		{
			$htaccess    .= "##### RewriteBase set - BEGIN\n";
			$rewritebase = trim($rewritebase, '/');
			$htaccess    .= "RewriteBase /$rewritebase\n";
			$htaccess    .= "##### RewriteBase set - END\n\n";
		}

		if ($hasHSTS)
		{
			$httpsHost = $config->httpshost;
			$htaccess  .= <<< END
##### HTTP to HTTPS redirection
## Since you have enabled HSTS the first redirection rule will instruct the browser to visit the HTTPS version of your
## site. This prevents unsafe redirections through HTTP.
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteRule .* https://$httpsHost%{REQUEST_URI} [L,R=301]


END;
		}

		if (!empty($config->custhead))
		{
			$htaccess .= "##### Custom Rules (Top of File) -- BEGIN\n";
			$htaccess .= $config->custhead . "\n";
			$htaccess .= "##### Custom Rules (Top of File) -- END\n\n";
		}

		if ($config->fileorder == 1)
		{
			$htaccess .= "##### File execution order -- BEGIN\n";
			$htaccess .= "DirectoryIndex index.php index.html\n";
			$htaccess .= "##### File execution order -- END\n\n";
		}

		if ($config->nodirlists == 1)
		{
			$htaccess .= "##### No directory listings -- BEGIN\n";
			$htaccess .= "IndexIgnore *\n";

			switch ($config->symlinks)
			{
				case 0:
					$htaccess .= "Options -Indexes\n";
					break;

				case 1:
					$htaccess .= "Options -Indexes +FollowSymLinks\n";
					break;

				case 2:
					$htaccess .= "Options -Indexes +SymLinksIfOwnerMatch\n";
					break;
			}

			$htaccess .= "##### No directory listings -- END\n\n";
		}
		elseif ($config->symlinks != 0)
		{
			$htaccess .= "##### Follow symlinks -- BEGIN\n";

			switch ($config->symlinks)
			{
				case 1:
					$htaccess .= "Options +FollowSymLinks\n";
					break;

				case 2:
					$htaccess .= "Options +SymLinksIfOwnerMatch\n";
					break;
			}

			$htaccess .= "##### Follow symlinks -- END\n\n";
		}

		if ($config->exptime != 0)
		{
			$expWeek  = '1 week';
			$expMonth = '1 month';

			if ($config->exptime == 2)
			{
				$expWeek  = '1 year';
				$expMonth = '1 year';
			}

			$htaccess .= <<<END
##### Optimal default expiration time - BEGIN
<IfModule mod_expires.c>
	# Enable expiration control
	ExpiresActive On
	
	# No caching for specific resource types
	## -- Application cache manifest
	ExpiresByType text/cache-manifest "now"
	## -- XML and JSON
	ExpiresByType application/json "now"
	ExpiresByType application/xml "now"
	ExpiresByType text/xml "now"

	## RSS and Atom feeds: 1 hour (hardcoded)
	ExpiresByType application/atom+xml "now plus 1 hour"
	ExpiresByType application/rss+xml "now plus 1 hour"

	# CSS and JS expiration: $expWeek after request
	ExpiresByType text/css "now plus $expWeek"
	ExpiresByType text/javascript "now plus $expWeek"
	ExpiresByType application/javascript "now plus $expWeek"
	ExpiresByType application/ld+json "now plus $expWeek"
	ExpiresByType application/x-javascript "now plus $expWeek"

	# Image files expiration: $expMonth after request
	ExpiresByType application/ico "now plus $expMonth"
	ExpiresByType application/smil "now plus $expMonth"
	ExpiresByType application/vnd.wap.wbxml "now plus $expMonth"
	ExpiresByType image/bmp "now plus $expMonth"
	ExpiresByType image/gif "now plus $expMonth"
	ExpiresByType image/ico "now plus $expMonth"
	ExpiresByType image/icon "now plus $expMonth"
	ExpiresByType image/jp2 "now plus $expMonth"
	ExpiresByType image/jpeg "now plus $expMonth"
	ExpiresByType image/jpg "now plus $expMonth"
	ExpiresByType image/pipeg "now plus $expMonth"
	ExpiresByType image/png "now plus $expMonth"
	ExpiresByType image/svg+xml "now plus $expMonth"
	ExpiresByType image/tiff "now plus $expMonth"
	ExpiresByType image/vnd.microsoft.icon "now plus $expMonth"
	ExpiresByType image/vnd.wap.wbmp "now plus $expMonth"
	ExpiresByType image/webp "now plus $expMonth"
	ExpiresByType image/x-icon "now plus $expMonth"
	ExpiresByType text/ico "now plus $expMonth"
	
	# Font files expiration: $expWeek after request
	ExpiresByType application/font-woff "now plus $expWeek"
	ExpiresByType application/font-woff2 "now plus $expWeek"
	ExpiresByType application/vnd.ms-fontobject "now plus $expWeek"
	ExpiresByType application/x-font-opentype "now plus $expWeek"
	ExpiresByType application/x-font-ttf "now plus $expWeek"
	ExpiresByType application/x-font-woff "now plus $expWeek"
	ExpiresByType font/opentype "now plus $expWeek"
	ExpiresByType font/otf "now plus $expWeek"
	ExpiresByType font/ttf "now plus $expWeek"
	ExpiresByType font/woff "now plus $expWeek"
	ExpiresByType font/woff2 "now plus $expWeek"

	# Audio files expiration: $expMonth after request
	ExpiresByType application/ogg "now plus $expMonth"
	ExpiresByType audio/3gpp "now plus $expMonth"
	ExpiresByType audio/3gpp2 "now plus $expMonth"
	ExpiresByType audio/aac "now plus $expMonth"
	ExpiresByType audio/basic "now plus $expMonth"
	ExpiresByType audio/mid "now plus $expMonth"
	ExpiresByType audio/midi "now plus $expMonth"
	ExpiresByType audio/mp3 "now plus $expMonth"
	ExpiresByType audio/mpeg "now plus $expMonth"
	ExpiresByType audio/ogg "now plus $expMonth"
	ExpiresByType audio/opus "now plus $expMonth"
	ExpiresByType audio/x-aiff "now plus $expMonth"
	ExpiresByType audio/x-mpegurl "now plus $expMonth"
	ExpiresByType audio/x-pn-realaudio "now plus $expMonth"
	ExpiresByType audio/x-wav "now plus $expMonth"
	ExpiresByType audio/wav "now plus $expMonth"

	# Movie files expiration: $expMonth after request
	ExpiresByType application/x-shockwave-flash "now plus $expMonth"
	ExpiresByType video/3gpp "now plus $expMonth"
	ExpiresByType video/3gpp2 "now plus $expMonth"
	ExpiresByType video/mp4 "now plus $expMonth"
	ExpiresByType video/mpeg "now plus $expMonth"
	ExpiresByType video/ogg "now plus $expMonth"
	ExpiresByType video/quicktime "now plus $expMonth"
	ExpiresByType video/webm "now plus $expMonth"
	ExpiresByType video/x-la-asf "now plus $expMonth"
	ExpiresByType video/x-ms-asf "now plus $expMonth"
	ExpiresByType video/x-msvideo "now plus $expMonth"
	ExpiresByType x-world/x-vrml "now plus $expMonth"
</IfModule>

# Disable caching of administrator/index.php
<Files "administrator/index.php">
	<IfModule mod_expires.c>
		ExpiresActive Off
	</IfModule>
	<IfModule mod_headers.c>
		Header unset ETag
		Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
		Header set Pragma "no-cache"
		Header set Expires "Wed, 11 Jan 1984 05:00:00 GMT"
	</IfModule>
</Files>

##### Optimal default expiration time - END


END;
		}

		if (!empty($config->hoggeragents) && ($config->nohoggers == 1))
		{
			$htaccess .= "##### Common hacking tools and bandwidth hoggers block -- BEGIN\n";
			$htaccess .= "<IfModule mod_setenvif.c>\n";

			foreach ($config->hoggeragents as $agent)
			{
				$htaccess .= "SetEnvIf user-agent \"(?i:$agent)\" stayout=1\n";
			}

			$htaccess .= <<< HTACCESS
<IfModule !mod_authz_core.c>
deny from env=stayout
</IfModule>
<IfModule mod_authz_core.c>
  <RequireAll>
	Require all granted
	Require not env stayout
  </RequireAll>
</IfModule>
##### Common hacking tools and bandwidth hoggers block -- END


HTACCESS;
			$htaccess .= "</IfModule>\n";
		}

		if (($config->autocompress == 1) && ($serverCaps->deflate))
		{
			// See https://stackoverflow.com/questions/5230202/apache-addoutputfilterbytype-is-deprecated-how-to-rewrite-using-mod-filter
			$apacheModuleForDeflate = version_compare($apacheVersion, '2.4', 'ge') ? 'mod_filter' : 'mod_deflate';
			$htaccess               .= <<<HTACCESS
##### Automatic compression of resources -- BEGIN
# Automatically serve .css.gz, .css.br, .js.gz or .js.br instead of the original file
# These are versions of the files pre-compressed with GZip or Brotli, respectively
<IfModule mod_headers.c>
    # Serve Brotli compressed CSS files if they exist and the client accepts Brotli.
    RewriteCond "%{HTTP:Accept-encoding}" "br"
    RewriteCond "%{REQUEST_FILENAME}\.br" -s
    RewriteRule "^(.*)\.css" "$1\.css\.br" [QSA]

    # Serve Brotli compressed JS files if they exist and the client accepts Brotli.
    RewriteCond "%{HTTP:Accept-encoding}" "br"
    RewriteCond "%{REQUEST_FILENAME}\.br" -s
    RewriteRule "^(.*)\.js" "$1\.js\.br" [QSA]
    
    # Serve correct content types, and prevent double compression.
    RewriteRule "\.css\.br$" "-" [T=text/css,E=no-gzip:1,E=no-brotli:1,L]
	RewriteRule "\.js\.br$" "-" [T=text/javascript,E=no-gzip:1,E=no-brotli:1,L]
    
    <FilesMatch "(\.js\.br|\.css\.br)$">
      # Serve correct encoding type.
      Header set Content-Encoding br

      # Force proxies to cache gzipped & non-gzipped css/js files separately.
      Header append Vary Accept-Encoding
    </FilesMatch>

    # Serve gzip compressed CSS files if they exist and the client accepts gzip.
    RewriteCond "%{HTTP:Accept-encoding}" "gzip"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.css" "$1\.css\.gz" [QSA]

    # Serve gzip compressed JS files if they exist and the client accepts gzip.
    RewriteCond "%{HTTP:Accept-encoding}" "gzip"
    RewriteCond "%{REQUEST_FILENAME}\.gz" -s
    RewriteRule "^(.*)\.js" "$1\.js\.gz" [QSA]

    # Serve correct content types, and prevent $apacheModuleForDeflate double gzip.
    # Also set it as the last rule to prevent the Front- or Backend protection from preventing access to the .gz file.
    RewriteRule "\.css\.gz$" "-" [T=text/css,E=no-gzip:1,E=no-brotli:1,L]
	RewriteRule "\.js\.gz$" "-" [T=text/javascript,E=no-gzip:1,E=no-brotli:1,L]

    <FilesMatch "(\.js\.gz|\.css\.gz)$">
      # Serve correct encoding type.
      Header set Content-Encoding gzip

      # Force proxies to cache gzipped & non-gzipped css/js files separately.
      Header append Vary Accept-Encoding
    </FilesMatch>
</IfModule>

## Automatically compress by MIME type using mod_brotli. Takes priority due to better compression ratio.
<IfModule mod_brotli.c>
	AddOutputFilterByType BROTLI_COMPRESS text/plain text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript text/javascript image/svg+xml
</IfModule>

## Automatically compress by MIME type using {$apacheModuleForDeflate}.
<IfModule {$apacheModuleForDeflate}.c>
	AddOutputFilterByType DEFLATE text/plain text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript text/javascript image/svg+xml
</IfModule>

## Fallback to mod_gzip when neither mod_brotli nor $apacheModuleForDeflate is available
<IfModule !mod_brotli.c>
	<IfModule !{$apacheModuleForDeflate}.c>
		<IfModule mod_gzip.c>
			mod_gzip_on Yes
			mod_gzip_dechunk Yes
			mod_gzip_keep_workfiles No
			mod_gzip_can_negotiate Yes
			mod_gzip_add_header_count Yes
			mod_gzip_send_vary Yes
			mod_gzip_min_http 1000
			mod_gzip_minimum_file_size 300
			mod_gzip_maximum_file_size 512000
			mod_gzip_maximum_inmem_size 60000
			mod_gzip_handle_methods GET
			mod_gzip_item_include file \.(html?|txt|css|js|php|pl|xml|rb|py|svg|scgz)$
			mod_gzip_item_include mime ^text/javascript$
			mod_gzip_item_include mime ^text/plain$
			mod_gzip_item_include mime ^text/xml$
			mod_gzip_item_include mime ^text/css$
			mod_gzip_item_include mime ^application/xml$
			mod_gzip_item_include mime ^application/xhtml+xml$
			mod_gzip_item_include mime ^application/rss+xml$
			mod_gzip_item_include mime ^application/javascript$
			mod_gzip_item_include mime ^application/x-javascript$
			mod_gzip_item_include mime ^image/svg+xml$
			mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
			mod_gzip_item_include handler ^cgi-script$
			mod_gzip_item_include handler ^server-status$
			mod_gzip_item_include handler ^server-info$
			mod_gzip_item_include handler ^application/x-httpd-php
			mod_gzip_item_exclude mime ^image/.*
		</ifmodule>
	</IfModule>
</IfModule>
##### Automatic compression of resources -- END

HTACCESS;

			if ($config->forcegzip == 1)
			{
				$htaccess .= <<< HTACCESS
## Force GZip compression for mangled Accept-Encoding headers
<IfModule mod_setenvif.c>
	<IfModule mod_headers.c>
		SetEnvIfNoCase ^(Accept-EncodXng|X-cept-Encoding|X{15}|~{15}|-{15})$ ^((gzip|deflate)\s*,?\s*)+|[X~-]{4,13}$ HAVE_Accept-Encoding
		RequestHeader append Accept-Encoding "gzip,deflate" env=HAVE_Accept-Encoding
	</IfModule>
</IfModule>

HTACCESS;
			}
		}

		if ($config->etagtype != 'default')
		{
			$htaccess .= "## Send ETag (selected method: {$config->etagtype})\n";

			switch ($config->etagtype)
			{
				case 'full':
					$htaccess .= <<< HTACCESS
FileETag All

HTACCESS;
					break;

				case 'sizetime':
					$htaccess .= <<< HTACCESS
FileETag MTime Size

HTACCESS;
					break;

				case 'size':
					$htaccess .= <<< HTACCESS
FileETag Size

HTACCESS;
					break;

				case 'none':
					$htaccess .= <<< HTACCESS
<IfModule mod_headers.c>
	Header unset ETag
</IfModule>

FileETag None

HTACCESS;
					break;
			}
		}

		if ($config->autoroot)
		{
			$htaccess .= <<<END
##### Redirect index.php to / -- BEGIN
RewriteCond %{THE_REQUEST} !^POST
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /index\.php\ HTTP/
RewriteRule ^index\.php$ / $redirCode
##### Redirect index.php to / -- END

END;
		}

		// If I have a rewriteBase condition, I have to append it here
		$subfolder = trim($config->rewritebase, '/') ? trim($config->rewritebase, '/') . '/' : '';

		switch ($config->wwwredir)
		{
			// non-www to www
			case 1:
				if ($hasHSTS)
				{
					$htaccess .= <<<END
##### Redirect non-www to www -- BEGIN
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$subfolder$1 $redirCode
##### Redirect non-www to www -- END


END;
				}
				else
				{
					{
						$htaccess .= <<<END
##### Redirect non-www to www -- BEGIN
# HTTP
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$subfolder$1 $redirCode
# HTTPS
RewriteCond %{HTTPS} =on [OR]
RewriteCond %{HTTP:X-Forwarded-Proto} ==https
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$subfolder$1 $redirCode
##### Redirect non-www to www -- END

END;
					}
				}

				break;

			// www to non-www
			case 2:
				if ($hasHSTS)
				{
					$htaccess .= <<<END
##### Redirect www to non-www -- BEGIN
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^(.*)$ https://%1/$subfolder$1 $redirCode
##### Redirect www to non-www -- END


END;
				}
				else
				{
					$htaccess .= <<<END
##### Redirect www to non-www -- BEGIN
# HTTP
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^(.*)$ http://%1/$subfolder$1 $redirCode
# HTTPS
RewriteCond %{HTTPS} =on [OR]
RewriteCond %{HTTP:X-Forwarded-Proto} ==https
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^(.*)$ https://%1/$subfolder$1 $redirCode
##### Redirect www to non-www -- END


END;
				}
				break;
		}

		if (!empty($config->olddomain))
		{
			$htaccess .= "##### Redirect old to new domain -- BEGIN\n";

			$domains        = trim($config->olddomain);
			$domains        = explode(',', $domains);
			$newHTTPDomain  = $config->httphost;
			$newHTTPSDomain = $config->httpshost;

			foreach ($domains as $olddomain)
			{
				$olddomain = trim($olddomain);

				if (empty($olddomain))
				{
					continue;
				}

				$httpRedirect  = $olddomain != $newHTTPDomain;
				$httpsRedirect = $olddomain != $newHTTPSDomain;
				$olddomain     = $this->escape_string_for_regex($olddomain);

				if ($httpRedirect && !$hasHSTS)
				{
					$htaccess .= <<<END
## Plain HTTP
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteCond %{HTTP_HOST} ^$olddomain [NC]
RewriteRule (.*) http://$newHTTPDomain/$1 $redirCode

END;
				}

				if ($httpsRedirect && !$hasHSTS)
				{
					$htaccess .= <<<END
## HTTPS
RewriteCond %{HTTPS} =on [OR]
RewriteCond %{HTTP:X-Forwarded-Proto} ==https
RewriteCond %{HTTP_HOST} ^$olddomain [NC]
RewriteRule (.*) https://$newHTTPSDomain/$1 $redirCode

END;
				}

				if ($httpsRedirect && $hasHSTS)
				{
					$htaccess .= <<<END
## Forced HTTPS - You have enabled the HSTS feature
RewriteCond %{HTTP_HOST} ^$olddomain [NC]
RewriteRule (.*) https://$newHTTPSDomain/$1 $redirCode

END;

				}
			}
			$htaccess .= "##### Redirect old to new domain -- END\n\n";
		}

		if (!empty($config->httpsurls))
		{
			$htaccess .= "##### Force HTTPS for certain pages -- BEGIN\n";

			foreach ($config->httpsurls as $url)
			{
				if (empty($url))
				{
					continue;
				}

				$urlesc   = '^' . $this->escape_string_for_regex($url) . '$';
				$htaccess .= <<<END
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
RewriteRule $urlesc https://{$config->httpshost}/$url $redirCode

END;
			}
			$htaccess .= "##### Force HTTPS for certain pages -- END\n\n";
		}

		$htaccess .= <<<END
##### Rewrite rules to block out some common exploits -- BEGIN
RewriteCond %{QUERY_STRING} proc/self/environ [OR]
RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|\%3D) [OR]
RewriteCond %{QUERY_STRING} base64_(en|de)code\(.*\) [OR]
RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]
RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2})
RewriteRule .* index.php [F]
##### Rewrite rules to block out some common exploits -- END

END;

		if ($config->fileinj == 1)
		{
			$htaccess .= <<<END
##### File injection protection -- BEGIN
RewriteCond %{REQUEST_METHOD} GET
RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=http[s]?:// [OR]
RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=(\.\.//?)+ [OR]
RewriteCond %{QUERY_STRING} [a-zA-Z0-9_]=/([a-z0-9_.]//?)+ [NC]
RewriteRule .* - [F]
##### File injection protection -- END


END;
		}

		// Advanced server protection
		if ($config->frontendprot == 1 || $config->backendprot == 1)
		{
			$htaccess .= "##### Advanced server protection rules exceptions -- BEGIN\n";

			if (!empty($config->exceptionfiles))
			{
				foreach ($config->exceptionfiles as $file)
				{
					$file     = '^' . $this->escape_string_for_regex($file) . '$';
					$htaccess .= <<<END
RewriteRule $file - [L]

END;
				}
			}

			if (!empty($config->exceptiondirs))
			{
				foreach ($config->exceptiondirs as $dir)
				{
					$dir      = trim($dir, '/');
					$dir      = $this->escape_string_for_regex($dir);
					$htaccess .= <<<END
RewriteCond %{REQUEST_FILENAME} !(\.php)$
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^$dir/ - [L]

END;
				}
			}

			if (!empty($config->fullaccessdirs))
			{
				foreach ($config->fullaccessdirs as $dir)
				{
					$dir      = trim($dir, '/');
					$dir      = $this->escape_string_for_regex($dir);
					$htaccess .= <<<END
RewriteRule ^$dir/ - [L]

END;
				}
			}

			$htaccess .= "##### Advanced server protection rules exceptions -- END\n\n";
		}

		$htaccess .= "##### Advanced server protection -- BEGIN\n\n";

		if ($config->frontendprot == 1 || $config->backendprot == 1)
		{
			if ($config->backendprot == 1)
			{
				$directories = $this->bugfixBackendProtectionExclusionDirectories($config->bepexdirs ?: []);
				$bedirs      = implode('|', $directories);
				$betypes     = is_array($config->bepextypes) ? $config->bepextypes : explode(',', $config->bepextypes);
				$betypes     = array_map('trim', $betypes);
				$betypes     = array_filter($betypes, function ($x) {
					return !empty($x);
				});
				$betypes     = implode('|', $betypes);
				$htaccess    .= <<<END
#### Back-end protection
RewriteRule ^administrator/?$ - [L]
RewriteRule ^administrator/index\.(php|html?)$ - [L]
RewriteRule ^administrator/($bedirs)/.*\.($betypes)$ - [L,NC]
RewriteRule ^administrator/ - [F]

END;
			}

			if ($config->frontendprot == 1)
			{
				$fedirs   = implode('|', $config->fepexdirs);
				$fetypes  = is_array($config->fepextypes) ? $config->fepextypes : explode(',', $config->fepextypes);
				$fetypes  = array_map('trim', $fetypes);
				$fetypes  = array_filter($fetypes, function ($x) {
					return !empty($x);
				});
				$fetypes  = implode('|', $fetypes);
				$htaccess .= <<<END
#### Front-end protection

END;

				if ($config->backendprot != 1)
				{
					/**
					 * When we have frontend protection enabled BUT backend protection disabled, the "Disallow access to all
					 * other front-end folders" and the "Disallow access to all other front-end files" rules will also block
					 * access to the administrator directory. Therefore we need to explicitly allow it _before_ we apply the
					 * front-end protection
					 */
					$htaccess .= <<< HTACCESS
## Prevent administrator access from being blocked by the front-end protection
RewriteRule ^administrator$ - [L]
RewriteRule ^administrator/ - [L]

HTACCESS;

				}


				$htaccess .= <<<END
## Allow limited access to additional TinyMCE plugins' HTML files
RewriteRule ^media/plg_editors_tinymce/js/plugins/.*\.(htm|html)$ - [L,NC]
## Allow limited access for certain directories with client-accessible content
RewriteRule ^($fedirs)/.*\.($fetypes)$ - [L,NC]
RewriteRule ^($fedirs)/ - [F]

END;

				$htaccess .= <<< END
## Disallow front-end access for certain Joomla! system directories (unless access to their files is allowed above)
RewriteRule ^includes/js/ - [L]
RewriteRule ^(cache|includes|language|logs|log|tmp)/ - [F]
RewriteRule ^(configuration\.php|CONTRIBUTING\.md|htaccess\.txt|joomla\.xml|LICENSE\.txt|phpunit\.xml|README\.txt|web\.config\.txt) - [F]

## Explicitly allow access to the site's index.php main entry point file
RewriteRule ^index.php(/.*){0,1}$ - [L]
## Explicitly allow access to the API application's index.php main entry point file
RewriteRule ^api/index.php(/.*){0,1}$ - [L]
## Explicitly allow access to the site's robots.txt file
RewriteRule ^robots.txt$ - [L]

## Disallow access to all other PHP files throughout the site, unless they are explicitly allowed
RewriteCond %{REQUEST_FILENAME} (\.php)$
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule (.*\.php)$ - [F]

END;
			}
		}

		// Advanced server protection rule exceptions also bypass Disable Client-side Risky Behaviour features
		if (($config->bestaticrisks == 1 && $config->backendprot == 1) || ($config->festaticrisks == 1 && $config->frontendprot == 1))
		{
			if ($config->bestaticrisks == 1 && $config->backendprot == 1)
			{
				$htaccess .= <<< END
#### Disable client-side risky behavior in backend static content
SetEnvIf Request_URI "^/administrator/($bedirs)/.*\.($betypes)$" disable_risky_behaviour

END;
			}

			if ($config->festaticrisks == 1 && $config->frontendprot == 1)
			{
				$htaccess .= <<< END
#### Disable client-side risky behavior in frontend static content
SetEnvIf Request_URI "^/($fedirs)/.*\.($fetypes)$" disable_risky_behaviour

END;
			}

			$htaccess .= "##### Always allow TinyMCE plugin files to load scripts (they need to)\n";
			if ($config->bestaticrisks && $config->backendprot)
			{
				$htaccess .= "SetEnvIf Request_URI \"^/media/plg_editors_tinymce/js/plugins/.*\.($betypes)$\" !disable_risky_behaviour\n";
			}
			if ($config->festaticrisks && $config->frontendprot)
			{
				$htaccess .= "SetEnvIf Request_URI \"^/media/plg_editors_tinymce/js/plugins/.*\.($fetypes)$\" !disable_risky_behaviour\n";
			}

			$htaccess .= "\n##### Advanced server protection rules exceptions also bypass the “disable client-side risky behavior” features -- BEGIN\n";

			foreach ($config->exceptionfiles as $file)
			{
				$file     = $this->escape_string_for_regex(ltrim($file, '/'));
				$htaccess .= "SetEnvIf Request_URI \"^/$file$\" !disable_risky_behaviour\n";
			}

			foreach ($config->exceptiondirs as $dir)
			{
				$dir      = trim($dir, '/');
				$dir      = $this->escape_string_for_regex($dir);

				if ($config->bestaticrisks && $config->backendprot == 1)
				{
					$htaccess .= "SetEnvIf Request_URI \"^/$dir/.*\.($betypes)$\" !disable_risky_behaviour\n";
				}
				if ($config->festaticrisks && $config->frontendprot == 1)
				{
					$htaccess .= "SetEnvIf Request_URI \"^/$dir/.*\.($fetypes)$\" !disable_risky_behaviour\n";
				}
			}

			foreach ($config->fullaccessdirs as $dir)
			{
				$dir      = trim($dir, '/');
				$dir      = $this->escape_string_for_regex($dir);

				if ($config->bestaticrisks && $config->backendprot == 1)
				{
					$htaccess .= "SetEnvIf Request_URI \"^/$dir/.*\.($betypes)$\" !disable_risky_behaviour\n";
				}
				if ($config->festaticrisks && $config->frontendprot == 1)
				{
					$htaccess .= "SetEnvIf Request_URI \"^/$dir/.*\.($fetypes)$\" !disable_risky_behaviour\n";
				}
			}


			$htaccess .= "##### Advanced server protection rules exceptions also bypass the “disable client-side risky behavior” features -- END\n\n";

			$htaccess .= <<< HTACCESS

# Apply the "Disable client-side risky behavior" features
Header always set Content-Security-Policy "default-src 'self'; script-src 'none';" env=disable_risky_behaviour

HTACCESS;
		}

		if ($config->leftovers == 1)
		{
			$htaccess .= <<<END
## Disallow access to htaccess.txt, php.ini, .user.ini and configuration.php-dist
RewriteRule ^(htaccess\.txt|configuration\.php-dist|php\.ini|\.user\.ini)$ - [F]

END;
		}

		if ($config->frontendprot == 1)
		{
			$htaccess .= <<<END
# Disallow access to all other front-end folders
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !^/
RewriteRule .* - [F]

# Disallow access to all other front-end files
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule !^index.php$ - [F]

END;

		}

		if ($config->clickjacking == 1)
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<< ENDCONF
## Protect against clickjacking
<IfModule mod_headers.c>

	Header $action X-Frame-Options SAMEORIGIN

	# The `X-Frame-Options` response header should be send only for
	# HTML documents and not for the other resources.

	<FilesMatch "\.(appcache|atom|bbaw|bmp|crx|css|cur|eot|f4[abpv]|flv|geojson|gif|htc|ico|jpe?g|js|json(ld)?|m4[av]|manifest|map|mp4|oex|og[agv]|opus|otf|pdf|png|rdf|rss|safariextz|svgz?|swf|topojson|tt[cf]|txt|vcard|vcf|vtt|webapp|web[mp]|woff2?|xloc|xml|xpi)$">
		Header unset X-Frame-Options
	</FilesMatch>

</IfModule>

ENDCONF;
		}

		if ($config->reducemimetyperisks == 1)
		{
			$htaccess .= <<< HTACCESS
## Reduce MIME type security risks
<IfModule mod_headers.c>
	Header set X-Content-Type-Options "nosniff"
</IfModule>

HTACCESS;
		}

		if ($config->reflectedxss == 1)
		{
			$htaccess .= <<< HTACCESS
## Reflected XSS prevention
<IfModule mod_headers.c>
Header set X-XSS-Protection "1; mode=block"
</IfModule>

# mod_headers cannot match based on the content-type, however,
# the X-XSS-Protection response header should be sent only for
# HTML documents and not for the other resources.

<IfModule mod_headers.c>
	<FilesMatch "\.(appcache|atom|bbaw|bmp|crx|css|cur|eot|f4[abpv]|flv|geojson|gif|htc|ico|jpe?g|js|json(ld)?|m4[av]|manifest|map|mp4|oex|og[agv]|opus|otf|pdf|png|rdf|rss|safariextz|svgz?|swf|topojson|tt[cf]|txt|vcard|vcf|vtt|webapp|web[mp]|webmanifest|woff2?|xloc|xml|xpi)$">
		Header unset X-XSS-Protection
	</FilesMatch>
</IfModule>

HTACCESS;
		}

		if ($config->svgneutralise)
		{
			$htaccess .= <<< HTACCESS
## Neutralize scripts in SVG files
<FilesMatch "\.svg$">
  <IfModule mod_headers.c>
    Header always set Content-Security-Policy "script-src 'none'"
  </IfModule>
</FilesMatch>

HTACCESS;
		}

		if ($config->noserversignature == 1)
		{
			$htaccess .= <<< HTACCESS
## Remove Apache and PHP version signature
<IfModule mod_headers.c>
	Header always unset X-Powered-By
	Header always unset X-Content-Powered-By
</IfModule>

ServerSignature Off

HTACCESS;
		}

		if ($config->notransform == 1)
		{
			$htaccess .= <<< HTACCESS
## Prevent content transformation
<IfModule mod_headers.c>
	Header merge Cache-Control "no-transform"
</IfModule>

HTACCESS;
		}

		$htaccess .= "##### Advanced server protection -- END\n\n";

		if ($config->hstsheader == 1)
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<<END
## HSTS Header - See http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
<IfModule mod_headers.c>
SetEnvIfExpr "%{HTTPS}='on'" USE_HSTS_HEADER
SetEnvIf X-Forwarded-Proto "https" USE_HSTS_HEADER
Header $action Strict-Transport-Security "max-age=31536000" env=USE_HSTS_HEADER
</IfModule>

END;
		}
		elseif ($config->hstsheader == 2)
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<<END
## HSTS Header - See http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
<IfModule mod_headers.c>
SetEnvIfExpr "%{HTTPS}='on'" USE_HSTS_HEADER
SetEnvIf X-Forwarded-Proto "https" USE_HSTS_HEADER
Header $action Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" env=USE_HSTS_HEADER
</IfModule>

END;
		}

		if ($config->notracetrack == 1)
		{
			/**
			 * Note to self: using [TraceEnable](https://httpd.apache.org/docs/2.4/mod/core.html#traceenable) will NOT work in
			 * a .htaccess file as it's only allowed in server and vhost configuration. Using rewrite rules is the only way to
			 * block TRACE requests in .htaccess.
			 */

			$tmpRedirCode = $serverCaps->customCodes ? '[R=405,L]' : '[F,L]';
			$htaccess     .= <<<END
## Disable HTTP methods TRACE and TRACK (protect against XST)
RewriteCond %{REQUEST_METHOD} ^TRACE
RewriteRule ^ - $tmpRedirCode

END;
		}

		if ($config->cors == 1)
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<<END
## Explicitly enable Cross-Origin Resource Sharing (CORS) -- See http://enable-cors.org/
<IfModule mod_headers.c>
	Header $action Access-Control-Allow-Origin "*"
	Header $action Timing-Allow-Origin "*"
</IfModule>

END;
		}
		elseif ($config->cors == -1)
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<<END
## Explicitly disable Cross-Origin Resource Sharing (CORS) -- See http://enable-cors.org/
<IfModule mod_headers.c>
	Header $action Cross-Origin-Resource-Policy "same-origin"
</IfModule>

END;

		}

		if ($config->referrerpolicy !== '-1')
		{
			$action   = version_compare($apacheVersion, '2.0', 'ge') ? 'always set' : 'set';
			$htaccess .= <<<END
## Referrer-policy
<IfModule mod_headers.c>
	Header $action Referrer-Policy "{$config->referrerpolicy}"
</IfModule>

END;
		}

		if ($config->utf8charset == 1)
		{
			$htaccess .= <<<END
## Set the UTF-8 character set as the default
#  Serve all resources labeled as `text/html` or `text/plain`
#  with the media type `charset` parameter set to `UTF-8`.

AddDefaultCharset utf-8

# Serve the following file types with the media type `charset`
# parameter set to `UTF-8`.
#
# https://httpd.apache.org/docs/current/mod/mod_mime.html#addcharset

<IfModule mod_mime.c>
	AddCharset utf-8 .atom \
					 .bbaw \
					 .css \
					 .geojson \
					 .js \
					 .json \
					 .jsonld \
					 .rdf \
					 .rss \
					 .topojson \
					 .vtt \
					 .webapp \
					 .xloc \
					 .xml
</IfModule>

END;
		}

		$htaccess .= <<<END
##### Joomla! core SEF Section -- BEGIN

END;

		$htaccess .= <<< APACHE
# -- SEF URLs for the API application
RewriteCond %{REQUEST_URI} ^/api/
RewriteCond %{REQUEST_URI} !^/api/index\.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* api/index.php [L]

# -- SEF URLs for the public frontend application

APACHE;

		$htaccess .= <<<END
##### Joomla! core SEF Section -- BEGIN
RewriteCond %{REQUEST_URI} !^/index\.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [L]
##### Joomla! core SEF Section -- END


END;

		$htaccess .= "\n\n" . $config->custfoot . "\n";

		return $htaccess;
	}
}