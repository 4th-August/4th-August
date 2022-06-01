define(function(require, exports, module) {

    var Store = require('store');
    var Class = require('class');
    var Messenger = require('./messenger');
    var swfobject = require('swfobject');

    exports.run = function() {

        var videoHtml = $('#player');
        var url = videoHtml.data('url');
        var watermark = videoHtml.data('watermark');

        videoHtml.html('<video id="lesson-player" style="width: 100%;height: 100%;" class="video-js vjs-default-skin"></video>');

        var PlayerFactory = require('./player-factory');
        var playerFactory = new PlayerFactory();
        var player = playerFactory.create(
            "balloon-cloud-video-player",
            {
                element: '#lesson-player',
                url: url,
                watermark: watermark
            }
        );

        var messenger = new Messenger({
            name: 'parent',
            project: 'PlayerProject',
            type: 'child'
        });
    
        player.on("ready", function(){
            var time = DurationStorage.get(userId, fileId);
            player.play();
        });

        player.on("firstplay", function(){
 
        });
        
        player.on("ready", function(){
            messenger.sendToParent("ready", {pause: true});
        });

        player.on("timechange", function(){
            messenger.sendToParent("timechange", {pause: true});
        });

        player.on("paused", function(){
            messenger.sendToParent("paused", {pause: true});
        });

        player.on("playing", function(){
            messenger.sendToParent("playing", {pause: false});
        });

        player.on("ended", function(){
            messenger.sendToParent("ended", {stop: true});
        });

    };

});