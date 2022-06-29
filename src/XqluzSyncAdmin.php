<?php

/**
 * Admin screen functionality
 *
 * @package XqluzSync
 */

namespace XqluzSync;

defined('ABSPATH') || exit;

/**
 * Admin class
 *
 * @package XqluzSync
 */
class XqluzSyncAdmin
{

	public $api = "https://api.unleashedsoftware.com/";
	public $apiId = "7f057946-85d2-4054-ba44-28ee24291882";
	public $apiKey = "umYTexpzj6P8M9dL9VYXLOl8FD3SFaGKaUOUbWBLWrmXzHDVnR08Rs0mSzU07vC6MmsRY9lccXUdxJxfFQ==";

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init()
	{
		$this->api_init();

		// Register pages
		add_action('admin_menu', array($this, 'add_admin_menu'));

		// Enqueue Scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Ajax Actions
		add_action('wp_ajax_xqluz_get_wc_products', array($this, 'xqluz_get_wc_products'));
		add_action('wp_ajax_xqluz_update_wc_products', array($this, 'xqluz_update_wc_products'));

		add_filter('manage_edit-product_columns', array($this,'product_update_col'));
		add_action('manage_product_posts_custom_column', array($this,'product_update_col_data'), 2);
	}

	/**
	 * Admin Enqueue Scripts
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ('toplevel_page_xqluzsync_blocks' === $hook) {
			$admin_deps = include_once plugin_dir_path(XQLUZ_SYNC_PLUGIN_FILE) . '/app/build/adminJS.asset.php';
			wp_enqueue_script('xqluz-sync-admin', plugin_dir_url(XQLUZ_SYNC_PLUGIN_FILE) . 'app/build/adminJS.js', $admin_deps['dependencies'], $admin_deps['version'], true);
		}

		wp_enqueue_script( 'common-admin-xqluz', plugin_dir_url(XQLUZ_SYNC_PLUGIN_FILE) . 'app/js/admin.js', ['jquery'], '1.0.0', true );

		wp_enqueue_style( 'xqluz-admin-css', plugin_dir_url(XQLUZ_SYNC_PLUGIN_FILE) . 'app/admin/admin.css'  );
	}

	public function api_init() {
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/products', array(
			'methods' => 'GET',
			'callback' => array($this,'products'),
			) );
		} );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/productsasync', array(
			'methods' => 'GET',
			'callback' => array($this,'productsasync'),
			) );
		} );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/singleproduct', array(
			'methods' => 'GET',
			'callback' => array($this,'singleproduct'),
			) );
		} );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/multiproducts', array(
			'methods' => 'GET',
			'callback' => array($this,'multiproducts'),
			) );
		} );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/imageupdate', array(
			'methods' => 'GET',
			'callback' => array($this,'imageupdate'),
			) );
		} );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'swzunleashedapi/v1', '/getapiproduct', array(
			'methods' => 'GET',
			'callback' => array($this,'getapiproduct'),
			) );
		} );
	}

	/**
	 * Add XqluzSync Menu
	 *
	 * @return void
	 */
	public function add_admin_menu()
	{
		$ADMIN_ICON = base64_encode('<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0)"><path d="M25 8.73L24 7L4 41.64H6H16H18L31 19.12L25 8.73Z" fill="#69758F"/><path d="M23 8.73L24 7L44 41.64H42H32H30L17 19.12L23 8.73Z" fill="#69758F"/><path d="M23 8.73L17 19.12L24 31.25L31 19.12L25 8.73L24 7L23 8.73Z" fill="#A3ACBF"/></g><defs><clipPath id="clip0"><rect width="40" height="34.64" fill="white" transform="translate(4 7)"/></clipPath></defs></svg>');
		add_menu_page(
			esc_html__('XqluzSync', 'xqluzsync'),
			esc_html__('XqluzSync', 'xqluzsync'),
			'manage_options',
			'xqluzsync_blocks',
			array($this, 'render_options_page'),
			'data:image/svg+xml;base64,' . $ADMIN_ICON,
			40
		);
	}

	/**
	 * Set Script Translations
	 *
	 * @return void
	 */
	public function set_script_translations()
	{
		wp_set_script_translations('xqluzsync', 'xqluzsync'); // Blocks.
		wp_set_script_translations('xqluzsync-admin', 'xqluzsync'); // XqluzSync Page.
	}

	/**
	 * render options page
	 *
	 * @return void
	 */
	public function render_options_page()
	{
		echo '<div id="xqluzSyncAdminPageRoot"></div>';
	}

