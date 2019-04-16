/*!
 * Start Bootstrap - Freelancer Bootstrap Theme (http://startbootstrap.com)
 * Code licensed under the Apache License v2.0.
 * For details, see http://www.apache.org/licenses/LICENSE-2.0.
 */

// jQuery for page scrolling feature - requires jQuery Easing plugin
$(function() {
  $('body').on('click', '.page-scroll a', function(event) {
    var $anchor = $(this);

    $('html, body').stop().animate({
      scrollTop: $($anchor.attr('href')).offset().top,
    }, 1500, 'easeInOutExpo', function() {
      var anchor = $(event.target).attr('href');
      if (anchor === undefined) anchor = '';
      location.hash = anchor;
    });

    event.preventDefault();
  });
});

// Floating label headings for the contact form
$(function() {
  $('body').on('input propertychange', '.floating-label-form-group', function(e) {
    $(this).toggleClass('floating-label-form-group-with-value', !! $(e.target).val());
  }).on('focus', '.floating-label-form-group', function() {
    $(this).addClass('floating-label-form-group-with-focus');
  }).on('blur', '.floating-label-form-group', function() {
    $(this).removeClass('floating-label-form-group-with-focus');
  });
});

$(function() {
  $('.price1').on('click', function() {
    location.href = './#/registration/1';
  });
  $('.price2').on('click', function() {
    location.href = './#/registration/2';
  });
  $('.price3').on('click', function() {
    location.href = './#/registration/3';
  });
});

// Highlight the top nav as scrolling occurs
$('body').scrollspy({
  target: '.navbar-fixed-top'
});

// Closes the Responsive Menu on Menu Item Click
$('.navbar-collapse ul li a').click(function() {
  $('.navbar-toggle:visible').click();
});
