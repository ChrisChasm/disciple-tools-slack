(function ($) {
    'use strict';



    //in slack post listing enable custom switch button

    $(document).ready(function($){

        //slack edit screen event section tab style
        $(document).on('click', '.nav-tab-wrapper a', function () {
            $(this).siblings().removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('section.cbxslackeventsection').hide();
            $('section.cbxslackeventsection').eq($(this).index()).show();
            return false;
        });


        //send test notification
        $(document).on('click', '.ppwpslack_test', function (e) {
            e.preventDefault();
            var $this = $(this);

            $('.ppwpslack_ajax_icon').show();
            var serviceurl = $this.data('serviceurl');
            var channel    = $this.data('channel');
            var username   = $this.data('username');
            var iconemoji  = $this.data('iconemoji');

            //ajax call for sending test notification
            jQuery.ajax({
                type: "post",
                dataType: "json",
                url: ppwpslack.ajaxurl,
                data: {
                    action: "ppwpslack_test_notification",
                    security: ppwpslack.nonce,
                    message: ppwpslack.message,
                    serviceurl: serviceurl,
                    channel: channel,
                    username: username,
                    iconemoji: iconemoji,
                },
                success: function (data, textStatus, XMLHttpRequest) {
                    $('.ppwpslack_ajax_icon').hide();
                    $('<p>' + ppwpslack.success + '</p>').insertAfter($this);
                }// end of success
            });// end of ajax
        });


        var elem = document.querySelector('.cbxslackjs-switch');
        var elems = Array.prototype.slice.call(document.querySelectorAll('.cbxslackjs-switch'));

        elems.forEach(function(changeCheckbox) {
            changeCheckbox.onchange = function() {
                //changeField.innerHTML = changeCheckbox.checked;
                //console.log(changeCheckbox.checked);
                var enable = (changeCheckbox.checked)? 1: 0;
                var postid = $(changeCheckbox).attr('data-postid');
                //ajax call for sending test notification
                jQuery.ajax({
                    type: "post",
                    dataType: "json",
                    url: ppwpslack.ajaxurl,
                    data: {
                        action: "ppwpslack_enable_disable",
                        security: ppwpslack.nonce,
                        enable: enable,
                        postid:postid
                    },
                    success: function (data, textStatus, XMLHttpRequest) {

                        //console.log(data);
                    }// end of success
                });// end of ajax
            };

            var switchery = new Switchery(changeCheckbox);
        });
    });



})(jQuery);
