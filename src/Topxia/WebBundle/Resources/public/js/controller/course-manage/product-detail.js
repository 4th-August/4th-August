define(function(require, exports, module) {

    var DynamicCollection = require('../widget/dynamic-collection4');
    require('jquery.sortable');
    require('es-ckeditor');
    require('webuploader');
    var Notify = require('common/bootstrap-notify');

    exports.run = function() {
        require('./header').run();

        // group:'course'
        CKEDITOR.replace('course-about-field', {
            toolbar: 'Detail',
            filebrowserImageUploadUrl: $('#course-about-field').data('imageUploadUrl')
        });


        var goalDynamicCollection = new DynamicCollection({
            element: '#course-goals-form-group'
        });

        var audiencesDynamicCollection = new DynamicCollection({
            element: '#course-audiences-form-group'
        });

        $(".sortable-list").sortable({
            'distance':20
        });

        $("#course-base-form").on('submit', function() {
            goalDynamicCollection.addItem();
            audiencesDynamicCollection.addItem();

        });

        $("#block-edit-form").on('click', '.js-remove-btn', function(){
            var $tr = $(this).parent().parent().parent();
            if (!confirm('你确定要删除吗?')) {
                return ;
            }
            $tr.remove();
        });

        bindCollapseEvent($("#block-edit-form"));
        bindUploader();
        $('.js-add-btn').click(function() {

            var $panelGroup = $(this).prev('.panel-group');
            var $panels = $panelGroup.children('.panel.panel-default');
            $model = $($panels[0]).clone();
            $model.find('input').attr('value', '').val('');
            $model.find('textarea').attr('html', '');
            $model.find('.title-label').html('');
            $model.find('.js-img-preview').attr('href', '');
            var headingId = new Date().getTime() + '-heading';
            $model.find('.panel-heading').attr('id', headingId);
            var collapseId = new Date().getTime() + '-collapse';
            $model.find('.panel-collapse').attr('aria-labelledby', headingId).attr('id', collapseId);
            $model.find('[data-toggle=collapse]').attr('aria-expanded', false).attr('href', "#"+collapseId).attr('aria-controls', collapseId);
            $model.find('input[data-role=radio-yes]').attr('checked', false);
            $model.find('input[data-role=radio-no]').attr('checked', true);
            var code = $panelGroup.data('code');
            var uploadId = new Date().getTime();
            $model.find('.webuploader-container').attr('id',  'item-' + code + 'uploadId-' + (uploadId));
            $panelGroup.append($model);
            refreshIndex($panelGroup);
        });

        // var uploaders=[];

        function bindUploader() {
            $("#block-edit-form").find('.img-upload').each(function(){
                var self = $(this);
                var uploader = WebUploader.create({
                    swf: require.resolve("webuploader").match(/[^?#]*\//)[0] + "Uploader.swf",
                    server: $(this).data('url'),
                    pick: '#'+$(this).attr('id'),
                    formData: {'_csrf_token': $('meta[name=csrf-token]').attr('content') },
                    accept: {
                        title: 'Images',
                        extensions: 'gif,jpg,jpeg,png',
                        mimeTypes: 'image/*'
                    }
                });

                uploader.on( 'fileQueued', function( file ) {
                    Notify.info('正在上传，请稍等！', 0);
                    uploader.upload();
                });

                uploader.on( 'uploadSuccess', function( file, response ) {
                    self.closest('.form-group').find('input[data-role=img-url]').val(response.url);
                    Notify.success('上传成功！', 1);
                });

                uploader.on( 'uploadError', function( file, response ) {
                    Notify.danger('上传失败，请重试！');
                });

                // var id =$(this).attr('id');
                // uploaders[id] = uploader;
            });
        }

        function refreshIndex($panelGroup) {
            $prefixCode = $panelGroup.data('prefix');
            $panels = $panelGroup.children('.panel.panel-default');
            $panels.each(function(index, object){
                $(this).find('input[type=text]').each(function(element){
                    $(this).attr('value', $(this).val());
                });
                $(this).find('input[type=radio]').each(function(element){
                    if ($(this).prop('checked')) {
                        $(this).attr('checked', 'checked');
                    }
                });

                $(this).find('.webuploader-container').html('上传');
                var replace = $(this)[0].outerHTML.replace(/\bdata\[.*?\]\[.*?\]/g, $prefixCode + "[" + index + "]");
                $(this).replaceWith(replace);
            });

            bindUploader();
            bindCollapseEvent($panelGroup);
        }

        function bindCollapseEvent($element) {
            $element.find('[data-role=collapse]').each(function(){
                $(this).on('shown.bs.collapse', function(e){
                    $(e.target).siblings('.panel-heading').find('.js-expand-icon').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
                    $(e.target).find('.webuploader-container div:eq(1)').css({width:46, height:30});
                });
                $(this).on('hidden.bs.collapse', function(e){
                    $(e.target).siblings('.panel-heading').find('.js-expand-icon').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
                    $(e.target).find('.webuploader-container div:eq(1)').css({width:1, height:1});
                });
            });

        }
    };

});