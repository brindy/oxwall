<?php
/**
 * Transio.org - Transio Framework (tm) for PHP 5 and MySQL 5
 *
 * RSS Library for reading and creating RSS Feeds
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2008, Transio.org
 * @link			http://www.transio.org/framework/data/rss/RSSIterator.php
 * @package			org.transio.framework
 * @subpackage		org.transio.framework.data.rss
 * @since			Transio Framework (tm) Media Library v 0.0.1
 * @version			0.0.1
 * @modifiedby		Steven Moseley
 * @lastmodified		2009/02/23
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

require_once("RSSItem.php");

/**
  * RSSIterator
  * This class is used to load and iterate an RSS Feed
  *
  */

class RSSIterator implements Iterator
{
	private $items;
	private $currentItem = 0;
	private $limit;

	public function __construct($path, $limit=null) {
		$dom = new DOMDocument();

		if (@$dom->load($path))
		{
		    $this->items = $dom->getElementsByTagName("item");
		}
		else
		{
			throw new Exception("Unable to read RSS file.");
		}

		$this->limit = $limit;
	}

	public function rewind() {
		$this->currentItem = 0;
	}

	public function valid() {
		return $this->currentItem < $this->items->length &&
			(is_null($this->limit) || $this->limit == 0 || $this->currentItem < $this->limit);
	}

	public function current() {
		$itemNode = $this->items->item($this->currentItem);

		$titleNodes = $itemNode->getElementsByTagName("title");
		$title = $titleNodes->item(0)->nodeValue;

		$dateNodes = $itemNode->getElementsByTagName("pubDate");
        $date = $dateNodes->item(0)->nodeValue;

        $linkNodes = $itemNode->getElementsByTagName("link");
        $link = $linkNodes->item(0)->nodeValue;

		$descriptionNodes = $itemNode->getElementsByTagName("description");
		$description = $descriptionNodes->item(0)->nodeValue;

		return new RSSItem($title, $description, $link, $date);
	}

	public function key() {
		return $this->currentItem;
	}

	public function next() {
		$this->currentItem++;
	}

	public function seek($itemNumber) {
		$this->currentItem = $itemNumber;
	}

}

?>