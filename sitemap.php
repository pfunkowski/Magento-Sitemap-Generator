<?php
//Instantiate magento
require_once(dirname(__FILE__).'/../app/Mage.php');
Mage::app();

//Output
echo "\n\nGenerating sitemaps.. please wait..\n\n";

//database adapters
$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

//Loop through the sitemaps
foreach($read->fetchAll("Select * from `sklep_sitemap`") as $sitemap)
{
	//Get the path
	$filePath = dirname(__FILE__).'/..'.$sitemap["sitemap_path"].$sitemap["sitemap_filename"];

	//Generate the sitemap
	if(generateSitemap($filePath, $sitemap["store_id"]))
	{
		//Output success message
		echo "Sitemap generated at [".str_replace("shell/../", "", $filePath)."]\n";

		//Update the time
		$write->update(
	        "sklep_sitemap",
	        array("sitemap_time" => Varien_Date::now()),
	        "sitemap_id=".$sitemap["sitemap_id"]
		);
	}
}

//Output
echo "\n\nAll done!\n";

//Generate a sitemap
function generateSitemap($filePath, $storeId)
{
	//Set the stat priorities
	$page_priority = '1';
	$category_priority = '0.5';
	$product_priority = '0.5';

	try {
		//Set the current store
		Mage::app()->setCurrentStore($storeId);

		//Create a new sitemap
		$sitemap = new PT_Magento_Sitemap($filePath);
		
		//Get the pages
		$collection = Mage::getModel('cms/page')
							->getCollection()
							->addStoreFilter($storeId)
							->addFieldToFilter('is_active', 1);

		//Add the pages				
		foreach($collection as $page)
			$sitemap->addUrl(Mage::getBaseUrl().$page->getIdentifier(), $page_priority, $page->getUpdateTime());
		
		//Clear
		unset($collection);
		
		//Get the categories
		$rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
		$collection = Mage::getModel('catalog/category')
					        ->getCollection()
							->setStoreId($storeId)
					        ->addAttributeToSelect('*')
							->addFieldToFilter('path', array('like'=> "1/$rootId/%"))
							->addFieldToFilter('include_in_menu', 1)
							->addFieldToFilter('is_active', 1)
					        ->addIsActiveFilter();
		
		//Add the categories		        
		foreach($collection as $category)
			$sitemap->addUrl($category->getUrl(), $category_priority, $category->getUpdatedAt());
		
		//Clear
		unset($collection);

		//Get the products
		$collection = Mage::getModel('catalog/product')
						->getCollection()
						->addStoreFilter($storeId)
						->addAttributeToSelect('*')
						->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
						->addAttributeToFilter('visibility', array(
							Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
							Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
						));
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
		//Add the products
		foreach($collection as $product)
			if (Mage::getBaseUrl('media').'catalog/product'.$product->getImage()!==false && $product->getImage() !== 'no_selection' && $product->getImage() !== '' && $product->getImage() !== null && $product->isAvailable())
				$sitemap->addUrl($product->getProductUrl(), $product_priority, $product->getUpdatedAt());
		
		//Clear
		unset($collection);
			
		//Generate and write the sitemap.
		$sitemap->generate();

		//Return
		return true;
	} catch(Exception $e) {
		//Output the error
		die($e->getMessage());
	}

	//Fallback
	return false;
}

//The sitemap class
class PT_Magento_Sitemap {

	protected $file;
	protected $filename;

	protected $urls;
	
	public function __construct($filename)
	{	
		$this->urls = array();
		$this->filename = $filename;
	}
	
	public function formatDate($datetime)
	{
		$timestamp = strtotime($datetime);
		return date('Y-m-d', $timestamp);
	}
	
	public function addUrl($loc, $priority = '1', $lastmod = NULL)
	{
		$this->urls[] = array(
			'loc' => $loc,
			'priority' => $priority,
			'lastmod' => ( $lastmod ? $this->formatDate($lastmod) : NULL ),
		);
		
		return true;
	}
	
	public function generate()
	{
		if ( ! $this->file ) {
			$this->openFile();
		}
	
		if ( ! $this->urls ) {
			return false;
		}
	
		foreach ( $this->urls as $url )  {
			$this->writeUrl($url);
		}
		
		$this->closeFile();
		
		return true;
	}
	
	private function openFile()
	{
		$this->file = fopen($this->filename, 'w');
		
		if ( ! $this->file ) {
			throw new Exception('Sitemap file '.$file.' is not writable');
			return false;
		}
		
		fwrite($this->file, '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL);
		fwrite($this->file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL);
		
		return true;
	}
	
	private function closeFile()
	{
		if ( $this->file ) {
			fwrite($this->file, "</urlset>");
			fclose($this->file);
		}
		 
		return true;
	}
	
	private function writeUrl($url)
	{
		fwrite($this->file,  "\t".'<url>'."\n".
			   "\t\t".'<loc>'.$url['loc'].'</loc>'."\n".
			   "\t\t".'<priority>'.$url['priority'].'</priority>'."\n".
			   ( $url['lastmod'] ? "\t\t".'<lastmod>'.$url['lastmod'].'</lastmod>'."\n" : '' ).
			   "\t".'</url>'."\n");
	}
}
?>
