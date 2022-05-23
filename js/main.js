require(["jquery"], function ($) {
    // Super Wheel Script
    $(document).ready(function(){
    	$('.wheel-with-image').superWheel({
            slices: [
                {
                    text: 'images/1.png',
                    value: 1,
                    message: "You win SoundCloud Icon",
                    background: "#546E7A",

                },
                {
                    text: 'images/2.png',
                    value: 1,
                    message: "You win Twitter Icon",
                    background: "#455A64",

                },
                {
                    text: 'images/3.png',
                    value: 1,
                    message: "You win HTML5 Icon",
                    background: "#546E7A",

                },
                {
                    text: 'images/4.png',
                    value: 1,
                    message: "You win Skype Icon",
                    background: "#455A64",

                },
                {
                    text: 'images/5.png',
                    value: 1,
                    message: "You win Amazon Icon",
                    background: "#546E7A",

                },
                {
                    text: 'images/6.png',
                    value: 1,
                    message: "You win Appstore Icon",
                    background: "#455A64",

                }
            ],
            text : {
                type: 'image',
                color: '#CFD8DC',
                size: 25,
                offset : 10,
                orientation: 'h'

            },
            line: {
                width: 10,
                color: "#78909C"
            },
            outer: {
                width: 14,
                color: "#78909C"
            },
            inner: {
                width: 15,
                color: "#78909C"
            },
            marker: {
                background: "#00BCD4",
                animate: 1
            },

            selector: "value",
        });

        var tick = new Audio('media/tick.mp3');

        $(document).on('click','.wheel-with-image-spin-button',function(e){
            $('.wheel-with-image').superWheel('start','value',1);
            $(this).prop('disabled',true);
        });

        $('.wheel-with-image').superWheel('onStart',function(results){
            $('.wheel-with-image-spin-button').text('Spinning...');
        });
        $('.wheel-with-image').superWheel('onStep',function(results){
            if (typeof tick.currentTime !== 'undefined')
                tick.currentTime = 0;
            tick.play();
        });
        $('.wheel-with-image').superWheel('onComplete',function(results){
            //console.log(results.value);
            if(results.value === 1){
                alert(results.value);
            }else{
                alert('Opps error');
            }
            $('.wheel-with-image-spin-button:disabled').prop('disabled',false).text('Spin');
        });

    });

});
