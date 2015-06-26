<?php

/**
 * Tags plugin
 * Adds ability to use "Tags:" field in post meta,
 * tags should be comma separeted without spaces
 * posts with given tag can be displayed when visiting
 * /tag/TAG URL
 *
 * @author Szymon Kaliski
 * @link http://treesmovethemost.com
 * @license http://opensource.org/licenses/MIT
 */

class Pico_Tags {
	private $base_url;
	private $current_url;
	private $current_tag;
	private $is_tag;
	private $current_meta;

	// copied from pico source, $headers as array gives ability to add additional metadata, e.g. header image
	private function read_file_meta($content) {
		$headers = array('tags' => 'Tags');

	 	foreach ($headers as $field => $regex) {
			if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $content, $match) && $match[1]){
				$headers[ $field ] = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
			} else {
				$headers[ $field ] = '';
			}
		}

		// only set $headers['tags'] if there are any
		if (strlen($headers['tags']) > 1) $headers['tags'] = explode(',', $headers['tags']);
		else $headers['tags'] = NULL;

		return $headers;
	}

	public function plugins_loaded() {

	}

	public function request_url(&$url) {
		$this->current_url = $url;

		// substr first four letters, becouse "tag/" is four letters long
		$this->is_tag = (substr($this->current_url, 0, 4) == "tag/");
		if ($this->is_tag) $this->current_tag = substr($this->current_url, 4);
	}

	public function before_load_content(&$file) {

	}

	public function after_load_content(&$file, &$content) {
		$this->current_meta = $this->read_file_meta($content);
	}

	public function before_404_load_content(&$file) {

	}

	public function after_404_load_content(&$file, &$content) {

	}

	public function config_loaded(&$settings) {
		$this->base_url = $settings['base_url'];
	}

	public function file_meta(&$meta) {

	}

	public function content_parsed(&$content) {

	}

	public function get_pages(&$pages, &$current_page, &$prev_page, &$next_page) {
		// display pages with current tag if visiting tag/ url
		// display only pages with tags when visiting index page
		// this adds possiblity to distinct tagged pages (e.g. blog posts),
		// and untagged (e.g. static pages like "about")

		$is_index = ($this->base_url == $current_page["url"]);

		$new_pages = array();

		foreach ($pages as $page) {
			$file_url = substr($page["url"], strlen($this->base_url));
			if($file_url[strlen($file_url) - 1] == "/") $file_url .= 'index';
			$file_name = CONTENT_DIR . $file_url . ".md";
			// get metadata from page
			if (file_exists($file_name)) {
				$file_content = file_get_contents($file_name);
				$file_meta = $this->read_file_meta($file_content);
				$page = array_merge($page, $file_meta);
				array_push($new_pages, $page);
			}
		}

		$pages = $new_pages;
	}

	public function before_twig_register() {

	}

	public function before_render(&$twig_vars, &$twig, &$template) {
		if ($this->is_tag) {
			// override 404 header
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
			
			$template = "tags";
			// set as front page, allows using the same navigation for index and tag pages
			$twig_vars["is_front_page"] = true;
			// sets page title to #TAG
			$twig_vars["meta"]["title"] = "#" . $this->current_tag;
			$pages = $twig_vars["pages"];
			$tagpages = array();
  		foreach ($pages as $page) {
			  if(isset($page["tags"]) && in_array($this->current_tag, $page["tags"])) {
			    array_push($tagpages, $page);
			  }
			}
			$twig_vars["showpages"] = $tagpages;
		}
		else {
			// add tags to post meta
			$twig_vars["meta"] = array_merge($twig_vars["meta"], $this->current_meta);
		}
	}

	public function after_render(&$output) {

	}
}

?>
