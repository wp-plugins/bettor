jQuery(document).ready(function () {
    bettor_loadMedia();    
});

jQuery(document).ready(function() {
            jQuery.timepicker.regional['de'] = {
                timeOnlyTitle: 'Uhrzeit auswählen',
                timeText: 'Zeit',
                hourText: 'Stunde',
                minuteText: 'Minute',
                secondText: 'Sekunde',
                currentText: 'Jetzt',
                closeText: 'Auswählen',
                ampm: false
              };    
            
            activateDatePicker('.bettor_kickoff');
            
            jQuery("#more_bet").click(function(){
                copyBetForm(this);
            });
        });
        
        function copyBetForm(button){
            countBet=jQuery(button).attr("value")*1;
            jQuery(button).attr("value",countBet+1);
            htmlcode=jQuery("#bettor_"+countBet).clone(true);
            if(typeof htmlcode==='undefinded'){
                return false;
            }

            htmlcode.prop('id', 'bettor_'+(countBet+1)+'' );
            htmlcode.find('input,textarea').val('');
            htmlcode.find('#bettor_kickoff_'+countBet).removeClass('hasDatepicker').removeData('datepicker').unbind();
            htmlcode.html(replaceAll('_'+countBet+'"', '_'+(countBet+1)+'"', htmlcode.html()));
            htmlcode.html(replaceAll('\['+countBet+'\]', '['+(countBet+1)+']', htmlcode.html()));
            jQuery("#bettor_"+countBet).after(htmlcode);
            jQuery("#bettor_"+countBet).after("<hr>");
                        
            activateDatePicker('.bettor_kickoff');
        }
        
        function replaceAll(find, replace, str) {
            return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
        }
        
        function escapeRegExp(string) {
            return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        }
        
        function activateDatePicker(id){
            jQuery(id).datetimepicker({
		inline: true,
		dateFormat:'yy-mm-dd',
		separator: ' ',
		timeFormat:'hh:mm',
		changeMonth: true,
		changeYear: true,
		firstDay: 1,
		minDate: new Date(2011, 0, 1),
		maxDate: '+24m',
		selectOtherMonths: true,
		showOtherMonths: true
            });
        }

function bettor_loadMedia() {
    if (jQuery('.upload_image_button').length > 0) {
// Uploading files
        var file_frame;
        var betid;
        var picture_id;

        jQuery('.upload_image_button').live('click', function (event) {
            id = this.id;
            id = id.split("_");
            picture_id = "imageBox_" + id[1];
            betid = "picture_" + id[1];

            event.preventDefault();

// If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

// Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: jQuery(this).data('uploader_title'),
                button: {
                    text: jQuery(this).data('uploader_button_text'),
                },
                multiple: false  // Set to true to allow multiple files to be selected
            });

// When an image is selected, run a callback.
            file_frame.on('select', function () {
                // We set multiple to false so only get one image from the uploader
                attachment = file_frame.state().get('selection').first().toJSON();
                jQuery("#" + picture_id).attr("src", attachment.url);
                jQuery("#" + betid).attr("value", attachment.id);
                // Do something with attachment.id and/or attachment.url here
            });

// Finally, open the modal
            file_frame.open();
        });
    }
}
