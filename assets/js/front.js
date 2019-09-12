import '../sass/cms_front.scss'
import $ from "jquery"

$(document).ready(function() {
    function lauchModalEditContent(id) {
        // Get the modal
        var modal = document.getElementById("modalEditContent");

        if (id){
            $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit', function( response ) {
                $("#modalEditContentBody").html(response)

                $("#modalEditContentBody").find('form')[0].onsubmit=function(e) {
                    e.preventDefault();

                    let form = $(e.target).serialize()

                    var uniqid = e.target[0].name.substring(0, e.target[0].name.indexOf('['))


                    $.post('admin/webetdesign/cms/cmscontent/'+ id +'/edit?uniqid=' + uniqid, form)

                }

            });
            modal.style.display = "block";
        }
    }

    var classname = document.getElementsByClassName("open-modal-edit-content");

    for (var i = 0; i < classname.length; i++) {
        classname[i].addEventListener('click', function(e) {
            // console.log(e.target.next());
            lauchModalEditContent(e.target.dataset["id"])
        }, false);
    }

    var modal = $("#modalEditContent")[0];

// Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close-edit")[0];

// When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
})
