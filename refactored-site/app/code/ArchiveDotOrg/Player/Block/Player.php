<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ArchiveDotOrg\Player\Block;



class Player extends \Magento\Checkout\Block\Cart {
    
	
	
	public function getPlaylist(){
		$_playlist = '';
		foreach($this->getItems() as $_item) {
		    $_playlist = $_playlist.'{';
		    $_playlist = $_playlist.'"title":"'.$_item->getProduct()->getData('title').'",';
		    $_playlist = $_playlist.'"mp3":"https://'.$_item->getProduct()->getData('song_url').'",';
			$_playlist = $_playlist.'"artist":"'.substr_replace($_item->getProduct()->getData('archive_collection'),"",-5).'.mp3"';
		    $_playlist = $_playlist.'}';
			$_playlist = $_playlist.',';
		}
		$_playlist = substr_replace($_playlist ,"",-1);
		return $_playlist;
	}
	
	
}