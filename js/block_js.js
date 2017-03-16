
var summarychecks = new Array();

function check_submit_activity(checkobj) {
    if (checkobj.checked == 1) {
        document.forms['exportactivityform'].submitpublish.disabled = 0;
        document.forms['exportactivityform'].submitpublish.className = '';
        summarychecks[checkobj.id] = 1;
    } else {
        document.forms['exportactivityform'].submitpublish.disabled = 1;
        document.forms['exportactivityform'].submitpublish.className = 'submit-disabled';
        summarychecks[checkobj.id] = 0;
        for (check in summarychecks) {
            if (summarychecks[check] == 1){
                document.forms['exportactivityform'].submitpublish.disabled = 0;
                document.forms['exportactivityform'].submitpublish.className = '';
                break;
            }
        }
    }
}