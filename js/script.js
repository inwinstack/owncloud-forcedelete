/**
 * ownCloud - forcedelete
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author CurlyYang <eric.y@inwinstack.com>
 * @copyright CurlyYang 2016
 */

if(!OCA.ForceDelete) {
    /**
     * Namespace for the forcedelete app
     * @namespace OCA.ForceDelete
     */

    OCA.ForceDelete = {};
}


OCA.ForceDelete  = {
    /**
     * @var string appName used for translation file
     * as transifex uses the github project name, use this instead of the appName
     */

    appName: 'forcedelete',
    
    ajaxBind: function(type, url) {
        ajaxSuccess.bind(type+':'+url, function(event) {
            var arr = [];
            while((trs = FileList._nextPage(true)) != false) {
                arr = arr.concat(trs);
            }

            $('.filename .fileactions .action-menu').click(function() {
                var $tr = $(this).closest('tr');
                var attribute = FileList.getModelForFile(String($tr.data('file'))).attributes;

                if('mountType' in attribute) {
                    $tr.find('.filename .fileActionsMenu ul .action-force').closest('li').remove();

                }

            });

            $.each(arr, function(index, value) {
                value.find('.action-menu').click(function() {
                    var $tr = $(this).closest('tr');
                    var attribute = FileList.getModelForFile(String($tr.data('file'))).attributes;

                    if('mountType' in attribute) {
                        $tr.find('.filename .fileActionsMenu ul .action-force').closest('li').remove();

                    }
    
                });
            });
        });
    },

    registerFileAction: function () {
        var img = OC.imagePath('core', 'actions/close');
        OCA.Files.fileActions.register(
            'all',
            t(this.appName, 'Force Delete'),
            OC.PERMISSION_DELETE,
            OC.imagePath('core', 'actions/close'),
            function(file) {
                var path = FileList.getCurrentDirectory();
                OCA.ForceDelete.delete(path, FileList.getModelForFile(file).attributes);
            }

        );

        //append button to Actions
        var el = $('#app-content-files #headerName .selectedActions');
        $('<a class="forcedelete" id="forcedelete" href="#"><img class="svg" src="'+img+'" alt="'+t(this.appName, 'Force Delete')+'">'+t(this.appName, "Force Delete")+'</a>').appendTo(el);
        el.find('.forcedelete').click(this.selectFiles);



    },

    selectFiles: function(event) {

        var files = FileList.getSelectedFiles();
        var path = FileList.getCurrentDirectory();
        
        OCA.ForceDelete.delete(path, files);
            
    },

    initialize: function() {
        
        this.registerFileAction();
    },

    delete: function(path, files) {

        if($.isArray(files)) {
            var selectedFiles = [];
            
            for(var i=0; i< files.length;i++) {

                if('mountType' in FileList.getModelForFile(files[i].name).attributes) {
                    OC.Notification.showTemporary(t(OCA.ForceDelete.appName,files[i].name + 'is not a local file'));
                    continue;
                }

                selectedFiles.push({'path': path+files[i].name, 'isdir': files[i].type == 'dir' ? true : false});
            }

            files = selectedFiles;

        } else {
            
            files = {'path': path+files.name, 'isdir': files.type == 'dir' ? true : false};
        
        }

        $.ajax({
            method: 'POST',
            url: OC.generateUrl('/apps/forcedelete/deleteFile'),
            data: {
                files: files
            }

        }).done(function(data) {
            console.dir(data);
            //data.status && FileList.remove(file);  
        
        });
        
    },
}



$(document).ready(function () {
    if(!OCA.Files) {
        return;
    }

    if(/(public)\.php/i.exec(window.location.href) != null) {
        return;
    }
    OCA.ForceDelete.initialize();
    
    OCA.ForceDelete.ajaxBind('GET', '/apps/files/ajax/list');
    OCA.ForceDelete.ajaxBind('POST', '/apps/files/ajax/newfile');
    OCA.ForceDelete.ajaxBind('POST', '/apps/files/ajax/newfolder');
    OCA.ForceDelete.ajaxBind('POST', '/apps/files/ajax/upload');

});

