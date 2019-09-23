import '../sass/cms_front.scss'
import $ from "jquery"
global.$ = global.jQuery = $


$(document).ready(function() {
    function launchModalEditContent(id) {
        removeAlert()
        if (id){
            $("#modalEditContent").show()
            $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit', function( response ) {

                var modal = $("#modalEditContentBody");
                modal.html(response)

                var modalForm = modal.find('form')[0];

                var uniqid = modalForm[0].name.substring(0, modalForm[0].name.indexOf('['));

                var send = $(modal).find('.btn-success')[0];
                send.parentNode.replaceWith(send)


                modalForm.onsubmit=function(e) {
                    e.preventDefault();

                    if (Object.entries(CKEDITOR.instances).length){
                        for(var instanceName in CKEDITOR.instances)
                            CKEDITOR.instances[instanceName].updateElement();
                    }

                    let form = $(e.target).serialize();
                    var form_value = $(e.target).serializeArray();

                    $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit?uniqid=' + uniqid, form).done(function() {
                        removeAlert()
                        $(form_value).each(function(index, element ) {
                            if (element.name === uniqid + '[value]'){
                                var chev = (element.value).indexOf(">");

                                if (!chev || element.value[0] !== '<'){
                                    var value = "<span class='text-edit-content' data-btn='" + id + "'>" + element.value + "</span>";
                                }else{
                                    var value = (element.value).substring(0, chev ) + " class='text-edit-content' data-btn='" + id + "'>" + (element.value).substring(chev + 1 );
                                }

                                var divContent = $("#div-content-" + id);
                                $(divContent).html(value)

                                $(divContent[0].firstChild).hover( function(e) {
                                    var btn = $("#div-content-" + id)[0].firstChild.dataset.btn;

                                    $("#btn-edit-content-" + btn).show().delay(2000).fadeOut();
                                })
                                showSuccess("modalEditContentBody")
                            }
                        })
                    })

                }

            });
        }
    }

    window.launchModalEditMedia = function(id, format, idImg, idMedia) {
        removeAlert()
        if (id){
            $("#modalEditContent").show()
            $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit', function( response ) {
                var modalBody = $("#modalEditContentBody");
                modalBody.html(response);

                ($(modalBody).find('.btn-warning')[0]).remove();

                var send = ($(modalBody).find('.btn-success')[1])
                send.parentNode.replaceWith(send)

                var modalForm = modalBody.find('form')[0];
                var uniqid = modalForm[0].name.substring(0, modalForm[0].name.indexOf('['));

                ($(modalBody).find('.btn-info')).on('click', function(e) {
                    e.preventDefault();
                    toggleMediaAndContent()
                    loadMedias(uniqid, format, idImg)
                })

                $($(modalBody).find('.btn-success')[0]).on('click', function(e) {
                    e.preventDefault();
                    loadAdd(uniqid, format, idImg)
                    toggleMediaAndContent(2)
                })

                var old = $(modalBody).find('.btn-danger')[0];
                var parent = old.parentNode;

                var del = document.createElement('button');
                del.setAttribute('class', 'btn btn-danger');
                del.setAttribute('id', 'delete-media');

                del.textContent = old.title;
                parent.replaceWith(del)

                $("#delete-media").on('click', function(e) {
                    e.preventDefault()

                    $.get('/admin/app/media/' + idMedia + '/delete?context=default&hide_context=0', function(response) {
                        modalBody.prepend(response)


                        var formDelete = modalBody.find('form')[1];
                        var old = $(formDelete).find('.btn-success')[0];

                        var parent = old.parentNode;

                        var edit = document.createElement('button');
                        edit.setAttribute('class', 'btn btn-info');

                        edit.textContent = "Annuler";
                        old.remove()
                        parent.append(edit)

                        $(edit).on('click', function(e) {
                            e.preventDefault()
                            e.stopPropagation()
                            $($(".sonata-ba-delete")[0]).remove()
                        })

                        formDelete.onsubmit=function(e) {
                            e.preventDefault();
                            let form = $(e.target).serialize();

                            $.post('/admin/app/media/' + idMedia + '/delete?context=default&hide_context=0', form).done(function(response) {
                                showSuccess("modalEditContentBody", "delete")
                                $($(".sonata-ba-delete")[0]).remove()
                            }).fail(
                                showError("modalEditContentBody"),
                                $($(".sonata-ba-delete")[0]).remove()
                            )
                        }

                    })

                })

                modalForm.onsubmit=function(e) {
                    e.preventDefault();

                    let form = $(e.target).serialize();

                    $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit?uniqid=' + uniqid, form).done(function(response) {
                        showSuccess("modalEditContentBody")
                    })
                }
            })
        }
    }

    function printLoader(id){
        $("#" + id).html('<div style="text-align: center; width: 100%">\n' +
            '    <i class="fa fa-spinner fa-4x fa-spin" aria-hidden="true"></i>\n' +
            '</div>'
        )
    }

    function removeAlert() {
        try {
            $("div[class^='alert']").each(function(index, element) {
                console.log(element);
                $(element).remove()
            })
        }catch (e) {
            console.log(e);
        }

    }

    function showError(id) {
        removeAlert()
        $("#" + id).prepend('<div class="alert alert-danger" role="alert">Une erreur s\'est produite. Veuillez recommencer.</div>');
    }

    function showSuccess(id, type = "modify") {
        removeAlert()
        switch (type) {
            case 'modify':
                $("#" + id).prepend('<div class="alert alert-success" role="alert">Modification effectuée</div>');
                break;
            case 'delete':
                $("#" + id).prepend('<div class="alert alert-success" role="alert">Suppression effectuée</div>');
        }
    }

    function setCatchMediaList(uniqid, format, idImg){
        var links = $("#modalListMediaBody").find('.mosaic-box');

        links.each(function(index, link) {
            $(link).on('click', function(e) {
                e.preventDefault()
                catchMediaList(link, uniqid, format, idImg)
            })
        })
    }

    function loadMedias(uniqid, format, idImg, page = 1) {
        $.get('/admin/app/media/list?context=cms_page&filter[_sort_order]=ASC&filter[_sort_by]=id&filter[_page]=' + page, function(response) {
            var modalMediaBody = $("#modalListMediaBody");

            modalMediaBody.html(response);

            setCatchMediaList(uniqid, format, idImg)
            catchPagination(uniqid, format, idImg)


            $("div[id^='filter-container']").remove()
            $(modalMediaBody).find('.navbar').remove()

        })
    }

    function loadAdd(uniqid, format, idImg) {
        var uniqidParent = uniqid;
        $.get('/admin/app/media/create?context=cms_page&hide_context=0&uniqid=' + uniqidParent, function(response) {
            var modalMediaBody = $("#modalAddMediaBody");
            modalMediaBody.html(response);
            var links = modalMediaBody.find('a');
            $(modalMediaBody.find('.box-body')[0]).addClass('row');

            links.each(function(index, link) {
                $(link).on('click', function(e) {
                    printLoader("modalAddMediaBody")
                    e.preventDefault()
                    $.get(link.href, function(response) {

                        modalMediaBody.html(response)

                        var form = modalMediaBody.find('form')[0]
                        form.onsubmit = function(e) {
                            e.preventDefault();

                            var formData = new FormData();

                            var provider = form[0]
                            var file = form[1].files[0]
                            var token = form[2]

                            var uniqid = form[0].name.substring(0, form[0].name.indexOf('['));
                            formData.set(uniqid + "[binaryContent]", file , file.name);
                            formData.set(uniqid + "[providerName]", provider.value);
                            formData.set(uniqid + "[_token]", token.value);


                            var request = new XMLHttpRequest();
                            request.open("POST", "http://foo.com/submitform.php");
                            request.send(formData);

                            $.ajax({
                                url: '/admin/app/media/create?context=cms_page&hide_context=0&uniqid=' + uniqid,
                                data: formData,
                                processData: false,
                                contentType: false,
                                type: 'POST',
                                success: function(response){
                                    if (response.result.indexOf("ok") > -1){
                                       $.get('editionFront/getMedia/'+ response.objectId + '/' + format).done(function(response) {
                                           if (response.id) {
                                               var divName = $("#field_widget_" + uniqidParent + "_media");
                                               divName.html('<a href="/admin/app/media/' + response.id +'/edit?context=cms_page&hide_context=0" target="_blank">' + response.name+ '</a>')
                                               $("#" + idImg)[0].href.baseVal = response.link;
                                               $("#" + idImg)[0].src = response.link;
                                               $("#" + uniqidParent + "_media").val(response.id)
                                               toggleMediaAndContent(2)
                                           }else{
                                               showError("modalEditContent")
                                           }
                                       }).fail(function() {
                                           showError("modalEditContent")
                                       })
                                   }else{
                                       modalMediaBody.html(response)
                                   }

                                }
                            });
                        }
                    })
                })
            })


        })
    }

    function toggleMediaAndContent(type = 1) {
        if (type === 2){
            $('#modalAddMedia').toggle()
            $('#modalEditContent').toggle()
        } else {
            $('#modalListMedia').toggle()
            $('#modalEditContent').toggle()
        }
    }

    function catchMediaList(link, uniqid, format, idImg) {
        var form = $("#modalEditContentBody").find('form')[0];
        var inputs = $(form).find('input');

        removeAlert()
        inputs.each(function(index, input) {
            if ((input.name).includes('media')){
                var id = link.attributes.objectid.value;
                $(input).val(id);

                $.get('editionFront/getMedia/'+ id + '/' + format).done(function(response) {
                    if (response.id) {
                        var divName = $("#field_widget_" + uniqid + "_media");
                        divName.html('<a href="/admin/app/media/' + response.id +'/edit?context=cms_page&hide_context=0" target="_blank">' + response.name+ '</a>')
                        $("#" + idImg)[0].href.baseVal = response.link;
                        $("#" + idImg)[0].src = response.link;
                        toggleMediaAndContent()
                    }else{
                        showError("modalListMediaBody")
                    }
                }).fail(function() {
                    showError("modalListMediaBody")
                })

            }
        })

    }

    function catchPagination(uniqid, format, idImg) {
        var modalMediaBody = $("#modalListMediaBody");
        var pagination = $(modalMediaBody).find('.pagination');
        var links = pagination.find('a');

        links.each(function(index, link) {
            if (link.title !== ""){
                link.remove()
            }
        })

        links = pagination.find('a');

        links.each(function(index, link) {
            $(link).on('click', function(e) {
                e.preventDefault()
                loadMedias(uniqid, format, idImg, index + 1)
            })
        })
    }

    var classname = document.getElementsByClassName("open-modal-edit-content");

    for (var i = 0; i < classname.length; i++) {
        classname[i].addEventListener('click', function(e) {
            launchModalEditContent(e.target.dataset["id"])
        }, false);
    }

    var modal = $("#modalEditContent")[0];
    var modalMedia = $("#modalListMedia")[0];
    var modalAddMedia = $("#modalAddMedia")[0];

    var span = document.getElementsByClassName("close-edit");

    span[0].onclick = function() {
        modal.style.display = "none";
        $("button[id^='btn-edit-content']").hide();
        $("button[id^='btn-edit-media']").hide();
        printLoader('modalEditContentBody')
    }


    span[1].onclick = function() {
        toggleMediaAndContent()
    }

    span[2].onclick = function() {
        toggleMediaAndContent(2)
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            $("button[id^='btn-edit-content']").hide();
            printLoader('modalEditContentBody')
        }

        if (event.target == modalMedia) {
            modalMedia.style.display = "none";
            $("button[id^='btn-edit-media']").hide();
            printLoader('modalListMediaBody')
        }

        if (event.target == modalAddMedia) {
            modalAddMedia.style.display = "none";
            printLoader('modalAddMediaBody')
        }
    }

    $(".text-edit-content").hover( function(e) {
        if (!e.target.dataset.btn){
            var btn = e.target.parentNode.dataset.btn;
        }else {
            var btn = e.target.dataset.btn;
        }

        $("#btn-edit-content-" + btn).show().delay(5000).fadeOut();
    })

})