	function products()
	{
		$args     = array('post_type' => 'product', 'posts_per_page' => 100);
		$wc_products = get_posts($args);
		foreach ($wc_products as $wcproductItem) {
			$wcproduct = wc_get_product($wcproductItem->ID);
			$skuCode = $wcproduct->sku;
			//$skuCode = 'AP175A-Blue';		
			$request = 'ProductCode=' . $skuCode;
			if ($skuCode) {
				$status = $this->updateProductPrice($skuCode, $request, $wcproductItem->ID);
				if ($status) {
					echo json_encode(array("sku" => $skuCode));
				}
			}
		}
	}
	function multiproducts()
	{
		set_time_limit(0);
		global $wpdb;

		$customer_data = array();
		$message = array();
		$customers = $this->getCustomers();

		$totalPages = $customers['Pagination']['NumberOfPages'];
		if (isset($customers['Items']) && !empty($customers['Items'])) {
			foreach ($customers['Items'] as $customer) {
				$customercode = $customer['CustomerCode'];
				$customer_data[$customercode]["usertype"] = $customer['SellPriceTier'];
			}

			for ($i = 2; $i <= $totalPages; $i++) {
				$customers = $this->getCustomers("json", $i);
				if (isset($customers['Items']) && !empty($customers['Items'])) {
					foreach ($customers['Items'] as $customer) {
						$customercode = $customer['CustomerCode'];
						$customer_data[$customercode]["usertype"] = $customer['SellPriceTier'];
					}
				}
			}
		}
		$totalCustomers = count($customer_data);

		$wc_products = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type='product' and post_status='publish' and uleashed_update_status = %d LIMIT 0,10", 0));


		if (empty($wc_products)) {
			$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET uleashed_update_status = %d", 0));
			$wc_products = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type='product' and post_status='publish' and uleashed_update_status = %d LIMIT 0,10", 0));
		}

		foreach ($wc_products as $wcproductItem) {
			$wcproduct = wc_get_product($wcproductItem->ID);
			$skuCode = $wcproduct->sku;
			//$skuCode = 'AP175A-Blue';
			$productId = $wcproductItem->ID;

			if ($skuCode) {
				$request = 'ProductCode=' . $skuCode;
				if (empty($productId)) {
					$productId = wc_get_product_id_by_sku($skuCode);
				}

				$message[$skuCode] = array();


				$execut = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET uleashed_update_status = %d WHERE ID=%d", 1, $productId));

				$product = array();
				$product_data = $this->getProducts($skuCode, $request);

				if (isset($product_data['Items']) && !empty($product_data['Items'])) {
					$product = $product_data['Items'][0];
					$imageUrl = $product_data['Items'][0]['ImageUrl'];
				} else {
					$message[$skuCode] = "Product not available";
					continue;
				}


				if ($imageUrl) {
					$attach_id = $this->insertImageWp($imageUrl);
					update_post_meta($productId, '_thumbnail_id', $attach_id);
				}

				$imagesUrl = $product_data['Items'][0]['Images'];
				$galleryImages = array();
				if ($imagesUrl) {
					foreach ($imagesUrl as $imgUrl) {
						if ($imgUrl['IsDefault'] != 1) {
							$gal_attach_id = $this->insertImageWp($imgUrl['Url']);
							$galleryImages[] = $gal_attach_id;
						}
					}
				}
				if ($galleryImages) {
					update_post_meta($productId, '_product_image_gallery',  implode(',', $galleryImages));
				}

				$priceWithRole = array();
				$priceWithRole['Regular'] = $product['SellPriceTier1']['Value'];
				$priceWithRole['Group 2'] = $product['SellPriceTier2']['Value'];
				$priceWithRole['Electrical'] = $product['SellPriceTier3']['Value'];
				$priceWithRole['Cash'] = $product['SellPriceTier4']['Value'];
				$priceWithRole['Sell Price Tier 5'] = $product['SellPriceTier5']['Value'];
				$priceWithRole['Sell Price Tier 6'] = $product['SellPriceTier6']['Value'];
				$priceWithRole['Sell Price Tier 7'] = $product['SellPriceTier7']['Value'];
				$priceWithRole['Sell Price Tier 8'] = $product['SellPriceTier8']['Value'];
				$priceWithRole['Sell Price Tier 9'] = $product['SellPriceTier9']['Value'];
				$priceWithRole['Sell Price Tier 10'] = $product['SellPriceTier10']['Value'];



				$specialPrices = array();
				$productPrices = $this->getProductPrices($skuCode, $request);
				$totalPages = $productPrices['Pagination']['NumberOfPages'];
				if (isset($productPrices['Items']) && !empty($productPrices['Items'])) {

					foreach ($productPrices['Items'] as $item) {
						$customerCode = $item['Customer']['CustomerCode'];
						$specialPrices[$customerCode] = $item['CustomerPrice'];
					}
					for ($i = 2; $i <= $totalPages; $i++) {
						$productPrices = $this->getProductPrices($skuCode, $request);
						if (isset($productPrices['Items']) && !empty($productPrices['Items'])) {
							foreach ($productPrices['Items'] as $item) {
								$customerCode = $item['Customer']['CustomerCode'];
								$specialPrices[$customerCode] = $item['CustomerPrice'];
							}
						}
					}
				} else {
					$message[$skuCode] = "Product not available";
				}

				$postdata = array();
				if ($totalCustomers) {
					foreach ($customer_data as $customerCode => $customer) {
						$userType = $customer['usertype'];
						$metaKey = $customerCode . '_wholesale_price';
						$metaValue = "";
						if (isset($priceWithRole[$userType])) {
							$metaValue = $priceWithRole[$userType];
						}
						if (isset($specialPrices[$customerCode])) {
							$metaValue = $specialPrices[$customerCode];
						}
						$postdata[$metaKey] = $metaValue;
						try {
							update_post_meta($productId, $metaKey, $metaValue);
							$message[$skuCode] = "Product updated";
						} catch (Exception $e) {
							// $e->getMessage;
							$message[$skuCode] = $e->getMessage;
						}
					}
				}
			}
		}
		echo json_encode($message);
		exit();
	}
	function singleproduct()
	{
		$skuCode = isset($_REQUEST['sku']) ? $_REQUEST['sku'] : "";
		$imageUrl = '';
		$productId = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : "";
		if ($productId) {
			$wcproduct = wc_get_product($productId);
			$skuCode = $wcproduct->sku;
		}

		if ($skuCode) {
			$customer_data = array();
			$customers = $this->getCustomers();
			/* echo "<pre>";
			print_R($customers);
			echo "</pre>";exit; */
			$totalPages = $customers['Pagination']['NumberOfPages'];
			if (isset($customers['Items']) && !empty($customers['Items'])) {
				foreach ($customers['Items'] as $customer) {
					$customercode = $customer['CustomerCode'];
					$customer_data[$customercode]["usertype"] = $customer['SellPriceTier'];
				}

				for ($i = 2; $i <= $totalPages; $i++) {
					$customers = $this->getCustomers("json", $i);
					if (isset($customers['Items']) && !empty($customers['Items'])) {
						foreach ($customers['Items'] as $customer) {
							$customercode = $customer['CustomerCode'];
							$customer_data[$customercode]["usertype"] = $customer['SellPriceTier'];
						}
					}
				}
			}
			$totalCustomers = count($customer_data);

			/* echo "<pre>";
			print_R($customer_data);
			echo "</pre>";exit; */

			$request = 'ProductCode=' . $skuCode . '&includeAttributes=true';
			if (empty($productId)) {
				$productId = wc_get_product_id_by_sku($skuCode);
			}

			$product = array();
			$product_data = $this->getProducts($skuCode, $request);
			// echo "<pre>";
			// print_r($product_data);
			// echo "</pre>";exit;
			if (isset($product_data['Items']) && !empty($product_data['Items'])) {
				$product = $product_data['Items'][0];
				$imageUrl = $product_data['Items'][0]['ImageUrl'];
			}

			if ($imageUrl) {
				$attach_id = $this->insertImageWp($imageUrl);
				update_post_meta($productId, '_thumbnail_id', $attach_id);
			}

			$product_attr_array = isset( $product_data['AttributeSet']['Attributes'] ) ? $product_data['AttributeSet']['Attributes'] : array();

			$active_array = array_filter( $product_attr_array, function($attr) {
				return $attr['Name'] === 'Active' && '1' == $attr['Value'];
			} );

			// echo "<pre>";
			// print_r($active_array);
			// echo "</pre>";exit;

			wp_update_post([
				'ID' => $productId,
				'post_title' => $product['ProductDescription'],
				'post_status' => ! empty( $active_array ) ? 'draft' : 'publish', 
			]);

			// Get and update unit of measure.
			$unit_of_measure = isset( $product['UnitOfMeasure'] ) ? $product['UnitOfMeasure']['Name'] : '';

			// Save to meta.
			update_post_meta( $productId, '_product_unit_of_measure', $unit_of_measure );

			// Update Regular Price.
			$reg_price = $product['DefaultSellPrice'];
			update_post_meta( $productId, '_regular_price', $reg_price );
			update_post_meta( $productId, '_price', $reg_price );

			$imagesUrl = $product_data['Items'][0]['Images'];
			$galleryImages = array();
			if ($imagesUrl) {
				foreach ($imagesUrl as $imgUrl) {
					if ($imgUrl['IsDefault'] != 1) {
						$gal_attach_id = $this->insertImageWp($imgUrl['Url']);
						$galleryImages[] = $gal_attach_id;
					}
				}
			}
			if ($galleryImages) {
				update_post_meta($productId, '_product_image_gallery',  implode(',', $galleryImages));
			}
			$priceWithRole = array();
			$priceWithRole['Regular'] = $product['SellPriceTier1']['Value'];
			$priceWithRole['Group 2'] = $product['SellPriceTier2']['Value'];
			$priceWithRole['Electrical'] = $product['SellPriceTier3']['Value'];
			$priceWithRole['Cash'] = $product['SellPriceTier4']['Value'];
			$priceWithRole['Sell Price Tier 5'] = $product['SellPriceTier5']['Value'];
			$priceWithRole['Sell Price Tier 6'] = $product['SellPriceTier6']['Value'];
			$priceWithRole['Sell Price Tier 7'] = $product['SellPriceTier7']['Value'];
			$priceWithRole['Sell Price Tier 8'] = $product['SellPriceTier8']['Value'];
			$priceWithRole['Sell Price Tier 9'] = $product['SellPriceTier9']['Value'];
			$priceWithRole['Sell Price Tier 10'] = $product['SellPriceTier10']['Value'];

			/* echo "<pre>";
			print_r($priceWithRole);
			echo "</pre>";exit; */

			$specialPrices = array();
			$productPrices = $this->getProductPrices($skuCode, $request);
			$totalPages = $productPrices['Pagination']['NumberOfPages'];
			if (isset($productPrices['Items']) && !empty($productPrices['Items'])) {

				foreach ($productPrices['Items'] as $item) {
					$customerCode = $item['Customer']['CustomerCode'];
					$specialPrices[$customerCode] = $item['CustomerPrice'];
				}
				for ($i = 2; $i <= $totalPages; $i++) {
					$productPrices = $this->getProductPrices($skuCode, $request);
					if (isset($productPrices['Items']) && !empty($productPrices['Items'])) {
						foreach ($productPrices['Items'] as $item) {
							$customerCode = $item['Customer']['CustomerCode'];
							$specialPrices[$customerCode] = $item['CustomerPrice'];
						}
					}
				}
			}
			/* echo "<pre>";
			print_r($specialPrices);
			echo "</pre>";exit; */
			$postdata = array();
			if ($totalCustomers) {
				foreach ($customer_data as $customerCode => $customer) {
					$userType = $customer['usertype'];
					$metaKey = $customerCode . '_wholesale_price';
					$metaValue = "";
					if (isset($priceWithRole[$userType])) {
						$metaValue = $priceWithRole[$userType];
					}
					if (isset($specialPrices[$customerCode])) {
						$metaValue = $specialPrices[$customerCode];
					}
					$postdata[$metaKey] = $metaValue;
					try {
						update_post_meta($productId, $metaKey, $metaValue);
					} catch (Exception $e) {
						echo $e->getMessage;
					}
				}
			}
			exit($skuCode);
		}
	}
	function updateProductPrice($skuCode, $request, $productId)
	{
		$status = false;
		$productPrices = $this->getProductPrices($skuCode, $request);

		if ($productId) {
			if (isset($productPrices['Items']) && !empty($productPrices['Items'])) {
				$status = true;
				foreach ($productPrices['Items'] as $item) {
					$customerCode = $item['Customer']['CustomerCode'];
					$customerPrice = $item['CustomerPrice'];
					$metaKey = $customerCode . '_wholesale_price';
					update_post_meta($productId, $metaKey, $customerPrice);
				}
			}
		}
		return $status;
	}
	function getSignature($request, $key)
	{
		return base64_encode(hash_hmac('sha256', $request, $key, true));
	}
	function getCurl($id, $key, $signature, $endpoint, $requestUrl, $format = 'json')
	{
		$url = $this->api . $endpoint . $requestUrl;


		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/$format",
			"Accept: application/$format", "api-auth-id: $id", "api-auth-signature: $signature"
		));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		// these options allow us to read the error message sent by the API
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_HTTP200ALIASES, range(400, 599));

		return $curl;
	}
	function getJson($id, $key, $endpoint, $request)
	{
		// GET it, decode it, return it

		return json_decode($this->get($id, $key, $endpoint, $request, "json"), true);
	}
	function get($id, $key, $endpoint, $request, $format)
	{
		$requestUrl = "";

		if (!empty($request)) $requestUrl = "?$request";

		try {
			// calculate API signature
			$signature = $this->getSignature($request, $key);
			// create the curl object
			$curl = $this->getCurl($id, $key, $signature, $endpoint, $requestUrl, $format);
			// GET something
			$curl_result = curl_exec($curl);
			error_log($curl_result);
			curl_close($curl);
			return $curl_result;
		} catch (Exception $e) {
			error_log('Error: ' + $e);
		}
	}
	function getCustomers($format = "", $page = 1)
	{

		return $this->getJson($this->apiId, $this->apiKey, "Customers/Page/" . $page, "");
	}
	function getProducts($skuCode, $request)
	{

		return $this->getJson($this->apiId, $this->apiKey, "Products", $request);
	}
	function getProductPrices($skuCode, $request, $page = 1)
	{

		return $this->getJson($this->apiId, $this->apiKey, "ProductPrices/Page/" . $page, $request);
	}
	function product_update_col($columns)
	{
		$new_columns = (is_array($columns)) ? $columns : array();
		$new_columns['UNLEASHUPDATE'] = 'Unleashed Update';
		$new_columns['UNITOFMEASURE'] = __( 'Unit of Measure', 'txtdomain' );
		return $new_columns;
	}


	function product_update_col_data($column)
	{
		global $post;

		if ($column == 'UNLEASHUPDATE') {
			echo '<a class="button xqlupdate-single-data" data-productid="'. esc_attr( $post->ID ) .'" target="_blank" href="">Update Images & Price</a>';
		}

		if ( $column === 'UNITOFMEASURE' ) {
			$unit_of_measure = get_post_meta( $post->ID, '_product_unit_of_measure', true );
			echo $unit_of_measure;
		}
	}

	function xqluz_get_wc_products()
	{
		$paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 10,
			'paged' => $paged
		);
		$loop = new \WP_Query($args);

		if ($loop->posts) {
			$products['items'] = $loop->posts;
			$products['next_page'] = $paged + 1;
			$products['status'] = 1;
		} else {
			$products['status'] = 0;
		}

		

		echo json_encode($products);
		exit;
	}
	function xqluz_update_wc_products()
	{
		$this->singleproduct();
		exit;
	}
	/**
	 * Update the image for product
	 */
	function imageupdate()
	{
		$skuCode = "CL16-10";
		//$skuCode = (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) ? $_REQUEST['sku'] : "";
		if (empty($skuCode)) {
			echo "Please select sku";
			exit;
		}
		$request = 'ProductCode=' . $skuCode;
		$product_data = $this->getProducts($skuCode, $request);

		if (isset($product_data['Items'][0])) {
			$imageUrl = $product_data['Items'][0]['ImageUrl'];
			if ($imageUrl) {
				$productId = wc_get_product_id_by_sku($skuCode);
				$attach_id = $this->insertImageWp($imageUrl);
				update_post_meta($productId, '_thumbnail_id', $attach_id);
				echo "Image imported successfully";
			} else {
				echo "Image not available or imported";
			}

			$imagesUrl = $product_data['Items'][0]['Images'];
			$galleryImages = array();
			if ($imagesUrl) {
				foreach ($imagesUrl as $imgUrl) {
					if ($imgUrl['IsDefault'] != 1) {
						$gal_attach_id = $this->insertImageWp($imgUrl['Url']);
						$galleryImages[] = $gal_attach_id;
					}
				}
			}
			if ($galleryImages) {
				update_post_meta($productId, '_product_image_gallery',  implode(',', $galleryImages));
			}
		} else {
			echo "Image not available or imported";
		}
		/*  */

		exit;
		exit("works");
	}

	/**
	 * Add Image to WP Media Library
	 * @param  string $url Image URL
	 * @return int|bool Attachment ID or false
	 */
	function insertImageWp($image_url)
	{

		$upload_dir = wp_upload_dir();

		$image_data = file_get_contents($image_url);

		$filename = basename($image_url);

		if (wp_mkdir_p($upload_dir['path'])) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($filename, null);

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attach_id = wp_insert_attachment($attachment, $file);
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata($attach_id, $file);
		wp_update_attachment_metadata($attach_id, $attach_data);
		return $attach_id;
	}

	function getapiproduct(){
		$skuCode = 'BKN61P16-C';
		// $skuCode = (isset($_REQUEST['sku']) && !empty($_REQUEST['sku'])) ? $_REQUEST['sku'] : "";
		$request = 'ProductCode='.$skuCode;
		$product_data = $this->getProducts($skuCode,$request);
		echo "<pre>";
		print_r($product_data);
		echo "</pre>"; 
	}
}
