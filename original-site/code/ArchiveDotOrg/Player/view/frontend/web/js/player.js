 define(['jquery','amplitude','domReady!'],function($){
   "use strict";
   return function (config) {


$(document).ready(function(){
console.dir(config.playlist);
	new jPlayerPlaylist({
		jPlayer: "#jquery_jplayer_1",
		cssSelectorAncestor: "#jp_container_1"
	}, config.playlist, {		
		supplied: "mp3",
		wmode: "window",
		useStateClassSkin: true,
		autoBlur: false,
		smoothPlayBar: true,
		keyEnabled: true
	});
});

   }
});