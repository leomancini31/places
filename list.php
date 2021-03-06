<?php
	// error_reporting(E_ALL);
	// ini_set('display_errors', 1);
	// ini_set('display_startup_errors', 1);

	require("base.php");
	require("render-list.php");
	include("../styles/map-style.php");
	header('Content-Type: text/html; charset=utf-8');

	$list = $_GET['list'];
	$list = explode("list-id:", $list);
	
	if($list[0] != "") {
		if(preg_match("/:/", $list[0])) {
			$list_name_and_neighborhood = explode(":", $list[0]);
			$list_name_display = $list_name_and_neighborhood[0];
		
			$url_neighborhood["raw"] = str_replace($list_name_display.":", "", $list[0]);
			$url_neighborhood["url"] = convert("search-query", "url", $url_neighborhood["raw"]);
				
			$url_neighborhood_terms = explode(":", $url_neighborhood["raw"]);
			$url_neighborhood_term_count = 0;
			
			foreach($url_neighborhood_terms as $url_neighborhood_term) {
				$url_neighborhood_term_count++;
				if($url_neighborhood_term_count > 1) { $separator = " / "; }
				$url_neighborhood["display"] .= $separator.convert("search-query", "display", $url_neighborhood_term);
			}
		} else {
			$list_name_display = $list[0];
		}
	
		// URL is a list name
		$list_name_display = convert("list", "display", $list_name_display);
		$list_name_display = mysqli_real_escape_string($db, $list_name_display);

		$lists_query = mysqli_query($db, "SELECT * FROM lists WHERE name = '".$list_name_display."'");
		$count = mysqli_num_rows($lists_query);

		$list_counter = 0;

		if($count > 1) {
			// Multiple lists match URL request name
			while($list = mysqli_fetch_array($lists_query)) {
				$list_counter++;
				echo "<a href='./id:".$list['foursquare_id']."'>".$list['name']."-".$list_counter."</a>";
				echo "<br>";
			}
		} else {
			// One list matches URL request name
			$list = mysqli_fetch_array($lists_query);
		}
	} else {
		// URL is a list id
		$list_id = $list[1];
		$list_id = mysqli_real_escape_string($db, $list_id);

		$list_query = mysqli_query($db, "SELECT * FROM lists WHERE foursquare_id = '".$list_id."'");
		$list = mysqli_fetch_array($list_query);
	}
	
	if($list) {
		// List exists
		$list_name_url = convert("list", "url", $list["name"]);
		$list_name_url_without_neighborhood = $list_name_url;
		if(isset($url_neighborhood)) { $list_name_url .= ":".$url_neighborhood["url"]; }

		$header_image_directory = "../../resources/images/list-headers/".$list_name_url_without_neighborhood."/";
		$header_image_directory_files = scandir($header_image_directory);
		foreach($header_image_directory_files as $header_image_directory_file) {
			if($header_image_directory_file != "." && $header_image_directory_file != "..") {
				$header_image_path = $header_image_directory_file;
			}
		}
	
		// Generate title including list name, neighborhood, categories, 
		$title = $list["name"];
		if(isset($url_neighborhood["url"])) { $title .= " / ".convert("neighborhood", "display", $url_neighborhood["url"]); }
		if(isset($_GET["category1"])) { $title .= " / ".convert("category", "display", $_GET["category1"]); }
		if(isset($_GET["category2"])) { $title .= " / ".convert("category", "display", $_GET["category2"]); }
		if(isset($_GET["category3"])) { $title .= " / ".convert("category", "display", $_GET["category3"]); }
		if(isset($_GET["category4"])) { $title .= " / ".convert("category", "display", $_GET["category4"]); }
		if(isset($_GET["category5"])) { $title .= " / ".convert("category", "display", $_GET["category5"]); }
		
		if($url_neighborhood["url"]) {
			foreach($url_neighborhood_terms as $url_neighborhood_term) {
				$check_neighborhood_is_real_query = mysqli_query($db, "SELECT neighborhood_long_name FROM neighborhoods WHERE neighborhood_long_name = '".convert("search-query", "display", $url_neighborhood_term)."' LIMIT 1");
				$is_real_neighborhood = mysqli_num_rows($check_neighborhood_is_real_query);
				if($is_real_neighborhood == 1) {
					$neighborhood = mysqli_fetch_array($check_neighborhood_is_real_query);
				}
				$map_neighborhood = $neighborhood["neighborhood_long_name"];
			}
		}
		
		if($url_neighborhood["url"] && $map_neighborhood) {
			$map["center"] = $list["name"]." ".$map_neighborhood;
			$map["zoom"] = 15;
		} else {
			$map["center"] = $list["name"];
			$map["zoom"] = 11;
		}
?>
	<!DOCTYPE HTML>
	<html>
		<head>
			<title><?php echo $title; ?></title>
			<meta property="og:title" content="<?php echo $title; ?>">
			<meta property="og:description" content="by Leo Mancini">
			<meta property="og:type" content="website">
			<meta property="og:image:width" content="600">
			<meta property="og:image:height" content="315">
			<meta property="og:image" content="https://places.leo.gd/google_static_map/<?php echo $list_name_url; ?>/<?php echo $map["zoom"]; ?>">
			<link rel="stylesheet" href="<?php echo $root; ?>/resources/styles/common.css">
			<link rel="stylesheet" href="<?php echo $root; ?>/resources/styles/list-<?php echo (is_mobile() ? "mobile" : "desktop"); ?>.css<?php echo "?".rand(0, 9999999); ?>">
			<meta name="viewport" content="width = device-width, initial-scale = 1, user-scalable = no" />
			<script src="<?php echo $root; ?>resources/js/lib/jquery.js"></script>
			<script src="<?php echo $root; ?>resources/js/lib/stretchy.js" data-filter=".stretchy"></script>
			<?php if(is_mobile()) { ?><script>window.mobile = 1;</script><?php } ?>
			<script src="<?php echo $root; ?>resources/js/list.js"></script>
			<!-- Global site tag (gtag.js) - Google Analytics -->
			<script async src="https://www.googletagmanager.com/gtag/js?id=UA-112757234-2"></script>
			<script>
			  window.dataLayer = window.dataLayer || [];
			  function gtag(){dataLayer.push(arguments);}
			  gtag('js', new Date());

			  gtag('config', 'UA-112757234-2');
			</script>
		</head>
		<body ontouchstart="">
		<?php
			if(($_GET['category1'] == "" &&
				$_GET['category2'] == "" &&
				$_GET['category3'] == "" &&
				$_GET['category4'] == "" &&
				$_GET['category5'] == "" &&
				$url_neighborhood["url"] == "")
			|| is_mobile() == false) {			
		?>
			<div id="header-image">
				<?php if($header_image_path) { ?>
					<img class="photo" src="<?php echo $root; ?>/resources/images/list-headers/<?php echo $list_name_url_without_neighborhood; ?>/<?php echo $header_image_path; ?>">	
				<?php
					} else {
				?>
					<div id="map"></div>
						<script>
							function initMap() {
								var map = new google.maps.Map(document.getElementById('map'), {
									zoom: <?php echo $map["zoom"]; ?>,
									disableDefaultUI: true,
									gestureHandling: 'none',
									scrollwheel: false,
									disableDoubleClickZoom: true,
									panControl: false,
									streetViewControl: false,
									draggableCursor: 'default',
									styles: <?php echo $map["style"]; ?>,

									hiding: [
										{
											featureType: 'poi.business', stylers: [{visibility: 'off'}]}, { featureType: 'transit', elementType: 'labels.icon', stylers: [{visibility: 'off'}]
										}
									]
								});
								var geocoder = new google.maps.Geocoder();
								var address = "<?php echo $map["center"]; ?>";

								geocoder.geocode({'address': address}, function(results, status) {
									if (status === 'OK') {
										map.setCenter(results[0].geometry.location);
									}
								});
							}
							</script>
							<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api_key; ?>&callback=initMap"></script>
					<?php } ?>
				</div>
		<?php } ?>		
			<div id="master">
				<div id="container">
					<div id="list" data-list-url="<?php echo $list_name_url; ?>">
						<?php
							$list_query = "WHERE foursquare_list_id = '".$list['foursquare_id']."'";
							
							foreach($split_list_combos as $split_list_combo) {
								if($list['foursquare_id'] === $split_list_combo["parent"]) {
									foreach($split_list_combo["children"] as $split_list_child_id) {
										$list_query .= " OR foursquare_list_id = '".$split_list_child_id."'";
									}
								}
							}
							
							render_list($list_query);
						?>
					</div>
				</div>
			</div>
		</body>
	</html>
<?php
	} else {
		// List doesn't exist
		header('Location: ./');
	}

	include("end.php");
?>