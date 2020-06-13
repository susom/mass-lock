<?php
namespace Stanford\MassLock;

$massLock = $module; /** @var MassLock $massLock */

require_once \ExternalModules\ExternalModules::getProjectHeaderPath();

// Verify user rights
$massLock->validateUserRights('lock_record');
$massLock->determineRecords();
$massLock->handlePost();

// Render Page
renderPageTitle("<img src='".APP_PATH_IMAGES."application_view_icons.png' class='imgfix2'>&nbsp;Mass Lock a bunch of records");

?>
<div class="container">
    <?php
        if (!empty($massLock->errors)) {
            print "<div class='alert alert-danger'><ul class='pl-2'><li>" . implode("</li><li>", $massLock->errors) . "</li></ul></div>";
        }

        if (!empty($massLock->notes)) {
            print "<div class='alert alert-success'><ul class='pl-2'><li>" . implode("</li><li>", $massLock->notes) . "</li></ul></div>";
        }
    ?>

    <div class="row">
        <p>
            This module is used to lock a large number of records.
        </p>
    </div>
    <div class="row">
        <form class="lock_records" method='POST'>
            <div class="card">
                <div class="card-header">
                    <h6>Select Records for Locking</h6>
					<?php if (!empty($massLock->arm_options)) { ?>
                        <div>
                            <label for="arms">Arm:&nbsp;</label><select name="arm"><?php echo implode('',$massLock->arm_options) ?></select>
                        </div>
					<?php } ?>
                </div>
                <div class="card-body">
                    <div class="wrapper">
                        <ul><li><?php print implode("</li><li>",$massLock->record_checkboxes) ?></li></ul>
                    </div>
                </div>
                <div class="card-footer">
                    <div data-choice="all"    class="btn btn-sm btn-secondary sel"/>All</div>
                    <div data-choice="none"   class="btn btn-sm btn-secondary sel"/>None</div>
                    <div data-choice="custom" class="btn btn-sm btn-secondary customList"/>Custom List</div>
                    <div id="lock" data-choice='lock' class="btn pull-right btn-danger"><i class="far fa-trash-alt"></i> Lock Selected Records</div>
                </div>
            </div>
        </form>
    <!--</div>   name="Run" value="Lock Selected Records" -->
    </div>
</div>
<?php

require_once \ExternalModules\ExternalModules::getProjectFooterPath();

?>

<style>
    div.alert { border: inherit !important; }
    .wrapper {overflow:auto; max-height: 300px;}
    .wrapper ul li {float:left; width: <?php echo $massLock->max_length ?>em; display:inline-block;}
    .wrapper br {clear:left;}
    .wrapper {margin-bottom: 1em;}
    .cr {width: 100%; height: 200px; overflow:auto;}
    .lock_btn { background-color: red; }
    .lock_btn:hover { background-color: red; background-image: none; }
</style>
<script type='text/javascript'>
    $(document).ready( function() {
        $('.sel').click( function() {
            var state = $(this).data('choice') == 'all';
            $('input[name="records[]"]').prop('checked',state);
            return false;
        });

        $('#lock').click( function() {
            var num_selected = $('input[name="records[]"]:checked').length;
            if (num_selected == 0) {
                simpleDialog('<b>You must first at least one record</b>');
                return false;
            }

            initDialog("confirmLock");
            $('#confirmLock')
                .html("Are you sure you want to lock the " + num_selected + " selected records!")
                .dialog({
                    title: "Confirm Lock",
                    bgiframe:true,
                    modal:true,
                    width:550,
                    close: function() { $(this).dialog('destroy'); },
                    open: function(){ fitDialog(this) },
                    buttons: {
                        'Cancel': function() { $(this).dialog('destroy'); },
                        'Lock': function() {
                            $(this).dialog('destroy');
                            showProgress(1);
                            var input = $("<input>")
                                .attr("type", "hidden")
                                .attr("name", "lock").val("true");
                            $('form.lock_records').append(input).submit();
                        }
                    },
                    create:function () {
                        var b = $(this).closest(".ui-dialog")
                            .find(".ui-dialog-buttonset .ui-button:last").addClass("lock_btn");
                    }
                });

            console.log (num_selected);
            // var state = $(this).data('choice') == 'all';
            // $('input[name="records[]"]').prop('checked',state);
            return false;
        });


        $('.customList').click( function() {
            // Open up a pop-up with a list
            var data = "<p>Enter a comma-separated or return-separated list of record ids to select</p>" +
                "<textarea class='cr' name='custom_records' placeholder='Enter a comma-separated list of record id to select'></textarea>";
            initDialog("custom_records_dialog", data);
            $('#custom_records_dialog').dialog({ bgiframe: true, title: 'Enter Custom Record List',
                modal: true, width: 650,
                buttons: {
                    'Close': function() { $(this).dialog('destroy'); },
                    'Apply': function() {
                        // Parse out contents
                        var list = $('#custom_records_dialog textarea').val();
                        var items = $.map(list.split(/\n|,/), $.trim);
                        var notFound = [];
                        var countChecked = 0;
                        var countTotal = 0;
                        $(items).each(function(i, e) {
                            // console.log (i, e);
                            // Skip empties
                            if (e == '') return true;

                            // Verify the record is valid
                            var record = $('input[value="' + e + '"]');
                            if (record.length) {
                                record.prop('checked',true);
                                countChecked++;
                            } else {
                                // Not found
                                notFound.push(e);
                            }
                            countTotal++;
                        });
                        $(this).dialog('close');
                        // console.log(notFound);
                        var msg = '<b>' + countChecked + ' of ' + countTotal + ' records checked from custom list</b>';
                        if (notFound.length > 0) msg += '<br>&nbsp;- Unable to find:<br><ul><li>' + notFound.join('</li><li>') + '</li></ul>';
                        simpleDialog(msg, "Record Selection Results");
                    }
                }
            });
        });
    });

    $('select[name="arm"]').on('change',function() {
        $(this).closest('form').submit();
    });


</script>


