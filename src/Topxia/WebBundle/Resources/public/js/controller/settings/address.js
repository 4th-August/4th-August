define(function(require, exports, module) {
	var Notify = require('common/bootstrap-notify');

	exports.run = function() {

		require('./../course-manage/header').run();

		// var uploader = new WebUploader({
		// 	element: '#upload-picture-btn'
		// });
        //
		// uploader.on('uploadSuccess', function(file, response ) {
		// 	var url = $("#upload-picture-btn").data("gotoUrl");
		// 	Notify.success('上传成功！', 1);
		// 	document.location.href = url;
		// });

        var a = $(".defau-mod");
        a.mouseover(function() {
            $(this).removeClass("def-hide")
        });
        a.mouseleave(function() {
            $(this).addClass("def-hide");
            $(this).find(".del-verify").removeAttr("style");
            $(this).find(".del-btn").removeAttr("style")
        });

        $('.def-addr-sure').find('.way').hover(
        	function() {
            $( this ).find("p").show();
             },
			function() {
            $( this ).find("p").hide();
        });

        $('.delivery-btn').click(function() {
            window.location.href= $( this ).data("url");
		});

        $(".remove-way").on('click', function(){
            if (!confirm('确认要删除吗？')) return false;
            var $btn = $(this);
            $.post($btn.data('url'), function(){
                $btn.parent().parent().parent().parent().remove();
                Notify.success('删除成功！');
            }).error(function(){
                Notify.danger('删除失败！');
            });
        });




        // $(".delivery-btn").live("click", function () {
        //     var b = $(this).find("p").attr("data-id");
        //     var a = $(this).find("p").attr("preferFlag");
        //     var c = $(this).parents(".defau-mod");
        //     probeAuthStatus(function () {
        //         $.mDialog({
        //             css: {
        //                 width: "567"
        //             },
        //             http: function (d, e) {
        //                 $.ajax({
        //                     url: "addressInput.do",
        //                     data: {
        //                         addrId: b,
        //                         preferFlag: a,
        //                         addrInputType: "resetDeliveryAddr"
        //                     },
        //                     cache: "false",
        //                     dataType: "json",
        //                     async: false,
        //                     success: function (g) {
        //                         d.find(".content").html(g.htmlDom);
        //                         loadModifyData(c);
        //                         $("#addr-name").val(c.find(".name").text());
        //                         $("#addr-detailAddress").val(c.find(".addr-detailAddress").text());
        //                         $("#addr-tel").val(c.find(".iphone").text());
        //                         $("#hidden-tel").val(c.find(".iphone").text());
        //                         var f = c.find(".addr-phone").text();
        //                         if (f != null && f != "") {
        //                             $("#addr-phone").val(f);
        //                             $("#hidden-teleNum").val(f);
        //                             $("#addr-phone").css("color", "#333")
        //                         }
        //                         $("#addr-name,#addr-detailAddress,#addr-tel").css("color", "#333");
        //                         $(".sure").attr("data-id", b)
        //                     }
        //                 })
        //             },
        //             title: "修改收货地址",
        //             overlayClick: true,
        //             fadeIn: 300,
        //             fadeOut: 500
        //         })
        //     }, function () {
        //         var d = "address.do";
        //         window.location.href = d
        //     })
        // })

	};
});