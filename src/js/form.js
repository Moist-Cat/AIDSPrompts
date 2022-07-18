
(function () {
    'use strict';
    window.addEventListener('load', function () {

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.getElementsByClassName('needs-validation');
        // Loop over them and prevent submission
        var validation = Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function () { }, false);
            $(".formDB").click(function (event) {
                $("#Command_SearchCode").prop('required', false);
                if (form.checkValidity() === false) {
                    form.classList.add('was-validated');
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementsByClassName('invalid-feedback')[0].parentElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
                
            });
            $(".formDB2").click(function (event) {
                if (form.checkValidity() === false) {
                    form.classList.add('was-validated');
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementsByClassName('invalid-feedback')[0].parentElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
                if ($("#Command_SearchCode").val() == "") {
                    $("#Command_SearchCode").prop('required', true);
                    form.classList.add('was-validated');
                    event.preventDefault();
                    event.stopPropagation();
                }
                
            });
        });

        $(document).ready(function () {
            // Number of WI currently on user page.
            var counterWI = (parseInt($("#Command_CWI").val()));

            // Add WorldInfo div on client side when Add Another is clicked. 
            $("#add-wi").click(function () {
                $('#anchorWI').append('<br id="br' + (counterWI + 1) + '">' + '<div id="world-info-card-' + (counterWI + 1) + '" class="card mb-4"> ' +
                    ' <div class="card-body">' + '<div class="form-group">' + '<label for="Command__WIK' + (counterWI + 1) + '">Keys</label>' +
                    ' <input class="form-control" type="text" id="Command__WIK' + (counterWI + 1) + '" name="Command._WIK' + (counterWI + 1) + '" value="" />' +
                    '</div>' + '<div class="form-group">' + '<label for="Command__WI' + (counterWI + 1) + '">Information</label>' +
                    '<textarea class="form-control" id="Command__WI' + (counterWI + 1) + '" name="Command._WI' + (counterWI + 1) + '"></textarea>' +
                    '</div>' + '   <div class="d-flex">' + '<button  type="button" id="Delete_' + (counterWI + 1) + '" class="world-info-delete-btn ml-auto btn btn-outline-danger" value=' + (counterWI + 1) + '>Delete</button>' +
                    '</div> ' + '</div>' + '</div>'

                );
                counterWI++;
                // We push the number of WI into hidden input to recover it on post.
                $("#Command_CWI").attr("value", counterWI);
            });

            // Delete the selectionned world info div when delete is clicked
            $(document).on('click', ".world-info-delete-btn", function () {
                var rem = parseInt($(this).attr("value"));
                $("#world-info-card-" + rem).remove();
                $("#br" + rem).remove();
                if (rem < counterWI) {
                    // We rename the WorldInfo div and element after this one to still be numbered correctly
                    for (var i = (rem + 1); i <= counterWI; i++) {
                        $("#world-info-card-" + i).attr("id", "world-info-card-" + (i - 1));
                        $("#Command__WIK" + i).attr("name", "Command._WIK" + (i - 1));
                        $("#Command__WIK" + i).attr("id", "Command__WIK" + (i - 1));
                        $("#Command__WI" + i).attr("name", "Command._WI" + (i - 1));
                        $("#Command__WI" + i).attr("id", "Command__WI" + (i - 1));
                        $("#br" + i).attr("id", "br" + (i - 1));
                        $("#Delete_" + i).attr("value", $("#Delete_" + i).val() - 1);
                        $("#Delete_" + i).attr("id", "Delete_" + (i - 1));


                    }
                }
                counterWI--;
                // We push the number of WI into hidden input to recover it on post.
                $("#Command_CWI").attr("value", counterWI);

            });

            //Function to read file 
            $("#upfile").click(function () {

                var fileInput = document.getElementById('fileInput');
                var file = fileInput.files[0];
                var lengthmax = 0;
                var reader = new FileReader();
                var ftags = "";
                var WIkeys;
                var i;
                var j;

                reader.onload = function (e) {

                    var str = reader.result.replace(/[\r\n]/gm, '');

                    $("#Command_File").val((str));
                    const fscenario = JSON.parse(str);

                    $("#Command_Title").val(fscenario.title.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/""/g, '"'));
                    $("#Command_PromptContent").val(fscenario.prompt.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/""/g, '"'));
                    $("#Command_Pf").val(fscenario.prompt);
                    $("#Command_Description").val(fscenario.description.replace(/\\/g, '').replace(/""/g, '"'));
                    for (let i in fscenario.tags)
                        ftags += fscenario.tags[i] + ",";
                    ftags = ftags.slice(0, ftags.length - 1)
                    $("#Command_PromptTags").val(ftags.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/\"/g, "").replace(/\s+/g, " "));
                    $("#Command_Memory").val(fscenario.context[0].text.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/""/g, '"'));
                    $("#Command_AuthorsNote").val(fscenario.context[1].text.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/""/g, '"'));

                    lengthmax = fscenario.lorebook.entries.length;
                    var p = counterWI;

                    //We remove World info div if too many on the page when compared to number of WI in the file.
                    for (i = p; i >= 0; i--) {

                        counterWI--;
                        $("#world-info-card-" + i).remove();
                        $("#br" + i).remove();

                    }

                    $("#Command_CWI").attr("value", counterWI);

                    for (let i in fscenario.lorebook.entries) {
                        WIkeys = "";
                        $('#add-wi').trigger('click');

                        // We give Key and Description the value in the file
                        $("#Command__WI" + i).val(fscenario.lorebook.entries[i].text.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/\"/g, "").trim());
                        for (let j in fscenario.lorebook.entries[i].keys)
                            WIkeys += fscenario.lorebook.entries[i].keys[j] + ",";
                        WIkeys = WIkeys.slice(0, WIkeys.length - 1)
                        $("#Command__WIK" + i).val(WIkeys.replace(/\\n/g, '\n').replace(/\\/g, '').replace(/\"/g, "").replace(/\s+/g, " "));


                    }
                    $("#Command_CWI").attr("value", counterWI);
                }

                reader.readAsText(file);
            });

            $("#Generate").click(function () {
                var result = '';
                var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789*/+-)([]$&';
                var charactersLength = characters.length;
                for (var i = 0; i < 10; i++) {
                    result += characters.charAt(Math.floor(Math.random() *
                        charactersLength));
                }

                $("#Command_GenerateCode").val(result);
            });
        });
    }, false);


})();
