define(['jquery','amplitude','domReady!'],function($){      
   "use strict";
   return function (config) {
	   $(document).ready(function(){
	   		jPlayerPlaylist.add(config.playlist);
	   });
   }
});