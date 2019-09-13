import '../sass/cms_front.scss'
import $ from "jquery"
global.$ = global.jQuery = $


$(document).ready(function() {
    function lauchModalEditContent(id) {
        // Get the modal
        var modal = document.getElementById("modalEditContent");

        if (id){
            $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit', function( response ) {
                var modal = $("#modalEditContentBody");
                modal.html(response)

                var modalForm = modal.find('form')[0];

                var uniqid = modalForm[0].name.substring(0, modalForm[0].name.indexOf('['));


                modalForm.onsubmit=function(e) {
                    e.preventDefault();

                    if (Object.entries(CKEDITOR.instances).length){
                        for(var instanceName in CKEDITOR.instances)
                            CKEDITOR.instances[instanceName].updateElement();
                    }

                    let form = $(e.target).serialize();
                    var form_value = $(e.target).serializeArray();
                    console.log(e.target);

                    $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit?uniqid=' + uniqid, form).done(function() {
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

                                modal.prepend('<div class="alert alert-success" role="alert">Modification effectuée</div>')
                            }
                        })
                    })

                }

            });
            modal.style.display = "block";
        }
    }

    function printLoader(){
        $("#modalEditContentBody").html('<div style="text-align: center; width: 100%">\n' +
            '    <i class="fa fa-spinner fa-4x fa-spin" aria-hidden="true"></i>\n' +
            '</div>'
        )
    }

    var classname = document.getElementsByClassName("open-modal-edit-content");

    for (var i = 0; i < classname.length; i++) {
        classname[i].addEventListener('click', function(e) {
            lauchModalEditContent(e.target.dataset["id"])
        }, false);
    }

    var modal = $("#modalEditContent")[0];

// Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close-edit")[0];

// When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
        $("button[id^='btn-edit-content']").hide();
        printLoader()
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            $("button[id^='btn-edit-content']").hide();
            printLoader()
        }
    }

    $(".text-edit-content").hover( function(e) {
        if (!e.target.dataset.btn){
            var btn = e.target.parentNode.dataset.btn;
        }
        var btn = e.target.dataset.btn;

        $("#btn-edit-content-" + btn).show().delay(2000).fadeOut();
    })

})
