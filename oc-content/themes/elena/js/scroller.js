// This function will be executed when the user scrolls the page.
$(window).scroll(function(e) {
    // Get the position of the location where the scroller starts.
    var scroller_top = $(".scroller_top").offset().top;
    var scroller_top2 = $(".scroller_top2").offset().top;
     
    // Check if the user has scrolled and the current position is after the scroller start location and if its not already fixed at the top 
    if ($(this).scrollTop() >= scroller_top && $('.scroller').css('position') != 'fixed') 
    {    // Change the CSS of the scroller to hilight it and fix it at the top of the screen.
        $('.scroller').css({
            'position': 'fixed',
            'top': '0px',
            'z-index': '5',
        });
        // Changing the height of the scroller anchor to that of scroller so that there is no change in the overall height of the page.
        $('.scroller_top').css('height', '50px');
    } 
    else if ($(this).scrollTop() < scroller_top && $('.scroller').css('position') != 'relative') 
    {    // If the user has scrolled back to the location above the scroller anchor place it back into the content.
         
        // Change the height of the scroller anchor to 0 and now we will be adding the scroller back to the content.
        $('.scroller_top').css('height', '0px');
         
        // Change the CSS and put it back to its original position.
        $('.scroller').css({
            'position': 'relative'
        });
    }

    // Check if the user has scrolled and the current position is after the scroller start location and if its not already fixed at the top 
    if ($(this).scrollTop() >= scroller_top2 && $('.scroller2').css('position') != 'fixed') 
    {    // Change the CSS of the scroller to hilight it and fix it at the top of the screen.
        $('.scroller2').css({
            'position': 'fixed',
            'top': '0px',
            'z-index': '1',
            'background': '#ffffff'
        });
        // Changing the height of the scroller anchor to that of scroller so that there is no change in the overall height of the page.
        $('.scroller_top2').css('height', '50px');
    } 
    else if ($(this).scrollTop() < scroller_top2 && $('.scroller2').css('position') != 'relative') 
    {    // If the user has scrolled back to the location above the scroller anchor place it back into the content.
         
        // Change the height of the scroller anchor to 0 and now we will be adding the scroller back to the content.
        $('.scroller_top2').css('height', '0px');
         
        // Change the CSS and put it back to its original position.
        $('.scroller2').css({
            'position': 'relative'
        });
    }


});

