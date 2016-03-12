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

class Pico_Tags extends AbstractPicoPlugin {
  protected $enabled = false;

	private $base_url;
	private $current_url;
	private $current_tag;
	private $is_tag;
  private $content_dir;
  private $sitetags;

  public function __construct(Pico $pico)
  {
    parent::__construct($pico);
    $this->sitetags = array();  
  }
  
	public function onRequestUrl(&$url)
  {
		$this->current_url = $url;

		// substr first four letters, becouse "tag/" is four letters long
		$this->is_tag = (substr($this->current_url, 0, 4) == "tag/");
		if ($this->is_tag) $this->current_tag = substr($this->current_url, 4);
	}

  public function onMetaHeaders(array &$headers)
  {
  	$headers['tags'] = 'Tags';
  }

  public function onMetaParsed(array &$meta)
  {
    // tagsメタデータを見て変換処理を行う
    // ・,が入っていれば配列にする（ただしすでに配列であった場合、何もしない（旧記法対応））
    // ・要素が一つしか無かった場合も配列にする
    if(!is_array($meta["tags"])){
      $meta["tags"] = explode(",", $meta["tags"]);
    }
    // sitetags配列に格納
    $this->sitetags += $meta["tags"];
  }

	public function onConfigLoaded(array &$config) 
  {
		$this->base_url = $config['base_url'];
    $this->content_dir = $config["content_dir"];
	}

  public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
  {
		if ($this->is_tag) {
			// override 404 header
			header($_SERVER['SERVER_PROTOCOL'].' 200 OK');
			
			$templateName = "tags";
			// set as front page, allows using the same navigation for index and tag pages
			$twigVariables["is_front_page"] = true;
			// sets page title to #TAG
			$twigVariables["meta"]["title"] = "#" . $this->current_tag;
			$pages = $twigVariables["pages"];
			$tagpages = array();
  		foreach ($pages as $page) {
        if(isset($page["tags"]) && in_array($this->current_tag, $page["tags"])) {
          array_push($tagpages, $page);
        }
			}
      var_dump($tagpages);
//			$twigVariables["showpages"] = $tagpages;
		}
    $twigVariables["sitetags"] = array_unique($this->sitetags);
	}
}

?>
