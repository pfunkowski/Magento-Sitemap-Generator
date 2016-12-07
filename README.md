# Magento Sitemap Shell/php generator

Original script by [Papertank](https://github.com/papertank) - Forked by [Maaggel](https://github.com/maaggel) @ [Klean](https://github.com/klean)

Using a custom class and Magento specific (collections) code, this simple script is designed to be used via the command line / cron job to generate a Google compatible XML sitemap.

## Installation

Upload the `sitemap.php` file to your Magento `shell` folder (within the root).

The sitemap generator can then be run via the command line with `php shell/sitemap.php` or set up as a regular task via crontab.

## Output

Running this script, will loop through the sitemaps setup in Magento, and generate the `sitemap.xml` files. The `Last Time Generated` will be updated aswell.

The XML file will contain:

  * All CMS pages which are have status *Active*
  * All Catalog Categories which have status *Enabled*
  * All Catalog Products which are *Enabled* and have visibility "*Catalog*" or "*Catalog, Search*"

## Configuration

You can change the **path** of the required Magento `Mage.php` by changing the line

	require_once(dirname(__FILE__).'/../app/Mage.php');

You can change the **priority** field of the different url types in the outputted `sitemap.xml` files by updating the lines in the function `generateSitemap()`

	$page_priority = '1';
	$category_priority = '0.5';
	$product_priority = '0.5';

## Tested versions

This is tested and should be working on Magento 1.9.3.1 and below.

It might very well work on newer versions aswell, but no guarantee is given.
  	
## Troubleshooting

The script can be used with a single storeview, or multiple. Just add the sitemaps in magento and run this script.

Feel free to submit an issue if you find any problems or bugs, but since this is added just to help - please don't expect any direct support.

## Development

- Source hosted at [GitHub](https://github.com/klean/magento-php-sitemap)
- Please fork and make suggested updates and improvements

## Authors

- Multistore update: [Maaggel](https://github.com/maaggel) @ [Klean](https://github.com/klean)
- Initial work: [Papertank](https://github.com/papertank)